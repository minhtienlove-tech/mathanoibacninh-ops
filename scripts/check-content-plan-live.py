"""Smoke-check the 20 published content-plan URLs."""
from html.parser import HTMLParser
import json
from pathlib import Path
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parents[1]
manifest = json.loads((root / "docs/content-plan-20/package/manifest.json").read_text(encoding="utf-8"))


class Checker(HTMLParser):
    def __init__(self):
        super().__init__()
        self.h1 = 0
        self.ids = set()
        self.hrefs = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "h1":
            self.h1 += 1
        if "id" in attrs:
            self.ids.add(attrs["id"])
        if tag == "a" and attrs.get("href", "").startswith("#"):
            self.hrefs.append(attrs["href"][1:])


failed = []
for item in manifest:
    url = item.get("url") or item.get("proposed_url")
    req = Request(url, headers={"User-Agent": "Mozilla/5.0 Codex content QA"})
    try:
        with urlopen(req, timeout=30) as response:
            html = response.read().decode("utf-8", errors="replace")
            status = response.status
    except Exception as error:
        failed.append(f"{item['id']} fetch: {error}")
        continue
    parser = Checker()
    parser.feed(html)
    missing = sorted(set(parser.hrefs) - parser.ids)
    checks = [
        (status == 200, "HTTP 200"),
        (parser.h1 == 1, f"one H1 (found {parser.h1})"),
        (not missing, f"TOC targets present (missing {missing[:4]})"),
        ("CẦN XÁC MINH" not in html and "Chặn xuất bản" not in html, "no internal notes"),
    ]
    if item["action"].startswith("new-"):
        checks.append(("eyecare-tra-cuu" in html, "article index"))
    else:
        checks.append(("eyecare-content-plan-addendum" in html, "addendum"))
    bad = [label for passed, label in checks if not passed]
    if bad:
        failed.append(f"{item['id']} {url}: {', '.join(bad)}")
    print(f"{item['id']} {status} h1={parser.h1} anchors={len(parser.hrefs)} {'FAIL ' + ', '.join(bad) if bad else 'OK'}")

if failed:
    raise SystemExit("\n".join(failed))
print("All 20 URLs passed the public HTML smoke check.")
