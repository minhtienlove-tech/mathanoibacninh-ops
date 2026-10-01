#!/usr/bin/env python3
"""Build reviewable inventory and contextual-link maps from WP export/drafts."""

from __future__ import annotations

import csv
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "docs" / "content-plan-20"


def intent(title: str, kind: str) -> str:
    s = title.casefold()
    if kind == "page":
        return "điều hướng/thông tin bệnh viện"
    if any(x in s for x in ("bao nhiêu tiền", "chi phí", "bảng giá")):
        return "chi phí"
    if any(x in s for x in ("ở đâu", "đặt lịch", "chuẩn bị", "quy trình", "cần mang")):
        return "chuẩn bị khám"
    if any(x in s for x in ("khi nào", "dấu hiệu", "nguy hiểm", "cấp cứu")):
        return "triệu chứng/khi cần khám"
    if any(x in s for x in ("điều trị", "phẫu thuật", "mổ ", "thuốc", "kiểm soát")):
        return "điều trị/theo dõi"
    return "giải thích bệnh và chăm sóc mắt"


def main() -> None:
    inventory = json.loads((ROOT / "inventory.json").read_text(encoding="utf-8-sig"))
    by_url = {row["url"].rstrip("/") + "/": row for row in inventory}
    with (ROOT / "inventory-review.csv").open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(("id", "title", "url", "type", "status", "categories", "search_intent_review", "main_keyword_review", "author_id", "author_display", "modified", "content_internal_links"))
        for item in inventory:
            writer.writerow((item["id"], item["title"], item["url"], item["type"], item["status"], "; ".join(item["categories"]), intent(item["title"], item["type"]), item["title"], item["author_id"], item["author_display"], item["modified"], "; ".join(item["internal_links"])))
    with (ROOT / "link-map.csv").open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(("source_id", "source_url", "target_url", "anchor_text", "location", "type", "target_status", "new_or_existing"))
        for path in sorted((ROOT / "drafts").glob("[0-9][0-9].md")):
            text = path.read_text(encoding="utf-8")
            header, body = text.split("\n---\n", 1)
            url_match = re.search(r"^(?:url|proposed_url):\s*(https?://\S+)", header, re.M)
            source = url_match.group(1) if url_match else ""
            for anchor, target in re.findall(r"\[([^]]+)\]\((https?://[^)]+)\)", body):
                if "mathanoibacninh.com" not in target:
                    continue
                item = by_url.get(target.rstrip("/") + "/")
                writer.writerow((path.stem, source, target, anchor, "draft body", "contextual" if "Nguồn" not in body[max(0, body.find(target)-100):body.find(target)] else "source", item["status"] if item else "not-in-public-inventory", "new proposal"))
    print(f"Exported {len(inventory)} inventory rows and contextual link map")


if __name__ == "__main__":
    main()
