"""Encode the 15 generated workplace article photos for WordPress.

The source directory must contain exactly 15 generation outputs, ordered by
creation time to match docs/workplace-15/package/manifest.json.
"""

import json
import sys
from pathlib import Path

from PIL import Image, ImageOps


def main() -> None:
    source = Path(sys.argv[1])
    target = Path(sys.argv[2])
    items = json.loads(Path("docs/workplace-15/package/manifest.json").read_text(encoding="utf-8"))
    images = sorted(source.glob("*.png"), key=lambda p: p.stat().st_ctime)
    if len(images) != len(items) or len(items) != 15:
        raise SystemExit(f"Expected 15 sources and 15 articles; got {len(images)}, {len(items)}")
    target.mkdir(parents=True, exist_ok=True)
    inventory = []
    for article, image in zip(items, images):
        filename = f"{article['slug']}.webp"
        destination = target / filename
        with Image.open(image) as original:
            rendered = ImageOps.fit(original.convert("RGB"), (1200, 675), method=Image.Resampling.LANCZOS)
            rendered.save(destination, "WEBP", quality=78, method=6)
        inventory.append({
            "slug": article["slug"],
            "title": article["title"],
            "source": str(image),
            "file": filename,
            "bytes": destination.stat().st_size,
        })
    (target / "inventory.json").write_text(json.dumps(inventory, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Prepared {len(inventory)} images; total {sum(i['bytes'] for i in inventory):,} bytes")
    for item in inventory:
        print(f"{item['bytes']:>7} {item['file']}")


if __name__ == "__main__":
    main()
