#!/usr/bin/env python3
"""Build the guarded medical-intro review manifest from a read-only WP snapshot.

The snapshot is deliberately outside Git. This script makes no WordPress changes.
"""

from __future__ import annotations

import hashlib
import json
import re
import sys
from pathlib import Path


CHANGES = {
    566: {
        "short": "Đau phía sau mắt tăng khi cử động có nhiều nguyên nhân, trong đó có bệnh ở mắt, hốc mắt hoặc dây thần kinh thị giác; không thể xác định nguyên nhân chỉ từ vị trí đau. Nếu đau mới xuất hiện hoặc kéo dài, hãy được khám mắt. Nếu kèm nhìn mờ, màu sắc nhạt đi, nhìn đôi, sưng quanh mắt, sốt hoặc đau dữ dội, cần được đánh giá khẩn.",
        "sources": ["https://www.nhs.uk/symptoms/eye-pain/", "https://www.gosh.nhs.uk/conditions-and-treatments/conditions-we-treat/optic-neuritis/"],
    },
    567: {
        "intro_old": "Đau quanh hốc mắt kéo dài nhiều ngày là lý do đi khám rất hay gặp, và phần lớn không xuất phát từ bản thân nhãn cầu mà từ những cấu trúc nằm sát nó.",
        "intro_new": "Đau quanh hốc mắt kéo dài nhiều ngày có thể liên quan đến mắt, hốc mắt, xoang hoặc nguyên nhân thần kinh; cần hỏi bệnh và khám để xác định.",
        "short": "Đau quanh hốc mắt kéo dài có nhiều nguyên nhân, từ mỏi mắt, khô mắt đến bệnh ở mắt, hốc mắt, xoang hoặc nguyên nhân thần kinh; không thể kết luận là lành tính chỉ dựa vào kiểu đau hay thời điểm trong ngày. Nếu đau tăng hoặc kèm nhìn mờ, nhìn đôi, sụp mi, đỏ mắt, sợ ánh sáng, sốt hay sưng quanh mắt, hãy được khám sớm. Đau dữ dội hoặc giảm thị lực đột ngột cần được đánh giá cấp cứu.",
        "sources": ["https://www.nhs.uk/symptoms/eye-pain/", "https://www.nhs.uk/symptoms/double-vision/"],
    },
    568: {
        "intro_old": "Nhóm mắt đỏ không kèm mờ này đa phần lành tính, nhưng có vài tình huống nhìn bên ngoài giống hệt mà cần được khám sớm.",
        "intro_new": "Mắt đỏ khi thị lực còn rõ có thể do nguyên nhân nhẹ, nhưng riêng điều đó không loại trừ bệnh cần khám.",
        "short": "Mắt đỏ nhưng còn nhìn rõ có thể gặp trong kích ứng, khô mắt, viêm kết mạc hoặc xuất huyết dưới kết mạc; không thể tự xác định nguyên nhân chỉ bằng việc còn nhìn rõ. Người đeo kính áp tròng nên tháo kính và liên hệ bác sĩ mắt khi mắt đỏ. Đau, sợ sáng, giảm thị lực, đỏ nhiều, chấn thương hoặc tiếp xúc hóa chất cần được đánh giá khẩn; đỏ kéo dài hoặc tái phát cũng nên được khám.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/healthy-vision/finding-eye-doctor", "https://www.nei.nih.gov/eye-health-information/healthy-vision/contact-lenses"],
    },
    569: {
        "short": "Nhìn kém khi trời tối hoặc chói, thấy quầng quanh đèn có thể liên quan đến tật khúc xạ, khô mắt, đục thủy tinh thể hoặc bệnh mắt khác; không thể xác định nguyên nhân chỉ từ thời điểm bị mờ. Hãy khám mắt nếu triệu chứng mới xuất hiện, tăng dần, ảnh hưởng việc lái xe hoặc rõ hơn ở một bên. Nếu kèm đau, đỏ mắt hay giảm thị lực đột ngột, cần được đánh giá khẩn.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts"],
    },
    571: {
        "intro_old": "Đây là một trong những triệu chứng đáng báo động nhất ở mắt — nó nói rằng vùng võng mạc phụ trách nhìn rõ nhất, được gọi là hoàng điểm, đang có vấn đề. Không giống như mờ mắt đơn thuần có thể do khúc xạ, nhìn méo luôn cần được khám chuyên khoa vì nó gợi ý tổn thương trực tiếp tại võng mạc.",
        "intro_new": "Nhìn méo mới xuất hiện có thể liên quan đến hoàng điểm hoặc phần khác của hệ thị giác. Triệu chứng này cần được khám mắt sớm để xác định nguyên nhân.",
        "short": "Đường thẳng nhìn thành cong là dấu hiệu cần khám mắt sớm, nhất là khi mới xuất hiện ở một bên. Các nguyên nhân có thể gồm thoái hóa hoàng điểm tuổi già, phù hoàng điểm hoặc màng trước võng mạc; không thể xác định bệnh chỉ qua triệu chứng. Nếu kèm giảm thị lực đột ngột hoặc mất một vùng nhìn, hãy đến cơ sở cấp cứu ngay.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/age-related-macular-degeneration", "https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/macular-edema", "https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/macular-pucker"],
    },
    572: {
        "short": "Một mảng tối hoặc cảm giác như màn che mới xuất hiện trong tầm nhìn là dấu hiệu cần được đánh giá cấp cứu ngay, dù chưa đau và dù vùng tối còn nhỏ. Bong võng mạc là một nguyên nhân cần loại trừ; bệnh mạch máu võng mạc hoặc đường dẫn truyền thị giác cũng có thể gây mất một vùng nhìn. Không tự xác định nguyên nhân hoặc chờ qua đêm. Nếu vùng tối đã tự hết, vẫn cần đánh giá cấp cứu vì có thể liên quan cơn thiếu máu thoáng qua.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/retinal-detachment", "https://www.stroke.org/en/about-stroke/types-of-stroke/tia-transient-ischemic-attack"],
    },
    575: {
        "intro_old": "Nhưng mờ một bên mang ý nghĩa khác: nó gợi ý có gì đó đang xảy ra ở chính mắt đó, và thời gian không phải lúc nào cũng ở bên bạn.",
        "intro_new": "Mờ mới xuất hiện ở một bên có thể liên quan đến mắt, dây thần kinh thị giác hoặc mạch máu; thời điểm khởi phát và các triệu chứng kèm theo quyết định mức độ khẩn.",
        "short": "Mờ một bên tồn tại ổn định lâu ngày có thể do tật khúc xạ hoặc bệnh mắt tiến triển chậm, nhưng mờ mới xuất hiện, đặc biệt đột ngột, có thể là cấp cứu ở mắt hoặc dấu hiệu thiếu máu não. Nếu giảm thị lực đột ngột, mất một vùng nhìn, nhìn đôi hoặc có yếu liệt, nói khó, hãy đến cấp cứu ngay. Ngay cả khi thị lực tự trở lại sau vài phút, vẫn cần được đánh giá cấp cứu.",
        "sources": ["https://www.stroke.org/en/about-stroke/types-of-stroke/tia-transient-ischemic-attack", "https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/retinal-detachment"],
    },
    621: {
        "intro_old": "Đây là bộ triệu chứng rất hay gặp, phần lớn lành tính và tự khỏi, nhưng cách xử lý trong vài ngày đầu quyết định việc bệnh gọn lại hay lan ra cả nhà.",
        "intro_new": "Đây có thể là viêm kết mạc, nhưng màu ghèn và mắt đỏ không đủ để xác định nguyên nhân hay loại trừ bệnh cần điều trị.",
        "short": "Ghèn đặc làm dính mi có thể gặp trong viêm kết mạc do vi khuẩn, nhưng chỉ màu ghèn không đủ để tự chẩn đoán hoặc quyết định dùng kháng sinh. Lau ghèn bằng vật sạch, rửa tay và không dùng chung khăn; người đeo kính áp tròng cần tháo kính và liên hệ bác sĩ mắt khi mắt đỏ hoặc tiết dịch. Hãy khám nếu ghèn nhiều, triệu chứng nặng lên hoặc không cải thiện, đau mắt, sợ sáng hay nhìn mờ. Trẻ sơ sinh có mắt đỏ hoặc ghèn cần được bác sĩ đánh giá ngay.",
        "sources": ["https://www.cdc.gov/conjunctivitis/signs-symptoms/index.html", "https://www.cdc.gov/conjunctivitis/treatment/index.html"],
    },
    628: {
        "intro_old": "Kiểu diễn biến lên xuống theo giờ và theo ngày này không phải chuyện mỏi mắt thông thường — nó là một chỉ dấu khá đặc hiệu, và nhận ra được thì hướng xử lý khác hẳn.",
        "intro_new": "Sụp mi dao động và nhìn đôi có thể gợi ý nhược cơ, nhưng cũng cần loại trừ các nguyên nhân khác, đặc biệt khi triệu chứng mới xuất hiện.",
        "short": "Sụp mi tăng khi mệt và đỡ sau nghỉ, đôi khi kèm nhìn đôi, có thể gợi ý nhược cơ nhưng không đủ để tự chẩn đoán. Sụp mi hoặc nhìn đôi mới xuất hiện cần được khám sớm; nếu khởi phát đột ngột, kèm đau đầu dữ dội, đồng tử hai bên khác nhau, yếu liệt hoặc nói khó, hãy đến cấp cứu. Khó nuốt hoặc khó thở là dấu hiệu cần cấp cứu ngay.",
        "sources": ["https://www.nhs.uk/conditions/myasthenia-gravis/", "https://www.ninds.nih.gov/sites/default/files/2025-05/myasthenia-gravis.pdf", "https://www.nhs.uk/symptoms/double-vision/", "https://www.stroke.org/en/about-stroke/stroke-symptoms"],
    },
    654: {
        "short": "Glôcôm góc mở gây tổn thương tiến triển ở dây thần kinh thị giác và thường ảnh hưởng vùng nhìn ngoại vi trước, trong khi thị lực trung tâm còn tốt lâu. Nhãn áp cao là yếu tố nguy cơ quan trọng nhưng bệnh vẫn có thể xảy ra khi kết quả đo nhãn áp nằm trong giới hạn thông thường. Vì bệnh thường không đau và ít biểu hiện sớm, cần khám mắt toàn diện; một lần đo nhãn áp riêng lẻ không đủ để loại trừ glôcôm.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/glaucoma/glaucoma-and-eye-pressure", "https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/glaucoma/types-glaucoma"],
    },
    656: {
        "intro_old": "Đây không phải mỏi mắt hay thay đổi độ cận. Đó là dấu hiệu sớm của màng trước võng mạc, một lớp xơ mỏng bám trên bề mặt võng mạc, đặc biệt ở vùng hoàng điểm — nơi chịu trách nhiệm cho thị lực trung tâm.",
        "intro_new": "Nhìn méo mới xuất hiện có thể do màng trước võng mạc hoặc bệnh khác ở hoàng điểm; cần khám để xác định nguyên nhân.",
        "short": "Màng trước võng mạc là lớp mô mỏng trên bề mặt võng mạc, có thể làm hình ảnh lượn sóng, nhìn méo hoặc giảm độ nét. Tuy nhiên, nhìn méo không đặc hiệu cho bệnh này; bác sĩ thường cần khám đáy mắt và có thể chụp OCT để phân biệt với bệnh hoàng điểm khác. Trường hợp nhẹ thường được theo dõi; khi ảnh hưởng sinh hoạt, bác sĩ có thể cân nhắc phẫu thuật. Mức cải thiện sau mổ khác nhau ở từng người.",
        "sources": ["https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/macular-pucker"],
    },
    661: {
        "intro_old": "Đây không phải vấn đề riêng của mắt — đó là dấu hiệu bệnh Basedow, hay cường giáp tự miễn, đang ảnh hưởng đến hốc mắt và cơ vận nhãn.",
        "intro_new": "Mắt lồi mới xuất hiện kèm khô cộm hoặc nhìn đôi có thể liên quan bệnh mắt do tuyến giáp, nhưng cũng có nguyên nhân khác ở hốc mắt; cần được khám để xác định.",
        "short": "Bệnh mắt do tuyến giáp là tình trạng viêm tự miễn ở mô hốc mắt, thường đi cùng bệnh Basedow nhưng cũng có thể xảy ra khi xét nghiệm chức năng tuyến giáp bình thường. Triệu chứng có thể gồm lồi mắt, khô cộm, mi co rút hoặc nhìn đôi; người bệnh cần được bác sĩ mắt và bác sĩ nội tiết đánh giá. Nếu thị lực giảm, màu sắc nhìn nhạt đi, mất vùng nhìn, mắt không nhắm kín hoặc lồi mắt tăng nhanh kèm đau dữ dội, hãy được đánh giá khẩn.",
        "sources": ["https://www.thyroid.org/thyroid-eye-disease/", "https://www.nhs.uk/symptoms/bulging-eyes/"],
    },
}


