"""Validate and build the 26 editorial drafts into a WordPress HTML package."""
from __future__ import annotations

import html
import json
from pathlib import Path
import re
import unicodedata

import markdown

ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "docs" / "content-200-26"
OUT = BASE / "package"
SITE = "https://mathanoibacninh.com"
inventory = json.loads((ROOT / ".tmp" / "public-inventory-after-merge.json").read_text(encoding="utf-8"))
live_urls = {item["url"] for item in inventory if item["type"] == "post" and item["status"] == "publish"}
live_slugs = {url.rstrip("/").split("/")[-1] for url in live_urls}
items = json.loads((BASE / "metadata.json").read_text(encoding="utf-8"))
assert len(items) == 26
assert len({item["slug"] for item in items}) == 26
assert all(item["slug"] not in live_slugs for item in items)
assert all(item["image_from"] in live_slugs for item in items)
OUT.mkdir(exist_ok=True)
manifest = []
for n, item in enumerate(items, 1):
    number = f"{n:02d}"
    assert item["number"] == number
    assert re.fullmatch(r"[a-z0-9-]+", item["slug"])
    source = (BASE / "drafts" / f"{number}.md").read_text(encoding="utf-8")
    title_match = re.match(r"^# (.+)\n\n", source)
    assert title_match, number
    title = title_match.group(1)
    body = source[title_match.end():]
    words = len(re.findall(r"\S+", body))
    assert 1400 <= words <= 1950, (number, words)
    assert body.count("## ") >= 5 and body.count("### ") >= 3, number
    assert "**Nguồn chuyên môn:**" in body, number
    links = re.findall(r"https://mathanoibacninh.com/kien-thuc/[a-z0-9-]+/", body)
    assert len(set(links)) >= 2, number
    assert all(link in live_urls for link in links), (number, set(links) - live_urls)
    external = re.findall(r"https://[^)\s]+", body.split("**Nguồn chuyên môn:**", 1)[1])
    assert len(external) >= 2, number
    content, sources = body.rsplit("**Nguồn chuyên môn:**", 1)
    rendered = markdown.markdown(content, extensions=["sane_lists"])
    sources_html = markdown.markdown("**Nguồn chuyên môn:**" + sources)
    rendered += '\n<section class="eyecare-nguon-tham-khao" aria-label="Nguồn tham khảo"><h2>Nguồn tham khảo</h2>' + sources_html + "</section>"
    assert "<h1" not in rendered and "<script" not in rendered and "<iframe" not in rendered
    (OUT / f"{number}.html").write_text(rendered, encoding="utf-8")
    first_paragraph = re.search(r"^(.+?)\n\n", content, re.S).group(1)
    description = re.sub(r"\s+", " ", re.sub(r"\*\*|__", "", re.sub(r"\[(.*?)\]\(.*?\)", r"\1", first_paragraph))).strip()
    if len(description) > 155:
        description = description[:155].rsplit(" ", 1)[0].rstrip(" ,.;") + "."
    manifest.append({**item, "title": title, "description": description,
                     "excerpt": description, "url": f"{SITE}/kien-thuc/{item['slug']}/",
                     "html": f"{number}.html", "word_count": words,
                     "internal_links": sorted(set(links))})

(OUT / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
(OUT / "review.html").write_text(
    '<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="robots" content="noindex"><title>Rà 26 bài nhãn khoa</title>'
    '<style>body{font:17px/1.65 Arial;max-width:850px;margin:2rem auto;padding:0 1rem;color:#19363c}h1,h2{color:#075765}article{border-top:2px solid #06a1b9;padding:2rem 0}a{color:#075765}</style>'
    '<h1>26 bài nhãn khoa — bản rà nội bộ</h1>'
    + "".join(f"<article><h2>{html.escape(item['title'])}</h2>{(OUT / item['html']).read_text(encoding='utf-8')}</article>" for item in manifest)
    + "</html>", encoding="utf-8")
print(f"Validated and built {len(manifest)} articles ({min(i['word_count'] for i in manifest)}–{max(i['word_count'] for i in manifest)} words).")
