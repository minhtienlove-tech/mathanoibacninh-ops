from pathlib import Path
from PIL import Image, ImageOps
import json, hashlib, re

base = Path('docs/drafts/dtnt-school-20261015')
stage = Path('C:/Users/htc/AppData/Local/Temp/dtnt-school-20261015-media')
stage.mkdir(exist_ok=True)
items = [
    ('DSCF1580.jpg', 'dtnt-bac-ninh-kham-mat-hoc-duong-toan-canh.webp', 'Toàn cảnh học sinh và đội ngũ kiểm tra tại khu vực khám mắt Trường DTNT Bắc Ninh.', 'Khu vực kiểm tra mắt trong chương trình tại Trường PTDTNT tỉnh Bắc Ninh.'),
    ('DSCF1563.jpg', 'dtnt-bac-ninh-huong-dan-thu-thi-luc.webp', 'Học sinh được hỗ trợ che một mắt để kiểm tra thị lực tại trường DTNT.', 'Hướng dẫn học sinh kiểm tra thị lực. Ảnh: Bệnh viện Mắt Hà Nội – Bắc Ninh.'),
    ('DSCF1267.jpg', 'dtnt-bac-ninh-nhan-vien-van-hanh-may-khuc-xa.webp', 'Nhân viên bệnh viện thao tác tại thiết bị kiểm tra mắt trong chương trình DTNT.', 'Nhân viên bệnh viện thao tác tại khu vực kiểm tra bằng máy.'),
    ('DSCF1343.jpg', 'dtnt-bac-ninh-thiet-bi-do-khuc-xa.webp', 'Nhân viên Bệnh viện Mắt Hà Nội – Bắc Ninh bên máy đo khúc xạ tại trường.', 'Thiết bị và nhân sự tại khu vực đo khúc xạ của chương trình.'),
    ('DSCF1638.jpg', 'dtnt-bac-ninh-mat-sang-hoc-duong.webp', 'Khu vực thư viện trường với bảng thông tin Mắt sáng học đường của bệnh viện.', 'Bảng thông tin chương trình tại trường. Nội dung trên bảng phản ánh chương trình tại thời điểm tổ chức.')
]
assets = []
for source, name, alt, caption in items:
    im = ImageOps.exif_transpose(Image.open(Path('F:/ảnh đi tuyến/DTNT/Done DTNT') / source)).convert('RGB')
    im.thumbnail((1600, 1600))
    target = stage / name
    im.save(target, 'WEBP', quality=86, method=6)
    assets.append(dict(source=source, filename=name, alt=alt, caption=caption, width=im.width, height=im.height, sha256=hashlib.sha256(target.read_bytes()).hexdigest(), bytes=target.stat().st_size))
(base / 'media-manifest.json').write_text(json.dumps(assets, ensure_ascii=False, indent=2), encoding='utf-8')
content = (base / 'content-template.html').read_text(encoding='utf-8')
print('Word count (whitespace):', len(re.sub('<[^>]+>', ' ', content).split()))
print('Images:', json.dumps([{'name':a['filename'],'width':a['width'],'height':a['height'],'bytes':a['bytes']} for a in assets], ensure_ascii=False))
