#!/usr/bin/env python3
"""Convert reviewed Markdown proposals into a local HTML/WP import package.

This script never writes to WordPress. Install locally with `python -m pip install
Markdown` if the dependency is absent. The resulting package is intentionally
marked review-only and must not be published until factual/clinical review.
"""

from __future__ import annotations

import json
import re
from html import escape
from pathlib import Path

import markdown

ROOT = Path(__file__).resolve().parents[1] / "docs" / "content-plan-20"
OUTPUT = ROOT / "package"


def parse(path: Path) -> tuple[dict[str, str], str]:
    raw = path.read_text(encoding="utf-8")
    header, body = raw[4:].split("\n---\n", 1)
    meta = {}
    for line in header.splitlines():
        key, separator, value = line.partition(":")
        if separator:
            meta[key] = value.strip().strip("'\"")
    # Brief is editorial guidance, not patient-facing copy.
    body = re.sub(r"\A\s*\*\*Brief:\*\*.*?\n\n", "", body, count=1, flags=re.S)
    return meta, body


def main() -> None:
    OUTPUT.mkdir(parents=True, exist_ok=True)
    manifest = []
    for path in sorted((ROOT / "drafts").glob("[0-9][0-9].md")):
        meta, body = parse(path)
        h1_match = re.search(r"^# (.+)$", body, flags=re.M)
        if not h1_match:
            raise ValueError(f"Missing article title in {path.name}")
        rendered = markdown.markdown(body, extensions=["attr_list", "tables", "sane_lists"])
        if "{#" in rendered:
            raise ValueError(f"Unconverted anchor in {path.name}")
        # The child theme already renders the WordPress title as H1.
        wp_body = re.sub(r"<h1(?:\s[^>]*)?>.*?</h1>\s*", "", rendered, count=1, flags=re.S)
        # OBS already injects an accessible TOC on knowledge posts and the team page.
        # Keep the manual TOC in standalone previews, but avoid two TOCs in WP.
        uses_plugin_toc = meta["action"].startswith("new-wordpress") or "existing-post" in meta["action"] or meta["id"] == "06"
        if uses_plugin_toc:
            wp_body = re.sub(r"<p><strong>Mục lục:</strong>.*?</p>\s*", "", wp_body, count=1, flags=re.S)
        html_path = OUTPUT / f"{path.stem}.html"
        html_path.write_text(wp_body + "\n", encoding="utf-8")
        preview = f'''<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{escape(meta['seo_title'])} — bản nháp</title><style>body{{margin:0;background:#edf5f4;color:#19363c;font:17px/1.7 system-ui,sans-serif}}main{{max-width:850px;margin:1.5rem auto;padding:clamp(1rem,4vw,3rem);background:#fff;border-radius:1rem}}.notice{{padding:1rem;background:#fff1cb;border-left:5px solid #b66a00;font-weight:700}}h1,h2,h3{{color:#075765;line-height:1.3}}a{{color:#087e95;overflow-wrap:anywhere}}li{{margin-bottom:.35rem}}@media(max-width:900px){{main{{margin:0;border-radius:0}}}}</style></head><body><main><p><a href="review.html">← Danh sách 20 chủ đề</a></p><p class="notice">BẢN NHÁP NỘI BỘ — chưa xác minh toàn bộ dữ liệu, chưa duyệt y khoa, không xuất bản.</p>{rendered}</main></body></html>'''
        (OUTPUT / f"preview-{path.stem}.html").write_text(preview, encoding="utf-8")
        manifest.append({**meta, "wp_title": h1_match.group(1), "html": html_path.name, "toc_for_wordpress": "OBS plugin" if uses_plugin_toc else "manual; recheck on WP preview", "warning": "INTERNAL DRAFT: verify placeholders, clinical content, sources, authorship and service facts before publication"})
    (OUTPUT / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    items = "".join(f'<li><a href="preview-{item["id"]}.html">{item["id"]}. {escape(item["seo_title"])}</a> — {"cập nhật URL hiện có" if item["action"].startswith("update") else "bài mới"}</li>' for item in manifest)
    index = f'''<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Duyệt 20 bản thảo nội dung mắt</title><style>body{{font:17px/1.6 system-ui,sans-serif;color:#17383c;background:#edf5f4;margin:0}}main{{max-width:850px;margin:auto;padding:2rem;background:#fff}}h1{{color:#075765}}a{{color:#087e95}}li{{margin:.8rem 0}}.notice{{background:#fff1cb;padding:1rem;border-left:5px solid #b66a00}}</style></head><body><main><h1>20 nội dung đề xuất</h1><p class="notice">Bản nháp nội bộ để bệnh viện duyệt. Chưa cập nhật website công khai; không dùng như tư vấn y khoa cá nhân.</p><ol>{items}</ol></main></body></html>'''
    (OUTPUT / "review.html").write_text(index, encoding="utf-8")
    print(f"Built {len(manifest)} HTML proposals in {OUTPUT}")


if __name__ == "__main__":
    main()
