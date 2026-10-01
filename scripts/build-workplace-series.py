"""Build 15 sanitized HTML articles from the reviewed source copy."""
from __future__ import annotations

import html
import importlib.util
import json
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs" / "workplace-15" / "articles.py"
EDITORIAL_FILES = ["extensions.py", "more.py", "more2.py", "more3.py", "more4.py", "more5.py", "last.py"]
OUT = ROOT / "docs" / "workplace-15" / "package"
EXCLUDE = {"lam-ca-dem-va-moi-mat", "dau-mat-sau-su-co-noi-lam-viec"}

spec = importlib.util.spec_from_file_location("workplace_articles", SOURCE)
module = importlib.util.module_from_spec(spec)
assert spec and spec.loader
spec.loader.exec_module(module)
editorial_maps = []
for filename in EDITORIAL_FILES:
    editorial_spec = importlib.util.spec_from_file_location(filename.replace(".", "_"), SOURCE.parent / filename)
    editorial_module = importlib.util.module_from_spec(editorial_spec)
    assert editorial_spec and editorial_spec.loader
    editorial_spec.loader.exec_module(editorial_module)
    editorial_maps.append(editorial_module.EXTRA if filename == "extensions.py" else editorial_module.MORE)
items = [row for row in module.ARTICLES if row[1] not in EXCLUDE]
assert len(items) == 15
assert len({row[1] for row in items}) == 15
OUT.mkdir(parents=True, exist_ok=True)

manifest = []
for index, row in enumerate(items, 1):
    title, slug, summary, situation, practical, seek, question, answer, source, internal = row
    assert re.fullmatch(r"[a-z0-9-]+", slug)
    sections = [section for editorial_map in editorial_maps for section in editorial_map.get(slug, [])]
    plain_words = len(" ".join(row[:8]).split()) + sum(len(heading.split()) + len(copy.split()) for heading, copy in sections)
    assert 1450 <= plain_words <= 1650, f"{slug}: {plain_words} words (target: ~1500)"
    number = f"{index:02d}"
    body = "\n".join([
        f"<p class=\"eyecare-tra-loi-nhanh\"><strong>Trả lời nhanh:</strong> {html.escape(summary)}</p>",
        "<h2>Điều gì trong công việc có thể ảnh hưởng đến mắt?</h2>",
        f"<p>{html.escape(situation)}</p>",
        "<h2>Cách giảm nguy cơ trong ca làm việc</h2>",
        f"<p>{html.escape(practical)}</p>",
        *[part for heading, copy in sections for part in (f"<h2>{html.escape(heading)}</h2>", f"<p>{html.escape(copy)}</p>")],
        "<h2>Khi nào cần khám mắt?</h2>",
        f"<p>{html.escape(seek)}</p>",
        "<h2>Câu hỏi thường gặp</h2>",
        f"<h3>{html.escape(question)}</h3>",
        f"<p>{html.escape(answer)}</p>",
        f"<p>Đọc thêm: <a href=\"https://mathanoibacninh.com{html.escape(internal, quote=True)}\">bài liên quan trên website bệnh viện</a>.</p>",
        "<section class=\"eyecare-nguon-tham-khao\" aria-label=\"Nguồn tham khảo\"><h2>Nguồn tham khảo</h2>",
        f"<p><a href=\"{html.escape(source, quote=True)}\" rel=\"noopener noreferrer\">Nguồn chuyên môn chính</a> (truy cập 01/10/2026). Nội dung mang tính tham khảo, không thay thế khám và chỉ định trực tiếp.</p></section>",
    ])
    (OUT / f"{number}.html").write_text(body, encoding="utf-8")
    manifest.append({
        "number": number,
        "title": title,
        "slug": slug,
        "url": f"https://mathanoibacninh.com/kien-thuc/{slug}/",
        "description": summary[:155].rstrip(" ,.;") + ".",
        "excerpt": summary,
        "source": source,
        "internal": f"https://mathanoibacninh.com{internal}",
        "html": f"{number}.html",
        "word_count": plain_words,
    })

(OUT / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
(OUT / "review.html").write_text("<!doctype html><html lang=\"vi\"><meta charset=\"utf-8\"><meta name=\"robots\" content=\"noindex\"><title>Rà 15 bài mắt và công việc</title><style>body{font:17px/1.65 Arial;max-width:850px;margin:2rem auto;padding:0 1rem;color:#19363c}h1,h2{color:#075765}article{border-top:2px solid #06a1b9;padding:2rem 0}a{color:#075765}</style><h1>15 bài Mắt và môi trường làm việc — bản rà nội bộ</h1>" + "".join(f"<article><h2>{html.escape(item['title'])}</h2>{(OUT / item['html']).read_text(encoding='utf-8')}</article>" for item in manifest) + "</html>", encoding="utf-8")
print(f"Built {len(manifest)} distinct articles at {OUT}")
