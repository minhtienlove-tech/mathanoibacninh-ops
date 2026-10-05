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
            "## Bốn đoạn thân bài cần bác sĩ xử lý trước khi đăng",
            "",
            "Các đoạn dưới đây **không nằm trong 20 đoạn thay thế của manifest**. Rà soát sau khi lập phiếu phát hiện chúng có thể khiến người đọc hiểu khác với phần ‘Trả lời ngắn’ mới. Phiếu duyệt 20 đoạn không tự động duyệt những câu này.",
            "",
            "- **Bài 569 — mờ mắt buổi tối:** câu ‘Mức bình thường có bốn dấu’ có thể bị hiểu là đủ để tự xác nhận mắt bình thường. Bác sĩ cần xác định tiêu chí và sửa cách diễn đạt nếu cần; trang đang công khai không phải công cụ tự chẩn đoán.",
            "- **Bài 572 — màn che/mất vùng nhìn:** đoạn nói hình ảnh tự hết hoàn toàn là một ‘điểm phân biệt’ có thể gây yên tâm sai; triệu chứng thị giác do cơn thiếu máu thoáng qua cũng có thể tự hết và vẫn cần đánh giá khẩn. [NHS về TIA](https://www.nhs.uk/conditions/transient-ischaemic-attack-tia/symptoms/), [American Stroke Association](https://www.stroke.org/en/about-stroke/types-of-stroke/tia-transient-ischemic-attack).",
            "- **Bài 654 — glôcôm góc mở:** các câu ‘Nhãn áp cao kéo dài...’ và ‘nhãn áp tăng từ từ...’ viết như cơ chế chung cho mọi người bệnh, không nhất quán với phần mới nêu bệnh vẫn có thể xảy ra ở mức nhãn áp thông thường. [National Eye Institute](https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/glaucoma/glaucoma-and-eye-pressure).",
            "- **Bài 656 — màng trước võng mạc:** câu ‘Đa số người bệnh cải thiện thị lực sau vài tuần’ có thể tạo kỳ vọng quá sớm; tài liệu bệnh viện mắt Moorfields mô tả mắt hồi phục trong vài tuần nhưng thị lực có thể tiếp tục cải thiện trong nhiều tháng. [Moorfields Eye Hospital](https://www.moorfields.nhs.uk/eye-conditions/epiretinal-membrane/diagnosis-and-treatment).",
            "",
            "**Kết luận bốn điểm:** ☐ Bác sĩ đã đọc và chấp nhận nguyên văn ☐ Cần sửa trước khi đăng ☐ Chưa đánh giá",
            "",
            "**Nhận xét và đoạn sửa đã duyệt:** ____________________________________________________________",
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
