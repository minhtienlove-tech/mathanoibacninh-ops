"""Read-only checks of the local area copy and existing crawl inventories.

This script never requests the production site. The source files are only a
proxy for public content when the matching WordPress page body is empty.
"""

from __future__ import annotations

import csv
import html
import json
import re
import statistics
from collections import Counter, defaultdict
from html.parser import HTMLParser
from itertools import combinations
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
REPORT = Path(__file__).resolve().parent
AREA_DIR = ROOT / "theme/eyecare-child/content/khu-vuc/dia-ban"
AREA_DATA = ROOT / "theme/eyecare-child/content/khu-vuc/areas.json"


class TextOnly(HTMLParser):
    def __init__(self):
        super().__init__()
        self.bits: list[str] = []

    def handle_data(self, data: str) -> None:
        self.bits.append(data)


def plain(source: str) -> str:
    parser = TextOnly()
    parser.feed(source)
    return re.sub(r"\s+", " ", html.unescape(" ".join(parser.bits))).strip()


def tokens(source: str) -> list[str]:
    return re.findall(r"\w+", source.casefold(), flags=re.UNICODE)


def shingles(words: list[str], n: int = 5) -> set[tuple[str, ...]]:
    return set(zip(*(words[i:] for i in range(n))))


def read_csv(name: str) -> list[dict[str, str]]:
    with (REPORT / name).open(encoding="utf-8-sig", newline="") as stream:
        return list(csv.DictReader(stream))


def main() -> None:
    areas = json.loads(AREA_DATA.read_text(encoding="utf-8"))["don_vi"]
    slugs = {a["slug"] for a in areas}
    files = {p.stem: p for p in AREA_DIR.glob("*.html")}
    lengths: list[int] = []
    doc_shingles: dict[str, set[tuple[str, ...]]] = {}
    paragraphs: defaultdict[str, list[str]] = defaultdict(list)
    faq_count = 0
    sources_with_post_link = 0
    sources_with_nei_link = 0
    all_texts: dict[str, str] = {}

    for slug in sorted(slugs):
        source = files[slug].read_text(encoding="utf-8")
        body = plain(source)
        all_texts[slug] = body
        words = tokens(body)
        lengths.append(len(words))
        doc_shingles[slug] = shingles(words)
        for para in re.findall(r"<p\b[^>]*>(.*?)</p>", source, flags=re.I | re.S):
            normalized = plain(para).casefold()
            if len(tokens(normalized)) >= 12:
                paragraphs[normalized].append(slug)
        faq_count += int("[faq]" in source and "[/faq]" in source)
        sources_with_post_link += int(bool(re.search(r'href=["\']/kien-thuc/[^"\']+/', source)))
        sources_with_nei_link += int("www.nei.nih.gov" in source)

    similarities: list[tuple[float, str, str, int]] = []
    for left, right in combinations(sorted(slugs), 2):
        a, b = doc_shingles[left], doc_shingles[right]
        overlap = len(a & b)
        similarity = overlap / len(a | b) if a or b else 0.0
        similarities.append((similarity, left, right, overlap))
    similarities.sort(reverse=True)

    inventory = read_csv("DANH-MUC-URL.csv")
    page_rows = {
        row["final_url"]: row
        for row in inventory
        if row["http_status"] == "200"
        and re.search(r"/khu-vuc/kham-mat-bac-(?:giang|ninh)/(?:xa|phuong)-[^/]+/$", row["final_url"])
    }
    wp_posts = {
        row["url"]
        for row in read_csv("wp-dates-raw.csv")
        if row["post_type"] == "post"
    }
    edges = read_csv("internal-link-edges.csv")
    inbound_from_posts: defaultdict[str, set[str]] = defaultdict(set)
    outbound_to_posts: defaultdict[str, set[str]] = defaultdict(set)
    for edge in edges:
        source, target = edge["source"], edge["target"]
        if source in wp_posts and target in page_rows:
            inbound_from_posts[target].add(source)
        if source in page_rows and target in wp_posts:
            outbound_to_posts[source].add(target)

    print(json.dumps({
        "area_data_count": len(areas),
        "source_html_count": len(files),
        "data_without_source_file": sorted(slugs - files.keys()),
        "source_file_outside_data": sorted(files.keys() - slugs),
        "body_word_count_min": min(lengths),
        "body_word_count_median": statistics.median(lengths),
        "body_word_count_max": max(lengths),
        "all_bodies_distinct": len(set(all_texts.values())) == len(areas),
        "paragraphs_12_words_or_more_repeated_across_files": sum(
            len(set(file_names)) > 1 for file_names in paragraphs.values()
        ),
        "faq_file_count": faq_count,
        "source_files_with_article_link": sources_with_post_link,
        "source_files_with_nei_link": sources_with_nei_link,
        "five_word_jaccard_max_pair": similarities[0],
        "five_word_jaccard_pairs_at_least_0_20": sum(s[0] >= .2 for s in similarities),
        "five_word_jaccard_pair_count": len(similarities),
        "public_area_urls_in_inventory": len(page_rows),
        "public_area_urls_with_city_in_meta": sum(
            "thành phố Bắc Ninh" in row["meta_description"] for row in page_rows.values()
        ),
        "public_area_urls_with_post_inbound": len(inbound_from_posts),
        "public_area_post_inbound_edges": sum(map(len, inbound_from_posts.values())),
        "public_area_urls_with_post_outbound": len(outbound_to_posts),
        "public_area_post_outbound_edges": sum(map(len, outbound_to_posts.values())),
        "outbound_examples": sorted(
            ((len(targets), source) for source, targets in outbound_to_posts.items()),
            reverse=True,
        )[:3],
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
