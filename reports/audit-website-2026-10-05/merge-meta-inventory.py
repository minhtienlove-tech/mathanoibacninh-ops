#!/usr/bin/env python3
"""Join two completed read-only crawl checkpoints into the URL inventory.

This script does not request the website. It validates exact URL/status equality
before adding meta descriptions and summary counts to the audit artifacts.
"""

import csv
import json
from collections import Counter, defaultdict
from pathlib import Path


OUT = Path(__file__).resolve().parent
INVENTORY = OUT / "DANH-MUC-URL.csv"
META = OUT / "meta-descriptions-raw.csv"
STATS = OUT / "inventory-stats-final.json"


def read_csv(path):
    with path.open(encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        return reader.fieldnames, list(reader)


def write_csv(path, fields, rows):
    with path.open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields)
        writer.writeheader()
        writer.writerows(rows)


def duplicate_groups(rows, field):
    groups = defaultdict(list)
    for row in rows:
        value = " ".join(row[field].split()).casefold()
        if value:
            groups[value].append(row["original_url"])
    return [urls for urls in groups.values() if len(urls) > 1]


def main():
    fields, rows = read_csv(INVENTORY)
    _, descriptions = read_csv(META)
    assert len(rows) == 406, f"Unexpected inventory row count: {len(rows)}"
    assert len(descriptions) == 406, f"Unexpected metadata row count: {len(descriptions)}"
    by_url = {row["URL"]: row for row in descriptions}
    assert len(by_url) == len(descriptions), "Duplicate URLs in meta crawl"
    assert {row["original_url"] for row in rows} == set(by_url), "URL sets differ"
    for row in rows:
        meta = by_url[row["original_url"]]
        assert row["http_status"] == meta["HTTP"], row["original_url"]
        row["meta_description"] = meta["meta_description"].strip()
    if "meta_description" not in fields:
        fields.insert(fields.index("title") + 1, "meta_description")

    # Measure search-facing pages once at their final URL. Redirect sources and
    # deliberate noindex pages are excluded from the actionable metadata counts.
    search_pages = [
        row for row in rows
        if row["http_status"] == "200"
        and row["noindex"] == "no"
        and row["original_url"] == row["final_url"]
    ]
    assert len(search_pages) == 380, len(search_pages)
    title_duplicates = duplicate_groups(search_pages, "title")
    description_duplicates = duplicate_groups(search_pages, "meta_description")
    duplicate_titles = {url for group in title_duplicates for url in group}
    duplicate_descriptions = {url for group in description_duplicates for url in group}
    metadata_ids = {
        "META_DESCRIPTION_MISSING",
        "META_DESCRIPTION_DUPLICATE",
        "TITLE_DUPLICATE",
    }
    for row in rows:
        issues = [
            issue.strip() for issue in row["issue_id"].split("|")
            if issue.strip() and issue.strip() not in metadata_ids
        ]
        if row in search_pages:
            if not row["meta_description"]:
                issues.append("META_DESCRIPTION_MISSING")
            if row["original_url"] in duplicate_titles:
                issues.append("TITLE_DUPLICATE")
            if row["original_url"] in duplicate_descriptions:
                issues.append("META_DESCRIPTION_DUPLICATE")
        row["issue_id"] = " | ".join(issues)
    write_csv(INVENTORY, fields, rows)

    by_status = dict(Counter(row["http_status"] for row in rows))
    stats = json.loads(STATS.read_text(encoding="utf-8"))
    stats["metadata_merge"] = {
        "inventory_rows": len(rows),
        "meta_rows": len(descriptions),
        "matched_exact_urls": len(by_url),
        "http_status": by_status,
        "search_facing_final_200_noindex_false": len(search_pages),
        "missing_title": sum(not row["title"].strip() for row in search_pages),
        "missing_meta_description": sum(not row["meta_description"] for row in search_pages),
        "missing_h1": sum(not row["h1"].strip() for row in search_pages),
        "duplicate_title_groups": title_duplicates,
        "duplicate_meta_description_groups": description_duplicates,
        "duplicate_h1_groups": duplicate_groups(search_pages, "h1"),
    }
    issue_counts = Counter(
        issue.strip()
        for row in rows
        for issue in row["issue_id"].split("|")
        if issue.strip()
    )
    stats["final_qc"] = {
        "urls_discovered": len(rows),
        "urls_fetched": sum(row["fetch_state"] == "fetched" for row in rows),
        "final_http_status": by_status,
        "redirect_sources": sum(row["original_url"] != row["final_url"] for row in rows),
        "final_200_noindex": sum(
            row["http_status"] == "200" and row["noindex"] == "yes"
            for row in rows
        ),
        "indexable_final_200_missing_canonical": sum(
            not row["canonical"] for row in search_pages
        ),
        "issue_id_counts": dict(issue_counts),
    }
    STATS.write_text(json.dumps(stats, ensure_ascii=False, indent=2), encoding="utf-8")
    summary = stats["metadata_merge"].copy()
    for key in ("duplicate_title_groups", "duplicate_meta_description_groups", "duplicate_h1_groups"):
        summary[key] = {"groups": len(summary[key]), "urls": sum(map(len, summary[key]))}
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
