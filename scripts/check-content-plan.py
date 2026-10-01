#!/usr/bin/env python3
"""Static QA for the 20 internal content proposals; no WordPress writes."""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1] / "docs" / "content-plan-20"
FIELDS = ("id", "action", "category", "primary_keyword", "seo_title", "meta_description", "author", "medical_review", "status")


def parse(path: Path) -> tuple[dict[str, str], str]:
    value = path.read_text(encoding="utf-8")
    match = re.match(r"\A---\n(.*?)\n---\n(.*)\Z", value, re.S)
    if not match:
        raise ValueError(f"{path.name}: front matter missing")
    metadata = {}
    for line in match.group(1).splitlines():
        key, sep, item = line.partition(":")
        if sep:
            metadata[key.strip()] = item.strip().strip("'\"")
    return metadata, match.group(2)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--json", action="store_true")
    args = parser.parse_args()
    paths = sorted((ROOT / "drafts").glob("[0-9][0-9].md"))
    errors = []
    titles, urls = set(), set()
    rows = []
    for path in paths:
        meta, body = parse(path)
        for key in FIELDS:
            if not meta.get(key):
                errors.append(f"{path.name}: missing {key}")
        if meta.get("id") != path.stem:
            errors.append(f"{path.name}: wrong ID")
        title = meta.get("seo_title", "")
        if title in titles:
            errors.append(f"{path.name}: duplicate SEO title")
        titles.add(title)
        url = meta.get("url") or meta.get("proposed_url", "")
        if url in urls:
            errors.append(f"{path.name}: duplicate URL")
        urls.add(url)
        if urlparse(url).netloc != "mathanoibacninh.com":
            errors.append(f"{path.name}: unexpected domain")
        h1 = re.findall(r"^# (.+)$", body, re.M)
        h2 = re.findall(r"^## (.+)$", body, re.M)
        faq = re.findall(r"^### (.+\?)$", body, re.M)
        anchors = re.findall(r"\{#([^}]+)\}", body)
        links = re.findall(r"\]\((https?://[^)]+)\)", body)
        if len(h1) != 1 or len(h2) < 3 or len(faq) < 3:
            errors.append(f"{path.name}: weak heading/FAQ structure: H1={len(h1)}, H2={len(h2)}, FAQ={len(faq)}")
        if len(anchors) != len(set(anchors)):
            errors.append(f"{path.name}: duplicate anchor")
        for target in re.findall(r"\]\(#([^)]+)\)", body):
            if target not in anchors:
                errors.append(f"{path.name}: broken TOC anchor #{target}")
        if not links:
            errors.append(f"{path.name}: no citations or internal links")
        if len(meta.get("meta_description", "")) > 170:
            errors.append(f"{path.name}: meta description >170 characters")
        rows.append({"id": path.stem, "word_count": len(re.findall(r"\S+", body)), "faq": len(faq), "links": len(links), "status": meta.get("status")})
    if len(paths) != 20:
        errors.append(f"expected 20 files, got {len(paths)}")
    result = {"drafts": len(paths), "rows": rows, "errors": errors}
    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print(f"{len(paths)} drafts; {sum(r['word_count'] for r in rows)} total words; {len(errors)} errors")
        for row in rows:
            print(f"{row['id']}: {row['word_count']} words, {row['faq']} FAQ, {row['links']} links")
        for error in errors:
            print("ERROR:", error)
    return bool(errors)


if __name__ == "__main__":
    raise SystemExit(main())
