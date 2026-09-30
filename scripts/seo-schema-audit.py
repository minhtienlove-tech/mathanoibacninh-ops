#!/usr/bin/env python3
"""Read-only summary of public SEO headers and structured-data emitters.

Usage: python scripts/seo-schema-audit.py URL [URL ...] --out audit.json
The output is deliberately limited to public entity identifiers and fields
needed to compare schema before and after a deployment.
"""

import argparse
import hashlib
import json
import re
import urllib.error
import urllib.request
from html.parser import HTMLParser
from pathlib import Path


class PageParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.scripts = []
        self.links = []
        self.metas = []
        self.microdata = []
        self.rdfa = []
        self._script = None
        self._title = False
        self.title = ""

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "script" and attrs.get("type", "").lower() == "application/ld+json":
            self._script = {"attrs": attrs, "parts": []}
        elif tag == "title":
            self._title = True
        elif tag == "link":
            self.links.append(attrs)
        elif tag == "meta":
            self.metas.append(attrs)
        if "itemscope" in attrs or "itemtype" in attrs:
            self.microdata.append({k: attrs.get(k) for k in ("itemtype", "itemid", "itemprop") if k in attrs})
        if "typeof" in attrs:
            self.rdfa.append({k: attrs.get(k) for k in ("typeof", "resource", "about") if k in attrs})

    def handle_endtag(self, tag):
        if tag == "script" and self._script is not None:
            self.scripts.append(self._script)
            self._script = None
        elif tag == "title":
            self._title = False

    def handle_data(self, data):
        if self._script is not None:
            self._script["parts"].append(data)
        elif self._title:
            self.title += data


def graph_nodes(value, path="$", seen=None):
    if seen is None:
        seen = set()
    if isinstance(value, list):
        for index, entry in enumerate(value):
            yield from graph_nodes(entry, f"{path}[{index}]", seen)
    elif isinstance(value, dict):
        if "@type" in value and path not in seen:
            seen.add(path)
            yield path, value
        if "@graph" in value:
            yield from graph_nodes(value["@graph"], f"{path}.@graph", seen)


def summarize_node(path, node, script_index, attrs):
    fields = (
        "@id", "@type", "name", "url", "telephone", "legalName", "sameAs",
        "address", "aggregateRating", "review", "reviewedBy", "medicalSpecialty", "medicalAudience", "publisher", "provider", "worksFor",
        "isPartOf", "mainEntityOfPage", "logo", "geo", "openingHoursSpecification",
    )
    return {
        "script_index": script_index,
        "script_attrs": {key: attrs[key] for key in ("id", "class", "data-type") if key in attrs},
        "path": path,
        "fields": {key: node[key] for key in fields if key in node},
    }


def inspect(url):
    result = {"requested_url": url}
    request = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (compatible; CodexSEOAudit/1.0)"})
    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            body = response.read()
            headers = response.headers
            result.update({
                "final_url": response.geturl(),
                "status": response.status,
                "content_type": headers.get("Content-Type"),
                "x_robots_tag": headers.get_all("X-Robots-Tag", []),
                "link_header": headers.get_all("Link", []),
                "html_sha256": hashlib.sha256(body).hexdigest(),
            })
    except (urllib.error.URLError, TimeoutError) as error:
        result["error"] = str(error)
        return result

    parser = PageParser()
    parser.feed(body.decode("utf-8", errors="replace"))
    result["title"] = parser.title.strip()
    result["canonical_html"] = [
        link.get("href") for link in parser.links
        if "canonical" in (link.get("rel") or "").lower().split()
    ]
    result["robots_meta"] = [
        meta.get("content") for meta in parser.metas
        if meta.get("name", "").lower() in ("robots", "googlebot", "oai-searchbot")
    ]
    result["microdata"] = parser.microdata
    result["rdfa"] = parser.rdfa
    result["jsonld_script_count"] = len(parser.scripts)
    result["jsonld_nodes"] = []
    result["jsonld_parse_errors"] = []
    for script_index, script in enumerate(parser.scripts):
        raw = "".join(script["parts"]).strip()
        try:
            data = json.loads(raw)
        except json.JSONDecodeError as error:
            result["jsonld_parse_errors"].append({"script_index": script_index, "error": str(error)})
            continue
        for path, node in graph_nodes(data):
            result["jsonld_nodes"].append(summarize_node(path, node, script_index, script["attrs"]))
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("urls", nargs="+")
    parser.add_argument("--out", type=Path, required=True)
    args = parser.parse_args()
    data = {"pages": [inspect(url) for url in args.urls]}
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    for page in data["pages"]:
        nodes = page.get("jsonld_nodes", [])
        types = [str(node["fields"].get("@type")) for node in nodes]
        print(f"{page.get('status', 'ERR')} {page['requested_url']} -> {page.get('final_url', page.get('error'))} "
              f"canonical={page.get('canonical_html', [])} scripts={page.get('jsonld_script_count', 0)} "
              f"nodes={len(nodes)} AutoDealer={sum('AutoDealer' in kind for kind in types)}")


if __name__ == "__main__":
    main()
