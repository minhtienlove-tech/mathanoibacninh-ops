#!/usr/bin/env python3
"""Bounded, read-only inventory of the hospital's public URLs.

No credentials, writes only to this report directory. Run at <= 1.5 requests/s.
"""
import csv
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from collections import Counter, defaultdict, deque
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

ROOT = "https://mathanoibacninh.com"
HOST = "mathanoibacninh.com"
OUT = Path(__file__).resolve().parent
DELAY = 0.72
MAX_URLS = 700
NS = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
UA = "HospitalSiteAudit/2026-10-05 (read-only, 1.4 req/s)"
last_request = 0.0


def polite_request(url):
    global last_request
    elapsed = time.monotonic() - last_request
    if elapsed < DELAY:
        time.sleep(DELAY - elapsed)
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "text/html,application/xml,*/*"})
    last_request = time.monotonic()
    try:
        with urllib.request.urlopen(req, timeout=25) as r:
            raw = r.read(5_000_001)
            return int(r.status), r.url, dict(r.headers), raw, len(raw) > 5_000_000
    except urllib.error.HTTPError as e:
        raw = e.read(200_000)
        if int(e.code) in (429, 502, 503, 504):
            retry_after = e.headers.get("Retry-After", "")
            try:
                time.sleep(min(int(retry_after), 30))
            except (ValueError, TypeError):
                time.sleep(3)
        return int(e.code), e.url, dict(e.headers), raw, False
    except Exception as e:
        return 0, url, {}, repr(e).encode("utf-8"), False


def norm_url(href, base=ROOT):
    if not href or href.startswith(("#", "mailto:", "tel:", "javascript:", "data:")):
        return ""
    u = urllib.parse.urlsplit(urllib.parse.urljoin(base, href))
    if u.scheme not in ("http", "https") or u.hostname not in (HOST, "www." + HOST):
        return ""
    if u.path.startswith(("/wp-admin/", "/wp-login.php", "/wp-json/", "/wp-content/", "/wp-includes/")):
        return ""
    if re.search(r"\.(?:jpg|jpeg|png|gif|svg|webp|avif|pdf|css|js|ico|woff2?|ttf|zip|xml)$", u.path, re.I):
        return ""
    if u.query:
        # Keep meaningful public query routes but prevent infinite tracking/filter loops.
        q = urllib.parse.parse_qsl(u.query, keep_blank_values=True)
        if len(q) > 3 or any(k.lower().startswith(("utm_", "fbclid", "gclid", "replytocom")) for k, _ in q):
            return ""
    path = u.path or "/"
    path = re.sub(r"/{2,}", "/", path)
    return urllib.parse.urlunsplit(("https", HOST, path, u.query, ""))


class PageParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title_mode = False
        self.title = ""
        self.meta_robots = []
        self.canonical = []
        self.links = []
        self.body_classes = ""
        self.h1 = []
        self.h1_mode = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "title":
            self.title_mode = True
        elif tag == "h1":
            self.h1_mode = True
        elif tag == "meta" and a.get("name", "").lower() in ("robots", "googlebot"):
            self.meta_robots.append(a.get("content", ""))
        elif tag == "link" and "canonical" in a.get("rel", "").lower().split():
            self.canonical.append(a.get("href", ""))
        elif tag == "a" and a.get("href"):
            self.links.append(a["href"])
        elif tag == "body":
            self.body_classes = a.get("class", "")

    def handle_endtag(self, tag):
        if tag == "title":
            self.title_mode = False
        elif tag == "h1":
            self.h1_mode = False

    def handle_data(self, data):
        if self.title_mode:
            self.title += data
        if self.h1_mode:
            self.h1.append(data)


def classify(url, body_class="", source=""):
    path = urllib.parse.urlsplit(url).path
    if path == "/":
        return "trang chủ"
    if re.search(r"/page/\d+/?$", path):
        return "phân trang"
    if urllib.parse.urlsplit(url).query:
        return "tìm kiếm/lọc/tham số"
    if path.startswith("/khu-vuc/"):
        return "trang địa phương" if path != "/khu-vuc/" else "trang khu vực"
    if path.startswith("/kien-thuc/"):
        return "bài viết" if "posts" in source or "single-post" in body_class else "thư viện/chuyên mục"
    if "cats" in source or "category" in body_class:
        return "chuyên mục"
    if path.startswith("/dich-vu/"):
        return "dịch vụ"
    if path.startswith("/doi-ngu/") or path.startswith("/bac-si/"):
        return "chuyên gia"
    if path.startswith("/gioi-thieu/"):
        return "giới thiệu"
    if path.startswith("/lien-he/"):
        return "liên hệ"
    if "posts" in source or "single-post" in body_class:
        return "bài viết"
    return "trang"


