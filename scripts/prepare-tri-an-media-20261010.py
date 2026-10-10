"""Transcode the four supplied posters to metadata-free WebP for WordPress."""

import argparse
import hashlib
import json
from pathlib import Path

from PIL import Image


POSTERS = [
    (
        "overview",
        "codex-clipboard-e1954781-8b22-4cb8-bf3e-1ff18404dcf5.png",
        "ba-chuong-trinh-tri-an-benh-vien-mat-ha-noi-bac-ninh-2026.webp",
        "Ấn phẩm Bệnh viện Mắt Hà Nội – Bắc Ninh giới thiệu ba chương trình tri ân từ 12/10 đến 20/11/2026",
        "Ấn phẩm tổng quan ba chương trình tri ân; hình tòa nhà trong thiết kế mang tính minh họa.",
    ),
    (
        "exam",
        "codex-clipboard-25610b5b-dc25-486e-ac77-828d8d396672.png",
        "chuong-trinh-kham-mat-mien-phi-2026.webp",
        "Ấn phẩm chương trình khám mắt miễn phí từ 12/10 đến 20/11/2026 với hình minh họa khám bằng đèn khe",
        "Ấn phẩm giới thiệu chương trình khám mắt miễn phí; cảnh khám trong thiết kế là hình minh họa.",
    ),
    (
        "surgery",
        "codex-clipboard-39b3c53e-fa76-458b-af9d-af690a9aa9e8.png",
        "phau-thuat-mien-phi-nguoi-benh-uu-tien-2026.webp",
        "Ấn phẩm chương trình phẫu thuật miễn phí cho người bệnh thuộc nhóm ưu tiên từ 12/10 đến 20/11/2026",
        "Ấn phẩm giới thiệu chương trình cho người bệnh thuộc nhóm ưu tiên; hình mắt và dụng cụ là minh họa.",
    ),
    (
        "glasses",
        "codex-clipboard-b1de6866-33f1-4790-959d-3d0b3a31d217.png",
        "mua-mat-kinh-tang-gong-kinh-2026.webp",
        "Ấn phẩm chương trình mua mắt kính tặng gọng kính từ 12/10 đến 20/11/2026 với ảnh minh họa gọng kính",
        "Ấn phẩm giới thiệu chương trình mua mắt kính, tặng gọng theo điều kiện áp dụng.",
    ),
]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--source-dir", type=Path, required=True)
    parser.add_argument("--output-dir", type=Path, required=True)
    args = parser.parse_args()
    image_dir = args.output_dir / "images"
    image_dir.mkdir(parents=True, exist_ok=True)
    assets = []
    for key, source_name, filename, alt, caption in POSTERS:
        source = args.source_dir / source_name
        target = image_dir / filename
        with Image.open(source) as original:
            rgb = original.convert("RGB")
            if max(rgb.size) > 1600:
                rgb.thumbnail((1600, 1600), Image.Resampling.LANCZOS)
            rgb.save(target, "WEBP", quality=90, method=6)
            width, height = rgb.size
        assets.append(
            {
                "key": key,
                "filename": filename,
                "sha256": hashlib.sha256(target.read_bytes()).hexdigest(),
                "width": width,
                "height": height,
                "bytes": target.stat().st_size,
                "alt": alt,
                "caption": caption,
            }
        )
    manifest = {"featured_key": "overview", "assets": assets}
    (args.output_dir / "media-manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(json.dumps([(x["filename"], x["bytes"]) for x in assets], ensure_ascii=False))


if __name__ == "__main__":
    main()
