from pathlib import Path
from PIL import Image, ImageOps
import json,hashlib,re
base=Path('docs/drafts/fpt-school-20261015')
# Keep media staging out of Git; only manifests and editorial sources belong in repository.
stage=Path('C:/Users/htc/AppData/Local/Temp/fpt-school-20261015-media')
stage.mkdir(exist_ok=True)
items=[
('IMG_5332.jpg','fpt-bac-giang-kham-mat-hoc-duong-toan-canh.webp','Nhân viên y tế bên bảng thị lực và học sinh tại khu vực khám mắt FPT Bắc Giang.','Toàn cảnh khu vực kiểm tra thị lực tại trường. Ảnh: Bệnh viện Mắt Hà Nội – Bắc Ninh.'),
('IMG_5353.jpg','fpt-bac-giang-kiem-tra-thi-luc-va-khuc-xa.webp','Học sinh được hỗ trợ che một mắt khi kiểm tra thị lực, cạnh khu vực kiểm tra bằng máy.','Khu vực kiểm tra thị lực và thiết bị tại FPT Bắc Giang.'),
('IMG_5348.jpg','fpt-bac-giang-hoc-sinh-tham-gia-kiem-tra.webp','Học sinh FPT Bắc Giang cầm phiếu tại khu vực tiếp nhận trong phòng học.','Học sinh mang theo phiếu tại khu vực kiểm tra.'),
('IMG_5340.jpg','fpt-bac-giang-doan-kham-benh-vien-mat.webp','Đội ngũ bệnh viện bên bàn khám mắt mang nhận diện FPT Schools.','Đội ngũ tham gia hoạt động bên bàn khám mắt tại trường.')]
assets=[]
for source,name,alt,caption in items:
    image=ImageOps.exif_transpose(Image.open(Path('F:/ảnh đi tuyến/FPT/done FPT')/source)).convert('RGB')
    image.thumbnail((1600,1600))
    target=stage/name
    image.save(target,'WEBP',quality=86,method=6)
    assets.append(dict(source=source,filename=name,alt=alt,caption=caption,width=image.width,height=image.height,sha256=hashlib.sha256(target.read_bytes()).hexdigest(),bytes=target.stat().st_size))
(base/'media-manifest.json').write_text(json.dumps(assets,ensure_ascii=False,indent=2),encoding='utf-8')
content=(base/'content-template.html').read_text(encoding='utf-8-sig')
(base/'content-template.html').write_text(content,encoding='utf-8',newline='\n')
text=re.sub('<[^>]+>',' ',content)
print('Whitespace word count:',len(text.split()))
print(json.dumps(assets,ensure_ascii=False,indent=2))