def fail(message: str) -> None:
    raise SystemExit(message)


def main() -> None:
    if len(sys.argv) != 3:
        fail("Usage: build-medical-intro-manifest-20261003.py SNAPSHOT.json OUTPUT.json")
    snapshot = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    if len(snapshot) != len(CHANGES) or {row["id"] for row in snapshot} != set(CHANGES):
        fail("Snapshot must contain exactly the 12 audited posts")
    out = {"scope": "12 published medical article intros/short answers; review required before applying", "posts": []}
    for row in snapshot:
        id_ = row["id"]
        content = row["content"]
        if hashlib.sha256(content.encode()).hexdigest() != row["sha256"]:
            fail(f"Snapshot hash mismatch for {id_}")
        first = re.search(r"<p(?:\s[^>]*)?>.*?</p>", content, re.S | re.I)
        short = re.findall(r"<p><strong>Trả lời ngắn:</strong>.*?</p>", content, re.S)
        if not first or len(short) != 1:
            fail(f"Expected first paragraph and one short answer for {id_}")
        changes = CHANGES[id_]
        replacements = []
        if "intro_old" in changes:
            old_first = first.group(0)
            if old_first.count(changes["intro_old"]) != 1:
                fail(f"Intro text not unique in first paragraph: {id_}")
            new_first = old_first.replace(changes["intro_old"], changes["intro_new"])
            if content.count(old_first) != 1:
                fail(f"First paragraph not unique in article: {id_}")
            replacements.append({"section": "intro", "old": old_first, "new": new_first, "count": 1})
        old_short = short[0]
        new_short = f"<p><strong>Trả lời ngắn:</strong> {changes['short']}</p>"
        if content.count(old_short) != 1 or old_short == new_short:
            fail(f"Short answer not uniquely replaceable: {id_}")
        replacements.append({"section": "short_answer", "old": old_short, "new": new_short, "count": 1})
        new_content = content
        for replacement in replacements:
            if new_content.count(replacement["old"]) != 1:
                fail(f"Replacement collision: {id_} {replacement['section']}")
            new_content = new_content.replace(replacement["old"], replacement["new"], 1)
        for marker in ("[faq]", "[/faq]", "[tac-gia]"):
            if content.count(marker) != new_content.count(marker):
                fail(f"Shortcode count changed for {id_}: {marker}")
        out["posts"].append({
            "id": id_,
            "slug": row["slug"],
            "url": row["url"],
            "title": row["title"],
            "expected_sha256": row["sha256"],
            "expected_new_sha256": hashlib.sha256(new_content.encode()).hexdigest(),
            "snapshot_modified_gmt": row["modified_gmt"],
            "replacements": replacements,
            "source_urls": changes["sources"],
            "medical_review_required": True,
            "reviewer_name": "",
            "review_date": "",
        })
    path = Path(sys.argv[2])
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(out, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Prepared {len(out['posts'])} posts / {sum(len(p['replacements']) for p in out['posts'])} exact replacements: {path}")


if __name__ == "__main__":
    main()
