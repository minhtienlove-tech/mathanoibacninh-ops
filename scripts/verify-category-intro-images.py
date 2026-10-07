"""Verify each published category introduction uses its distinct WebP image."""

from __future__ import annotations

import csv
import json
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[1]
DRAFTS = ROOT / "docs" / "drafts" / "category-intros"
SITE = "https://mathanoibacninh.com"


class Images(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.tags: list[dict[str, str]] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        if tag == "img":
            self.tags.append({key: value or "" for key, value in attrs})


def fetch(url: str) -> tuple[bytes, str]:
    request = Request(url, headers={"User-Agent": "Category-image-verification/1.0"})
    with urlopen(request, timeout=30) as response:
        if response.status != 200:
            raise ValueError(f"HTTP {response.status}: {url}")
        if response.geturl() != url:
            raise ValueError(f"Unexpected redirect: {url} -> {response.geturl()}")
        return response.read(), response.headers.get_content_type()


def main() -> None:
    entries = json.loads((DRAFTS / "manifest.json").read_text(encoding="utf-8"))["entries"]
    images = {
        image["post_id"]: image
        for image in json.loads((DRAFTS / "images" / "manifest.json").read_text(encoding="utf-8"))["images"]
    }
    with (DRAFTS / "images" / "imported.tsv").open(encoding="utf-8", newline="") as imported_file:
        imported = {int(row["post_id"]): row for row in csv.DictReader(imported_file, delimiter="\t")}
    if not (len(entries) == len(images) == len(imported) == 12):
        raise ValueError("Count mismatch")
    seen_image_urls: set[str] = set()
    for entry in entries:
        image = next(item for item in images.values() if item["category_slug"] == entry["category_slug"])
        post_id = image["post_id"]
        deployed = imported[post_id]
        if deployed["file"] != image["file"]:
            raise ValueError(f"Wrong file mapping: {post_id}")
        page_url = f"{SITE}/{entry['group']}/{entry['category_slug']}/{entry['post_slug']}/"
        html, mime = fetch(page_url)
        if mime != "text/html":
            raise ValueError(f"Not an HTML page: {page_url}")
        parser = Images()
        parser.feed(html.decode("utf-8", errors="replace"))
        expected_stem = image["file"].removesuffix(".webp")
        matches = [tag for tag in parser.tags if expected_stem in tag.get("src", "") or expected_stem in tag.get("srcset", "")]
        if not matches:
            raise ValueError(f"Featured image missing from HTML: {page_url}")
        # The current single-post template uses the article title as image alt.
        # The more descriptive image alt remains stored on the attachment.
        if not any(tag.get("alt") for tag in matches):
            raise ValueError(f"Alt text missing: {page_url}")
        image_url = deployed["url"]
        if image_url in seen_image_urls:
            raise ValueError(f"Duplicate image URL: {image_url}")
        seen_image_urls.add(image_url)
        _, image_mime = fetch(image_url)
        if image_mime != "image/webp":
            raise ValueError(f"Wrong image MIME: {image_url}: {image_mime}")
        print(f"OK {post_id} {page_url} -> {image_url}")
    print(f"Verified {len(entries)} distinct public featured images")


if __name__ == "__main__":
    main()
