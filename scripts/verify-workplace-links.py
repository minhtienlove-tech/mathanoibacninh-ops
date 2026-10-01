"""Read-only public check for reciprocal article links."""
from __future__ import annotations

import html
import json
from pathlib import Path
import re
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parents[1]
items = json.loads((root / "docs/workplace-15/package/manifest.json").read_text(encoding="utf-8"))
by_number = {item["number"]: item for item in items}
companions = {
    "01": ("02", "03"), "02": ("01", "03"), "03": ("02", "13"),
    "04": ("05", "06"), "05": ("04", "12"), "06": ("05", "12"),
    "07": ("09", "14"), "08": ("06", "12"), "09": ("07", "14"),
    "10": ("15", "05"), "11": ("12", "07"), "12": ("05", "11"),
    "13": ("03", "12"), "14": ("07", "09"), "15": ("10", "03"),
}
old_groups = {}
for item in items:
    old_groups.setdefault(item["internal"], []).append(item)

def read(url):
    with urlopen(Request(url, headers={"User-Agent": "Mozilla/5.0 (site QA)"}), timeout=30) as response:
        assert response.status == 200, (url, response.status)
        return html.unescape(response.read().decode("utf-8", errors="replace"))

def block_links(page):
    blocks = re.findall(r'<nav\b[^>]*class=["\']eyecare-workplace-links["\'][^>]*>(.*?)</nav>', page, re.S | re.I)
    assert len(blocks) == 1, f"expected one link block, got {len(blocks)}"
    return re.findall(r'href=["\']([^"\']+)', blocks[0])

for item in items:
    page = read(item["url"])
    links = block_links(page)
    expected = {by_number[n]["url"] for n in companions[item["number"]]}
    assert set(links) == expected, (item["number"], links, expected)
    assert item["internal"] in page, (item["number"], "old-article link missing")
    print("new", item["number"], "2 same-series + 1 existing")
for url, targets in old_groups.items():
    links = block_links(read(url))
    expected = {target["url"] for target in targets}
    assert set(links) == expected, (url, links, expected)
    print("existing", len(targets), url)
print("PASS: 15 new and 11 existing posts have reciprocal links")
