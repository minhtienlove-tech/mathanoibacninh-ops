#!/usr/bin/env python3
"""Read-only, rate-limited audit of public UI/schema/sitemap dates.

Run with the bundled Python runtime that includes lxml. No credentials are used.
Raw per-URL evidence is checkpointed in date-crawl-raw.jsonl.
"""

import csv
import datetime as dt
import json
import pathlib
import re
import sys
import time
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET

from lxml import html


HERE = pathlib.Path(__file__).resolve().parent
ROOT = "https://mathanoibacninh.com"
SITEMAP = ROOT + "/wp-sitemap.xml"
AGENT = "HospitalWebsiteAudit/2026-10-05 (read-only; 1.25 requests/s)"
# Vietnam had no daylight-saving shift in the dates covered by this audit.
LOCAL_TZ = dt.timezone(dt.timedelta(hours=7))
NS = {"s": "http://www.sitemaps.org/schemas/sitemap/0.9"}
INTERVAL = 0.8


def get(url):
    for attempt in range(4):
        request = urllib.request.Request(url, headers={"User-Agent": AGENT})
        try:
            with urllib.request.urlopen(request, timeout=25) as response:
                return response.status, response.geturl(), dict(response.headers), response.read()
        except urllib.error.HTTPError as exc:
            if exc.code in (429, 500, 502, 503, 504) and attempt < 3:
                retry = exc.headers.get("Retry-After")
                try:
                    delay = min(60, float(retry)) if retry else (2 ** attempt) * 3
                except ValueError:
                    delay = (2 ** attempt) * 3
                time.sleep(delay)
                continue
            return exc.code, exc.geturl(), dict(exc.headers), exc.read()
        except Exception as exc:
            if attempt < 3:
                time.sleep((2 ** attempt) * 3)
                continue
            return 0, url, {}, str(exc).encode("utf-8")


def parse_sitemap():
    status, final, _, data = get(SITEMAP)
    if status != 200:
        raise RuntimeError(f"Sitemap index HTTP {status}: {final}")
    index = ET.fromstring(data)
    children = []
    entries = {}
    for item in index.findall("s:sitemap", NS):
        loc = item.findtext("s:loc", default="", namespaces=NS)
        lastmod = item.findtext("s:lastmod", default="", namespaces=NS)
        if not loc:
            continue
        children.append({"loc": loc, "lastmod": lastmod})
        time.sleep(INTERVAL)
        child_status, _, _, body = get(loc)
        if child_status != 200:
            raise RuntimeError(f"Child sitemap HTTP {child_status}: {loc}")
        child = ET.fromstring(body)
        for entry in child.findall("s:url", NS):
            url = entry.findtext("s:loc", default="", namespaces=NS)
            if url:
                candidate = {
                    "sitemap": loc,
                    "lastmod": entry.findtext("s:lastmod", default="", namespaces=NS),
                }
                # /kien-thuc/ occurs in both page and category sitemaps. Keep
                # its page-level lastmod rather than overwrite it with an empty
                # category entry from the sitemap parsed last.
                if url not in entries or (candidate["lastmod"] and not entries[url]["lastmod"]):
                    entries[url] = candidate
    return children, entries


def load_wp():
    mapping = {}
    with (HERE / "wp-dates-raw.csv").open("r", encoding="utf-8-sig", newline="") as handle:
        for row in csv.DictReader(handle):
            if row.get("url"):
                mapping[row["url"]] = row
    return mapping


def tidy(value):
    return " ".join((value or "").split())


def norm(value):
    if not value:
        return ""
    try:
        if re.fullmatch(r"\d{4}-\d\d-\d\d", value):
            return value
        obj = dt.datetime.fromisoformat(value.replace("Z", "+00:00"))
        if obj.tzinfo:
            obj = obj.astimezone(LOCAL_TZ)
        return obj.isoformat(timespec="seconds")
    except ValueError:
        return "INVALID"


