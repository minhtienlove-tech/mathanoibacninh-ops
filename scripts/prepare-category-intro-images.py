"""Prepare distinct, lightweight featured images for the 2026-10-07 category posts.

Source PNGs are generated editorial illustrations. This script only resizes and
encodes them for the web; it never changes the underlying scene.
"""

from __future__ import annotations

import hashlib
import json
from pathlib import Path

from PIL import Image, ImageOps


ROOT = Path(__file__).resolve().parents[1]
DRAFTS = ROOT / "docs" / "drafts" / "category-intros"
SOURCE = DRAFTS / "image-source"
OUTPUT = DRAFTS / "images"
POST_IDS = {
    "mi-mat-le-dao-hoc-mat": 1678,
    "trieu-chung-mat-thuong-gap": 1679,
    "mat-va-benh-toan-than": 1680,
    "hieu-ket-qua-kham-mat": 1681,
    "dung-thuoc-mat-an-toan": 1682,
    "cau-chuyen-nguoi-benh": 1683,
    "goc-bac-si": 1684,
    "doi-song-benh-vien": 1685,
    "hoat-dong-cong-dong": 1686,
    "thong-bao-benh-vien": 1687,
    "uu-dai-va-ho-tro-nguoi-benh": 1688,
    "tuyen-dung": 1689,
}
ALT = {
    "mi-mat-le-dao-hoc-mat": "Ảnh minh họa khám mi mắt và vùng lệ đạo",
    "trieu-chung-mat-thuong-gap": "Ảnh minh họa người bệnh trao đổi triệu chứng mắt với bác sĩ",
    "mat-va-benh-toan-than": "Ảnh minh họa tư vấn về mắt và sức khỏe toàn thân",
    "hieu-ket-qua-kham-mat": "Ảnh minh họa giải thích kết quả khám mắt",
    "dung-thuoc-mat-an-toan": "Ảnh minh họa cách nhỏ thuốc mắt không chạm đầu lọ vào mắt",
    "cau-chuyen-nguoi-benh": "Ảnh minh họa cuộc trao đổi giữa người bệnh và bác sĩ mắt",
    "goc-bac-si": "Ảnh minh họa bác sĩ khám mắt bằng đèn khe",
    "doi-song-benh-vien": "Ảnh minh họa nhân viên chuẩn bị phòng khám mắt",
    "hoat-dong-cong-dong": "Ảnh minh họa trao đổi chăm sóc mắt với người cao tuổi",
    "thong-bao-benh-vien": "Ảnh minh họa quầy tiếp nhận thông tin tại cơ sở nhãn khoa",
    "uu-dai-va-ho-tro-nguoi-benh": "Ảnh minh họa nhân viên giải thích thông tin hỗ trợ người bệnh",
    "tuyen-dung": "Ảnh minh họa nhóm nhân sự nhãn khoa trao đổi công việc",
}


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main() -> None:
    entries = json.loads((DRAFTS / "manifest.json").read_text(encoding="utf-8"))["entries"]
    assert len(entries) == len(POST_IDS) == len(ALT) == 12
    assert {entry["category_slug"] for entry in entries} == set(POST_IDS)
    OUTPUT.mkdir(parents=True, exist_ok=True)
    result = []
    for entry in entries:
        slug = entry["category_slug"]
        source = SOURCE / f"{slug}.png"
        target = OUTPUT / f"{slug}.webp"
        if not source.is_file():
            raise FileNotFoundError(source)
        with Image.open(source) as original:
            image = ImageOps.exif_transpose(original).convert("RGB")
            image.thumbnail((1200, 1200), Image.Resampling.LANCZOS)
            image.save(target, format="WEBP", quality=82, method=6, exact=True)
            width, height = image.size
        if target.stat().st_size > 250_000:
            raise ValueError(f"Image exceeds 250 kB: {target}")
        result.append(
            {
                "post_id": POST_IDS[slug],
                "post_slug": entry["post_slug"],
                "post_title": entry["post_title"],
                "category_slug": slug,
                "file": target.name,
                "alt": ALT[slug],
                "caption": "Ảnh minh họa tạo bằng AI; không phải bác sĩ, người bệnh hoặc hoạt động thực tế của bệnh viện.",
                "width": width,
                "height": height,
                "bytes": target.stat().st_size,
                "sha256": sha256(target),
                "source_sha256": sha256(source),
            }
        )
    manifest = {"version": 1, "images": result}
    (OUTPUT / "manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    with (OUTPUT / "deployment.tsv").open("w", encoding="utf-8", newline="\n") as deploy_file:
        deploy_file.write("".join(
            "\t".join(
                [
                    str(row["post_id"]),
                    row["post_slug"],
                    row["post_title"],
                    row["file"],
                    row["alt"],
                    row["caption"],
                    row["sha256"],
                ]
            ) + "\n"
            for row in result
        ))
    print(f"Prepared {len(result)} images, {sum(row['bytes'] for row in result):,} bytes total")
    for row in result:
        print(f"{row['post_id']} {row['file']} {row['bytes']:,} B")


if __name__ == "__main__":
    main()
