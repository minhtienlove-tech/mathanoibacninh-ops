"""Build a public-safe copy of the reviewed content plan.

The review package remains unchanged. This removes editorial instructions and
unverified, site-specific placeholders; it never fills them with invented data.
"""

from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs/content-plan-20/package"
TARGET = ROOT / "docs/content-plan-20/publication"
TARGET.mkdir(exist_ok=True)

for number in range(1, 21):
    name = f"{number:02d}.html"
    html = (SOURCE / name).read_text(encoding="utf-8")
    html = re.sub(r"\s*\[CẦN XÁC MINH:[^\]]*\]", "", html, flags=re.I)
    html = re.sub(
        r"\s*<strong>(?:Cần xác minh(?: trước xuất bản)?|Chặn xuất bản):</strong>[^<]*(?=</p>)",
        "", html,
        flags=re.I,
    )
    # An unanswered facilities question should not appear as a public FAQ.
    if number == 2:
        html = re.sub(
            r"<h3>Có chỗ gửi xe hoặc lối vào riêng không\?</h3>\s*<p>[^<]*</p>",
            "", html,
        )
    html = re.sub(r"<p>\s*</p>\s*", "", html)
    html = html.replace("..", ".")
    html = html.replace("Bệnh viện xác nhận có phẫu thuật khúc xạ, nhưng.", "Bệnh viện xác nhận có phẫu thuật khúc xạ.")
    html = html.replace("Bệnh viện xác nhận có dịch vụ đo và cắt kính, nhưng.", "Bệnh viện xác nhận có dịch vụ đo và cắt kính.")
    html = html.replace("Bệnh viện xác nhận có phẫu thuật khúc xạ;.", "Bệnh viện xác nhận có phẫu thuật khúc xạ.")
    html = html.replace("để biết thông tin bệnh viện công bố;.", "để biết thông tin bệnh viện công bố.")
    if re.search(r"\.\.|, nhưng\.|;\.", html):
        raise SystemExit(f"Broken punctuation in {name}")
    if re.search(r"CẦN XÁC MINH|Chặn xuất bản|Cần xác minh", html, re.I):
        raise SystemExit(f"Internal note remains in {name}")
    if "<script" in html.lower() or "<h1" in html.lower():
        raise SystemExit(f"Unexpected document markup in {name}")
    (TARGET / name).write_text(html, encoding="utf-8")

print(f"Prepared 20 public HTML fragments in {TARGET}")