def walk_schema(obj):
    if isinstance(obj, list):
        for item in obj:
            yield from walk_schema(item)
    elif isinstance(obj, dict):
        if any(key in obj for key in ("datePublished", "dateModified", "dateReviewed", "lastReviewed")):
            yield {
                "@id": obj.get("@id", ""),
                "@type": obj.get("@type", ""),
                "datePublished": obj.get("datePublished", ""),
                "dateModified": obj.get("dateModified", ""),
                "dateReviewed": obj.get("dateReviewed", ""),
                "lastReviewed": obj.get("lastReviewed", ""),
            }
        for value in obj.values():
            yield from walk_schema(value)


def parse_page(url, wp, sitemap, response):
    status, final, headers, data = response
    result = {
        "url": url,
        "final_url": final,
        "http_status": status,
        "fetch_utc": dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds"),
        "content_type": headers.get("Content-Type", ""),
        "sitemap": sitemap.get("sitemap", ""),
        "sitemap_lastmod": sitemap.get("lastmod", ""),
        "wp": wp or {},
        "time_elements": [],
        "visible_date_labels": [],
        "schema_dates": [],
        "schema_parse_errors": [],
        "og_dates": {},
        "meta_description": "",
        "date_text_excerpt": "",
    }
    if status != 200 or "html" not in result["content_type"]:
        return result
    try:
        tree = html.fromstring(data)
    except Exception as exc:
        result["html_parse_error"] = str(exc)
        return result
    for meta in tree.xpath("//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='description']"):
        result["meta_description"] = tidy(meta.get("content", ""))
        break
    for element in tree.xpath("//time"):
        parent_text = tidy(element.getparent().text_content())[:220]
        result["time_elements"].append(
            {
                "datetime": element.get("datetime", ""),
                "datetime_local": norm(element.get("datetime", "")),
                "text": tidy(element.text_content()),
                "parent_text": parent_text,
                "class": element.getparent().get("class", ""),
            }
        )
    for element in tree.xpath("//main//*[self::p or self::div or self::span or self::small]"):
        if len(element):
            continue
        value = tidy(element.text_content())
        if re.search(r"(?:Đăng ngày|Cập nhật lần cuối|Duyệt chuyên môn|Bác sĩ duyệt|Ngày duyệt|Cập nhật nội dung)", value, re.I):
            result["visible_date_labels"].append(value[:200])
            if len(result["visible_date_labels"]) >= 15:
                break
    for meta in tree.xpath("//meta[starts-with(@property, 'article:')]"):
        name = meta.get("property", "")
        if name in ("article:published_time", "article:modified_time"):
            result["og_dates"][name] = meta.get("content", "")
    for script_index, script in enumerate(tree.xpath("//script[@type='application/ld+json']")):
        try:
            document = json.loads(script.text or "")
            for entity in walk_schema(document):
                entity["script_index"] = script_index
                result["schema_dates"].append(entity)
        except Exception as exc:
            result["schema_parse_errors"].append(str(exc)[:120])
    for element in tree.xpath("//main//*[contains(@class,'chinh-sach__ngay')]"):
        result["date_text_excerpt"] = tidy(element.text_content())[:200]
    return result


def pick_schema(result):
    # Prefer child-theme Article/MedicalWebPage; retain all entities in raw JSONL.
    ranked = sorted(
        result["schema_dates"],
        key=lambda x: (
            "#bai-viet" not in x.get("@id", "") and "#noi-dung-y-khoa" not in x.get("@id", ""),
            "Article" not in str(x.get("@type", "")) and "MedicalWebPage" not in str(x.get("@type", "")),
        ),
    )
    return ranked[0] if ranked else {}