def save_csv(path, rows, columns):
    with path.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.DictWriter(f, fieldnames=columns, extrasaction="ignore", quoting=csv.QUOTE_ALL)
        w.writeheader()
        w.writerows(rows)


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    started = datetime.now(timezone.utc).isoformat()
    sources = defaultdict(set)
    sitemap_lastmod = {}
    in_sitemap = set()
    seed_status = []
    sitemap_index_urls = [ROOT + "/wp-sitemap.xml", ROOT + "/obs-sitemap.xml"]
    sitemap_children = set()
    for index_url in sitemap_index_urls:
        status, final, headers, raw, truncated = polite_request(index_url)
        seed_status.append({"url": index_url, "status": status, "final": final, "bytes": len(raw)})
        if status != 200:
            continue
        try:
            xml = ET.fromstring(raw)
            for el in xml.findall("sm:sitemap", NS):
                loc = el.findtext("sm:loc", default="", namespaces=NS)
                if loc:
                    sitemap_children.add(loc)
        except ET.ParseError as e:
            seed_status[-1]["error"] = str(e)
    for child in sorted(sitemap_children):
        status, final, headers, raw, truncated = polite_request(child)
        seed_status.append({"url": child, "status": status, "final": final, "bytes": len(raw)})
        if status != 200:
            continue
        try:
            xml = ET.fromstring(raw)
            for el in xml.findall("sm:url", NS):
                loc = norm_url(el.findtext("sm:loc", default="", namespaces=NS))
                mod = el.findtext("sm:lastmod", default="", namespaces=NS)
                if loc:
                    in_sitemap.add(loc)
                    sources[loc].add(child.rsplit("/", 1)[-1])
                    sitemap_lastmod[loc] = mod
        except ET.ParseError as e:
            seed_status[-1]["error"] = str(e)
    robots_status, robots_final, _, robots_body, _ = polite_request(ROOT + "/robots.txt")
    seed_status.append({"url": ROOT + "/robots.txt", "status": robots_status, "final": robots_final, "bytes": len(robots_body)})
    sources[ROOT + "/"].add("seed:home")
    # Public WordPress REST lists provide a second, independent finite CMS source.
    cms = set()
    for kind in ("posts", "pages"):
        page = 1
        while page <= 5:
            u = f"{ROOT}/wp-json/wp/v2/{kind}?per_page=100&page={page}&_fields=id,link,status,slug"
            status, final, headers, raw, truncated = polite_request(u)
            seed_status.append({"url": u, "status": status, "bytes": len(raw), "kind": "cms"})
            if status != 200:
                break
            try:
                arr = json.loads(raw)
            except json.JSONDecodeError:
                break
            if not isinstance(arr, list) or not arr:
                break
            for item in arr:
                url = norm_url(item.get("link", ""))
                if url and item.get("status") == "publish":
                    sources[url].add("cms:" + kind)
                    cms.add(url)
            if len(arr) < 100:
                break
            page += 1
    initial = sorted(sources)
    (OUT / "url-seeds.json").write_text(json.dumps({"started_utc": started, "sitemap": sorted(in_sitemap), "cms": sorted(cms), "all": initial, "seed_fetch": seed_status, "robots_text": robots_body.decode("utf-8", "replace")}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"SEED_READY sitemap={len(in_sitemap)} cms={len(cms)} union={len(initial)}", flush=True)

    queue = deque(initial)
    rows = {}
    link_edges = set()
    excluded = Counter()
    blocked = 0
    while queue and len(rows) < MAX_URLS:
        original = queue.popleft()
        if original in rows:
            continue
        status, final, headers, raw, truncated = polite_request(original)
        final_norm = norm_url(final) or final
        ct = headers.get("Content-Type", headers.get("content-type", ""))
        parsed = PageParser()
        html = status == 200 and ("text/html" in ct or raw[:100].lstrip().lower().startswith((b"<!doctype html", b"<html")))
        if html:
            parsed.feed(raw.decode("utf-8", "replace"))
            for href in parsed.links:
                target = norm_url(href, final)
                if target:
                    link_edges.add((original, target))
                    if target not in rows and target not in sources and len(sources) < MAX_URLS:
                        sources[target].add("internal-link:" + original)
                        queue.append(target)
                    elif target in sources:
                        sources[target].add("internal-link:" + original)
        else:
            if status in (401, 403, 429):
                blocked += 1
        xrobots = headers.get("X-Robots-Tag", headers.get("x-robots-tag", ""))
        meta_robots = " | ".join(parsed.meta_robots)
        canonical = " | ".join(parsed.canonical)
        issue = []
        if status >= 400 or status == 0:
            issue.append("HTTP_ERROR")
        if status == 200 and not html:
            issue.append("NON_HTML")
        if len(parsed.canonical) > 1:
            issue.append("MULTIPLE_CANONICAL")
        if len(parsed.canonical) == 1 and norm_url(parsed.canonical[0], final) != final_norm:
            issue.append("CANONICAL_DIFFERS")
        if ("noindex" in (meta_robots + xrobots).lower()) and original in in_sitemap:
            issue.append("SITEMAP_NOINDEX")
        if status == 200 and html and not parsed.title.strip():
            issue.append("TITLE_MISSING")
        rows[original] = {
            "original_url": original,
            "final_url": final,
            "discovery_source": " | ".join(sorted(sources[original])),
            "sitemap": "yes" if original in in_sitemap else "no",
            "sitemap_lastmod": sitemap_lastmod.get(original, ""),
            "http_status": status,
            "content_type": ct,
            "meta_robots": meta_robots,
            "x_robots_tag": xrobots,
            "noindex": "yes" if "noindex" in (meta_robots + xrobots).lower() else "no",
            "canonical": canonical,
            "title": parsed.title.strip(),
            "h1": " ".join(" ".join(parsed.h1).split()),
            "template": classify(original, parsed.body_classes, " | ".join(sources[original])),
            "body_class": parsed.body_classes,
            "fetch_state": "fetched" if status else "error",
            "render_state": "not-rendered",
            "html_bytes": len(raw),
            "response_truncated": "yes" if truncated else "no",
            "issue_id": " | ".join(issue),
        }
        if len(rows) % 25 == 0:
            print(f"FETCHED {len(rows)} queued={len(queue)} discovered={len(sources)}", flush=True)
            write_checkpoint(rows, sources, link_edges, in_sitemap, cms, excluded, blocked, started, False)
    for u in queue:
        excluded["not_fetched_limit"] += 1
    write_checkpoint(rows, sources, link_edges, in_sitemap, cms, excluded, blocked, started, True)
    print(f"DONE discovered={len(sources)} fetched={len(rows)} rendered=0 excluded={sum(excluded.values())} blocked={blocked}", flush=True)


def write_checkpoint(rows, sources, link_edges, in_sitemap, cms, excluded, blocked, started, done):
    columns = ["original_url", "final_url", "discovery_source", "sitemap", "sitemap_lastmod", "http_status", "content_type", "meta_robots", "x_robots_tag", "noindex", "canonical", "title", "h1", "template", "body_class", "fetch_state", "render_state", "html_bytes", "response_truncated", "issue_id"]
    items = []
    for url in sorted(sources):
        if url in rows:
            d = dict(rows[url])
            d["discovery_source"] = " | ".join(sorted(sources[url]))
            d["template"] = classify(url, d["body_class"], d["discovery_source"])
        else:
            d = {"original_url": url, "discovery_source": " | ".join(sorted(sources[url])), "sitemap": "yes" if url in in_sitemap else "no", "fetch_state": "not-fetched", "render_state": "not-rendered", "issue_id": "NOT_FETCHED"}
        items.append(d)
    save_csv(OUT / "DANH-MUC-URL.csv", items, columns)
    save_csv(OUT / "internal-link-edges.csv", ({"source": s, "target": t} for s, t in sorted(link_edges)), ["source", "target"])
    stats = {
        "started_utc": started,
        "checkpoint_utc": datetime.now(timezone.utc).isoformat(),
        "done": done,
        "sitemap_urls": len(in_sitemap),
        "cms_urls": len(cms),
        "discovered": len(sources),
        "fetched": len(rows),
        "rendered": 0,
        "excluded": dict(excluded),
        "blocked": blocked,
        "http_status": dict(Counter(str(v["http_status"]) for v in rows.values())),
        "issue_id": dict(Counter(issue for v in rows.values() for issue in v["issue_id"].split(" | ") if issue)),
        "sitemap_not_in_cms": sorted(in_sitemap - cms),
        "cms_not_in_sitemap": sorted(cms - in_sitemap),
        "no_inbound_from_fetched": sorted(u for u in sources if u != ROOT + "/" and not any(t == u for _, t in link_edges)),
    }
    (OUT / "inventory-stats.json").write_text(json.dumps(stats, ensure_ascii=False, indent=2), encoding="utf-8")


if __name__ == "__main__":
    main()
