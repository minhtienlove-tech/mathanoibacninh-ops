"""Read-only public smoke check for the 15 workplace-eye posts."""
from __future__ import annotations

import html
from html.parser import HTMLParser
import json
from pathlib import Path
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[1]
ITEMS = json.loads((ROOT / "docs/workplace-15/package/manifest.json").read_text(encoding="utf-8"))

class Check(HTMLParser):
    def __init__(self):
        super().__init__()
        self.h1 = 0
        self.descriptions = []
        self.canonicals = []
        self.headings = 0
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "h1": self.h1 += 1
        if tag == "h2": self.headings += 1
        if tag == "meta" and attrs.get("name") == "description": self.descriptions.append(attrs.get("content", ""))
        if tag == "link" and attrs.get("rel") == "canonical": self.canonicals.append(attrs.get("href", ""))

for item in ITEMS:
    req = Request(item["url"], headers={"User-Agent": "Mozilla/5.0 (site QA)"})
    with urlopen(req, timeout=15) as res:
        body = res.read().decode("utf-8", errors="replace")
        assert res.status == 200, (item["slug"], res.status)
    p = Check(); p.feed(body)
    assert p.h1 == 1, (item["slug"], "h1", p.h1)
    assert p.headings >= 10, (item["slug"], "h2", p.headings)
    assert len(p.descriptions) == 1 and len(p.descriptions[0]) >= 60, (item["slug"], "description", p.descriptions)
    assert len(p.canonicals) == 1 and p.canonicals[0] == item["url"], (item["slug"], "canonical", p.canonicals)
    assert "eyecare-nguon-tham-khao" in body, (item["slug"], "source")
    assert "pending-author-and-medical-review" not in body, (item["slug"], "internal status leaked")
    print(item["number"], "HTTP 200", "H1=1", f"H2={p.headings}", "description=1", "canonical=1")

with urlopen(Request("https://mathanoibacninh.com/kien-thuc/", headers={"User-Agent": "Mozilla/5.0 (site QA)"}), timeout=15) as res:
    index = html.unescape(res.read().decode("utf-8", errors="replace"))
for item in ITEMS:
    assert item["url"] in index, (item["slug"], "not linked from index")
print("PASS: all 15 articles linked from /kien-thuc/")
