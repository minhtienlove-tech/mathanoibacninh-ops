"""Render a clinician review sheet from the staged exact-replacement manifest.

This script only reads the manifest and writes Markdown. It never changes WordPress.
"""

from __future__ import annotations

import html
import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
REVIEW_DIR = ROOT / "docs" / "faq-medical-review-20261003"
MANIFEST = REVIEW_DIR / "intro-summary-manifest.json"
OUTPUT = REVIEW_DIR / "intro-summary-physician-review.md"


def plain_text(markup: str) -> str:
    """Present the patient-visible text, while the manifest retains exact HTML."""
    text = re.sub(r"<[^>]+>", "", markup)
    text = html.unescape(text)
    return re.sub(r"\s+", " ", text).strip()


def quoted(text: str) -> str:
    return "> " + text.replace("\n", "\n> ")


def main() -> None:
    data = json.loads(MANIFEST.read_text(encoding="utf-8"))
    posts = data["posts"]
    count = sum(len(post["replacements"]) for post in posts)
    assert len(posts) == 12 and count == 20

    lines = [
        "# Phiếu bác sĩ duyệt: đoạn mở đầu và ‘Trả lời ngắn’",
        "",
        "**Trạng thái: dự thảo, chưa áp dụng lên website.** Đây là 12 bài với 20 đoạn đề xuất thay thế. Mục ‘Trước’ là nội dung đang công khai tại thời điểm chụp bản gốc; mục ‘Sau’ là văn bản dự kiến. Bản [manifest](intro-summary-manifest.json) giữ nguyên HTML, mã SHA-256 và điều kiện thay thế chính xác. Không điền tên hoặc ngày duyệt thay bác sĩ.",
        "",
        "Bác sĩ cần đọc **toàn bài**, FAQ và đối chiếu hướng dẫn chuyên môn đang áp dụng trước khi ký. Nguồn bên dưới là tài liệu đối chiếu của dự thảo, không phải xác nhận chuyên môn của bệnh viện. Nếu cần sửa câu chữ, ghi vào ô nhận xét và yêu cầu cập nhật manifest trước khi xuất bản.",
        "",
        "| Bài | Số đoạn | Kết luận |",
        "|---|---:|---|",
    ]
    for post in posts:
        lines.append(
            f"| [{post['id']} — {post['title']}](#bai-{post['id']}) | "
            f"{len(post['replacements'])} | ☐ Duyệt ☐ Cần sửa ☐ Không duyệt |"
        )

    for post in posts:
        lines.extend(
            [
                "",
                f"<a id=\"bai-{post['id']}\"></a>",
                "",
                f"## Bài {post['id']}: {post['title']}",
                "",
                f"**Trang công khai:** [{post['url']}]({post['url']})",
                "",
                "**Nguồn đối chiếu:** "
                + "; ".join(f"[Nguồn {i}]({url})" for i, url in enumerate(post["source_urls"], 1)),
                "",
            ]
        )
        for index, replacement in enumerate(post["replacements"], 1):
            section = "Đoạn mở đầu" if replacement["section"] == "intro" else "Trả lời ngắn"
            lines.extend(
                [
                    f"### {index}. {section}",
                    "",
                    "**Trước — đang công khai theo bản chụp:**",
                    "",
                    quoted(plain_text(replacement["old"])),
                    "",
                    "**Sau — đề xuất:**",
                    "",
                    quoted(plain_text(replacement["new"])),
                    "",
                    "- [ ] Bác sĩ xác nhận thay đổi đoạn này; không mâu thuẫn với toàn bài và FAQ.",
                    "",
                ]
            )
        lines.extend(
            [
                "**Kết luận cho bài:** ☐ Duyệt toàn bộ đề xuất ☐ Cần sửa ☐ Không duyệt",
                "",
                "**Nhận xét / câu cần chỉnh:** ____________________________________________________________",
                "",
                "**Bác sĩ duyệt (họ tên, chuyên khoa):** ____________________  **Ngày duyệt:** __________",
                "",
            ]
        )
    lines.extend(
        [
            "---",
            "",
            "**Quyết định cho cả đợt:** ☐ Được xuất bản sau khi cập nhật góp ý ☐ Chưa được xuất bản",
            "",
            "**Người đối chiếu đủ 12 bài:** ____________________  **Ngày:** __________",
            "",
            "**Ghi chú triển khai:** Chỉ chạy công cụ áp dụng sau khi có xác nhận thực tế cho từng bài; kiểm tra lại tiền kiểm, backup cơ sở dữ liệu và các bản bài viết, rồi QA công khai sau khi xóa cache. Phiếu này không tự kích hoạt triển khai.",
            "",
        ]
    )
    OUTPUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {OUTPUT} ({len(posts)} posts, {count} replacements)")


if __name__ == "__main__":
    main()