def summary_row(result, pending_review_ids):
    wp = result["wp"]
    schema = pick_schema(result)
    all_schemas = result["schema_dates"]
    main_dates = [x for x in result["time_elements"] if "eyecare-bai__ngay" in x["class"]]
    if not main_dates:
        main_dates = [x for x in result["time_elements"] if "eyecare-tac-gia__cap-nhat" in x["class"]]
    ui = main_dates[0] if main_dates else {}
    wp_pub = wp.get("post_date", "")
    wp_mod = wp.get("post_modified", "")
    pub = schema.get("datePublished", "")
    mod = schema.get("dateModified", "")
    lastmod = result.get("sitemap_lastmod", "")
    notes = []
    issues = []
    pending_review = wp.get("ID", "") in pending_review_ids
    if pending_review and not all_schemas:
        notes.append("Article date-bearing JSON-LD withheld pending author and medical review; verified WP post meta")
    for entity in all_schemas:
        item_pub, item_mod = entity.get("datePublished", ""), entity.get("dateModified", "")
        if wp and item_pub and norm(item_pub)[:19] != norm(wp_pub)[:19]:
            issues.append("D01")
            notes.append("one schema entity datePublished differs from WP post_date")
        if wp and item_mod and norm(item_mod)[:19] != norm(wp_mod)[:19]:
            issues.append("D02")
            notes.append("one schema entity dateModified differs from WP post_modified")
    og_pub = result["og_dates"].get("article:published_time", "")
    og_mod = result["og_dates"].get("article:modified_time", "")
    if wp and ((og_pub and norm(og_pub)[:19] != norm(wp_pub)[:19]) or (og_mod and norm(og_mod)[:19] != norm(wp_mod)[:19])):
        issues.append("D09")
        notes.append("OG article time differs from corresponding WP date")
    if wp and lastmod and norm(lastmod)[:19] != norm(wp_mod)[:19]:
        # Different sitemap and article modification timestamps require
        # contextual investigation; mark suspected, not automatically wrong.
        issues.append("D03?")
        notes.append("sitemap lastmod differs from WP post_modified; investigate")
    if wp and ui and ui.get("datetime"):
        expected = wp_mod if re.search("Cập nhật", ui.get("parent_text", ""), re.I) else wp_pub
        if norm(ui["datetime"])[:10] != norm(expected)[:10]:
            issues.append("D04")
            notes.append("UI time date differs from corresponding WP date")
    if result.get("date_text_excerpt") and wp_mod:
        match = re.search(r"(\d{1,2})/(\d{1,2})/(\d{4})", result["date_text_excerpt"])
        if match:
            date = f"{int(match.group(3)):04}-{int(match.group(2)):02}-{int(match.group(1)):02}"
            if date != wp_mod[:10]:
                issues.append("D05")
                notes.append("privacy policy visible last-updated differs from WP post_modified")
    for key, value in (("datePublished", pub), ("dateModified", mod), ("lastmod", lastmod)):
        normalized = norm(value)
        if normalized == "INVALID":
            issues.append("D06")
            notes.append(f"invalid {key} value")
        if normalized and normalized != "INVALID" and normalized[:10] > "2026-10-05":
            issues.append("D07")
            notes.append(f"future {key} date")
    if pub and mod and norm(mod) != "INVALID" and norm(pub) != "INVALID" and norm(mod) < norm(pub):
        issues.append("D08")
        notes.append("dateModified precedes datePublished")
    return {
        "URL": result["url"],
        "URL cuoi": result["final_url"],
        "HTTP": result["http_status"],
        "Loai trang": wp.get("post_type", "taxonomy/archive"),
        "WP ID": wp.get("ID", ""),
        "WP trang thai duyet": "pending-author-and-medical-review" if pending_review else "",
        "Nhan UI": ui.get("parent_text", ""),
        "Ngay UI": ui.get("text", "") or result.get("date_text_excerpt", ""),
        "time datetime": ui.get("datetime", ""),
        "time datetime chuan hoa": ui.get("datetime_local", ""),
        "Ngay duyet UI/khac": " | ".join(result["visible_date_labels"]),
        "datePublished": pub,
        "datePublished chuan hoa": norm(pub),
        "dateModified": mod,
        "dateModified chuan hoa": norm(mod),
        "Tat ca schema ngay": json.dumps(all_schemas, ensure_ascii=False, separators=(",", ":")),
        "lastReviewed/dateReviewed": schema.get("lastReviewed", "") or schema.get("dateReviewed", ""),
        "WP ngay duyet": "",  # WP-CLI --meta_key=_bvmat_bac_si_duyet returned 0 published entries on audit date.
        "article:published_time": og_pub,
        "article:modified_time": og_mod,
        "Sitemap lastmod": lastmod,
        "Sitemap lastmod chuan hoa": norm(lastmod),
        "Sitemap": result.get("sitemap", ""),
        "WP post_date": wp_pub,
        "WP post_modified": wp_mod,
        "Mui gio": "Asia/Ho_Chi_Minh (+07:00)",
        "Nguon": "public HTML/JSON-LD; OBS sitemap; WP-CLI published post fields",
        "Trang thai": "Không đánh giá ngày (HTTP không phải 200)" if result["http_status"] != 200 else ("Cần kiểm tra" if issues else "Khớp/không có ngày áp dụng"),
        "Issue ID": ";".join(dict.fromkeys(issues)),
        "Ghi chu": "; ".join(notes),
    }


