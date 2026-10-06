#!/usr/bin/env python3
"""Append five historical Search Console examples to the public URL inventory.

Run only after crawl-url-inventory.py has finished, to preserve the rate limit.
"""
import csv
import importlib.util
import json
import time
import urllib.error
import urllib.request
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

OUT = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location("inventory", OUT / "crawl-url-inventory.py")
inv = importlib.util.module_from_spec(spec)
spec.loader.exec_module(inv)

GSC = [
    ("https://mathanoibacninh.com/2026/08/11/hello-world/", "GSC:404, last crawl 2026-09-21"),
    ("https://mathanoibacninh.com/Phone", "GSC:404, last crawl 2026-09-21"),
    ("https://mathanoibacninh.com/category/dich-vu/phau-thuat-khuc-xa/phau-thuat-khuc-xa-femtolasik/", "GSC:404, last crawl 2026-09-20"),
    ("https://mathanoibacninh.com/laser-quang-dong-vong-mac-7-chi-dinh-quy-trinh-va-hieu-qua-dieu-tri-hien-nay/", "GSC:5xx, last crawl 2026-09-04"),
    ("https://mathanoibacninh.com/trong-kinh-essilor-stellest-1-giai-phap-kiem-soat-tien-trien-can-thi-o-tre-em/", "GSC:5xx, last crawl 2026-09-03"),
]


class RedirectTrace(urllib.request.HTTPRedirectHandler):
    def __init__(self):
        self.trace = []

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        self.trace.append(f"{code} {req.full_url} -> {newurl}")
        time.sleep(inv.DELAY)
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def fetch(url):
    redir = RedirectTrace()
    opener = urllib.request.build_opener(redir)
    request = urllib.request.Request(url, headers={"User-Agent": inv.UA, "Accept": "text/html,*/*"})
    try:
        with opener.open(request, timeout=25) as r:
            raw = r.read(5_000_001)
            return int(r.status), r.url, dict(r.headers), raw, redir.trace
    except urllib.error.HTTPError as e:
        return int(e.code), e.url, dict(e.headers), e.read(200_000), redir.trace
    except Exception as e:
        return 0, url, {}, repr(e).encode(), redir.trace


def main():
    p = OUT / "DANH-MUC-URL.csv"
    with p.open(encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)
        cols = reader.fieldnames
        rows = list(reader)
    existing = {r["original_url"] for r in rows}
    details = []
    for url, source in GSC:
        if url in existing:
            details.append({"original_url": url, "source": source, "already_in_inventory": "yes"})
            continue
        time.sleep(inv.DELAY)
        status, final, headers, raw, trace = fetch(url)
        ct = headers.get("Content-Type", "")
        parsed = inv.PageParser()
        if b"<html" in raw[:500].lower() or "text/html" in ct:
            parsed.feed(raw.decode("utf-8", "replace"))
        meta_robots = " | ".join(parsed.meta_robots)
        xrobots = headers.get("X-Robots-Tag", "")
        canonical = " | ".join(parsed.canonical)
        issue = []
        if status >= 400 or status == 0:
            issue.append("HTTP_ERROR")
        if status == 200 and any(x in (parsed.title + " ".join(parsed.h1)).lower() for x in ("page not found", "không tìm thấy")):
            issue.append("POSSIBLE_SOFT_404")
        if len(parsed.canonical) > 1:
            issue.append("MULTIPLE_CANONICAL")
        rows.append({
            "original_url": url, "final_url": final, "discovery_source": source,
            "sitemap": "no", "sitemap_lastmod": "", "http_status": str(status),
            "content_type": ct, "meta_robots": meta_robots, "x_robots_tag": xrobots,
            "noindex": "yes" if "noindex" in (meta_robots + xrobots).lower() else "no",
            "canonical": canonical, "title": parsed.title.strip(),
            "h1": " ".join(" ".join(parsed.h1).split()),
            "template": "URL lịch sử GSC", "body_class": parsed.body_classes,
            "fetch_state": "fetched" if status else "error", "render_state": "not-rendered",
            "html_bytes": str(len(raw)), "response_truncated": "no", "issue_id": " | ".join(issue),
        })
        details.append({"original_url": url, "source": source, "checked_utc": datetime.now(timezone.utc).isoformat(), "final_url": final, "status": status, "redirect_chain": " | ".join(trace), "title": parsed.title.strip(), "h1": " ".join(" ".join(parsed.h1).split()), "canonical": canonical, "meta_robots": meta_robots, "x_robots_tag": xrobots, "issue_id": " | ".join(issue)})
    inv.save_csv(p, sorted(rows, key=lambda x: x["original_url"]), cols)
    inv.save_csv(OUT / "gsc-historical-recheck.csv", details, ["original_url", "source", "checked_utc", "already_in_inventory", "final_url", "status", "redirect_chain", "title", "h1", "canonical", "meta_robots", "x_robots_tag", "issue_id"])
    stats = json.loads((OUT / "inventory-stats.json").read_text(encoding="utf-8"))
    stats["gsc_historical_seed_count"] = len(GSC)
    stats["discovered_with_gsc"] = len(rows)
    stats["fetched_with_gsc"] = sum(r["fetch_state"] == "fetched" for r in rows)
    stats["http_status_with_gsc"] = dict(Counter(r["http_status"] for r in rows if r["fetch_state"] == "fetched"))
    (OUT / "inventory-stats-final.json").write_text(json.dumps(stats, ensure_ascii=False, indent=2), encoding="utf-8")
    # Console on Windows may be cp1252 even while report files are UTF-8.
    print(json.dumps(details, ensure_ascii=True, indent=2))


if __name__ == "__main__":
    main()