def main():
    wp = load_wp()
    discovered = set()
    inventory = HERE / "DANH-MUC-URL.csv"
    if inventory.exists():
        with inventory.open("r", encoding="utf-8-sig", newline="") as handle:
            discovered = {row["original_url"] for row in csv.DictReader(handle) if row.get("original_url")}
    raw_path = HERE / "date-crawl-raw.jsonl"
    done = {}
    if raw_path.exists():
        for line in raw_path.read_text(encoding="utf-8").splitlines():
            try:
                item = json.loads(line)
                done[item["url"]] = item
            except Exception:
                pass
    if "--offline" in sys.argv:
        sitemap = {
            url: {"sitemap": item.get("sitemap", ""), "lastmod": item.get("sitemap_lastmod", "")}
            for url, item in done.items()
            if item.get("sitemap")
        }
        children = json.loads((HERE / "sitemap-index-raw.json").read_text(encoding="utf-8"))
    else:
        children, sitemap = parse_sitemap()
        (HERE / "sitemap-index-raw.json").write_text(json.dumps(children, ensure_ascii=False, indent=2), encoding="utf-8")
    urls = sorted(set(sitemap) | set(wp) | discovered | set(done) | {ROOT + "/"})
    if "--offline" in sys.argv and not set(urls).issubset(done):
        raise RuntimeError("Offline checkpoint is missing URLs; run online to fetch missing pages")
    print(f"Sitemap URLs={len(sitemap)}, WP published posts/pages={len(wp)}, inventory URLs={len(discovered)}, union+home={len(urls)}, cached={len(done)}", flush=True)
    with raw_path.open("a", encoding="utf-8") as out:
        for index, url in enumerate(urls, 1):
            if url in done:
                continue
            started = time.monotonic()
            item = parse_page(url, wp.get(url), sitemap.get(url, {}), get(url))
            out.write(json.dumps(item, ensure_ascii=False) + "\n")
            out.flush()
            done[url] = item
            if index % 25 == 0:
                print(f"{index}/{len(urls)} fetched; HTTP {item['http_status']} {url}", flush=True)
            time.sleep(max(0, INTERVAL - (time.monotonic() - started)))
        missing_meta = [url for url in urls if "meta_description" not in done[url]]
        if missing_meta:
            print(f"Backfilling meta descriptions for {len(missing_meta)} checkpointed URLs", flush=True)
        for index, url in enumerate(missing_meta, 1):
            started = time.monotonic()
            item = parse_page(url, wp.get(url), sitemap.get(url, {}), get(url))
            out.write(json.dumps(item, ensure_ascii=False) + "\n")
            out.flush()
            done[url] = item
            if index % 25 == 0:
                print(f"meta backfill {index}/{len(missing_meta)}", flush=True)
            time.sleep(max(0, INTERVAL - (time.monotonic() - started)))
    pending_path = HERE / "wp-pending-review-ids.txt"
    pending_review_ids = set(pending_path.read_text(encoding="utf-8-sig").split()) if pending_path.exists() else set()
    rows = [summary_row(done[url], pending_review_ids) for url in urls]
    with (HERE / "DOI-CHIEU-NGAY.csv").open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=list(rows[0]), quoting=csv.QUOTE_MINIMAL)
        writer.writeheader()
        writer.writerows(rows)
    with (HERE / "meta-descriptions-raw.csv").open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=["URL", "HTTP", "meta_description"], quoting=csv.QUOTE_MINIMAL)
        writer.writeheader()
        writer.writerows([{"URL": url, "HTTP": done[url]["http_status"], "meta_description": done[url].get("meta_description", "")} for url in urls])
    print("Wrote DOI-CHIEU-NGAY.csv", flush=True)


if __name__ == "__main__":
    main()
