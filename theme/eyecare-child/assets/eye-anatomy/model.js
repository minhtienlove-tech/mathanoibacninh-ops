/* Procedural educational eye anatomy. Units are illustrative, not millimetres. */
function createEyeModel(T) {
  const eye = new T.Group();
  const appearance=createEyeAppearance(T);
  const parts = Object.create(null), pickables = [], modeObjects = [];
  const anchors = {
    cornea: [-2.12,.18,-.20], sclera: [.40,1.955,-.12], iris: [-1.62,.73,-.22],
    pupil: [-1.62,0,0], aqueous: [-1.84,.35,-.22], lens: [-1.17,.40,0],
    ciliary: [-1.31,1.21,-.12], vitreous: [.20,.10,-.05], choroid: [.46,1.85,0],
    retina: [1.12,1.40,-.05], macula: [1.70,.09,-.55], nerve: [2.52,-.60,-.85]
  };
  function part(id) {
    const g=new T.Group(); g.name=id; g.userData.id=id; eye.add(g); parts[id]=g; return g;
  }
  Object.keys(anchors).forEach(part);
  const mat=(color,extras={})=>{
    const {shininess=28,specular,...rest}=extras;
    return new T.MeshPhysicalMaterial({color,roughness:Math.max(.18,1-shininess/145),metalness:0,clearcoat:.18,side:T.DoubleSide,...rest});
  };
  function mesh(geo,material,id,mode='both',pick=true) {
    const m=new T.Mesh(geo,material); m.userData.id=id; m.userData.mode=mode;
    m.userData.originalOpacity=material.opacity; m.userData.originalColor=material.color.clone();
    parts[id].add(m); if(mode!=='both')modeObjects.push(m); if(pick)pickables.push(m); return m;
  }
  function parametric(fn,nu=76,nv=48) {
    const pos=[],uv=[],indices=[];
    for(let i=0;i<=nu;i++)for(let j=0;j<=nv;j++){pos.push(...fn(i/nu,j/nv));uv.push(i/nu,j/nv);}
    for(let i=0;i<nu;i++)for(let j=0;j<nv;j++){let a=i*(nv+1)+j,b=a+nv+1;indices.push(a,b,a+1,b,b+1,a+1);}
    const g=new T.BufferGeometry();g.setAttribute('position',new T.Float32BufferAttribute(pos,3));g.setAttribute('uv',new T.Float32BufferAttribute(uv,2));g.setIndex(indices);g.computeVertexNormals();return g;
  }
  function spherePatch(r,frontAngle,phiStart,phiLen,center=[0,0,0],scale=[1,1,1]) {
    const geometry=parametric((u,v)=>{const t=frontAngle+(Math.PI-frontAngle)*u,p=phiStart+phiLen*v;return [center[0]-r*Math.cos(t)*scale[0],center[1]+r*Math.sin(t)*Math.cos(p)*scale[1],center[2]+r*Math.sin(t)*Math.sin(p)*scale[2]];},96,72);
    const uv=geometry.getAttribute('uv');
    for(let i=0;i<uv.count;i++)uv.setY(i,(phiStart+phiLen*uv.getY(i))/(Math.PI*2));
    return geometry;
  }
  function cutRim(r0,r1,angle,sign=1) {
    return parametric((u,v)=>{const r=r0+(r1-r0)*v,t=angle+(Math.PI-angle)*u;return [-r*Math.cos(t),sign*r*Math.sin(t),.002];},90,2);
  }
  function tube(points,radius,color,id,mode='both',pick=true) {
    const curve=new T.CatmullRomCurve3(points.map(p=>new T.Vector3(...p)));
    return mesh(new T.TubeGeometry(curve,Math.max(18,points.length*7),radius,8,false),(id==='nerve'?appearance.tissue('nerve'):mat(color,{shininess:25})),id,mode,pick);
  }
  function shell(id,rOut,rIn,color,edge,frontAngle) {
    mesh(spherePatch(rOut,frontAngle,0,Math.PI*2),appearance.tissue(id),id,'whole');
    mesh(spherePatch(rOut,frontAngle,Math.PI,Math.PI),appearance.tissue(id),id,'cut');
    mesh(spherePatch(rIn,frontAngle,Math.PI,Math.PI),appearance.tissue(id),id,'cut');
    for(const sign of [-1,1])mesh(cutRim(rIn,rOut,frontAngle,sign),mat(edge,{shininess:5}),id,'cut');
    // Lip at the anterior opening.
    mesh(parametric((u,v)=>{const r=rIn+(rOut-rIn)*u,p=Math.PI+v*Math.PI;return [-r*Math.cos(frontAngle),r*Math.sin(frontAngle)*Math.cos(p),r*Math.sin(frontAngle)*Math.sin(p)];},3,80),mat(edge),id,'cut');
  }
  // Three concentric coats, with exaggerated thickness for patient education.
  shell('sclera',2.00,1.93,'#ede9df','#fbf9ee',.60);
  shell('choroid',1.923,1.846,'#943e47','#703c3a',.77);
  shell('retina',1.839,1.800,'#e69a77','#e2ad8c',.82);

  // Corneal dome joins the anterior scleral opening. It is clear, not iris tissue.
  function corneaGeo(cut){return parametric((u,v)=>{const t=u*Math.PI/2,p=(cut?Math.PI:0)+v*(cut?Math.PI:Math.PI*2);return [-1.65-.56*Math.cos(t),1.127*Math.sin(t)*Math.cos(p),1.127*Math.sin(t)*Math.sin(p)];},36,72);}
  mesh(corneaGeo(false),appearance.glass('cornea'),'cornea','whole');
  mesh(corneaGeo(true),appearance.glass('cornea'),'cornea','cut');
  for(const sign of [-1,1]){
    const pts=[];for(let i=0;i<=28;i++){let a=i/28*Math.PI/2;pts.push([-1.65-.56*Math.cos(a),sign*1.127*Math.sin(a),.004]);}
    tube(pts,.012,'#bed9de','cornea','cut');
  }
  // Anterior chamber: a faint clear volume between cornea and iris.
  function aqGeo(cut){return parametric((u,v)=>{const r=1.02*u,p=(cut?Math.PI:0)+v*(cut?Math.PI:Math.PI*2);return [-1.675-.39*Math.sqrt(Math.max(0,1-u*u)),r*Math.cos(p),r*Math.sin(p)];},30,52);}
  mesh(aqGeo(false),mat('#89d9ec',{opacity:.055,transparent:true,depthWrite:false}),'aqueous','whole',false);
  mesh(aqGeo(true),mat('#89d9ec',{opacity:.095,transparent:true,depthWrite:false}),'aqueous','cut',false);

  // Annular iris, with an actual open pupil at its centre.
  function irisGeo(cut){
    const geometry=parametric((u,v)=>{const p=(cut?Math.PI:0)+v*(cut?Math.PI:2*Math.PI),r=.32+.77*u;
      const relief=.007*Math.sin(p*153+u*12)*Math.sin(u*Math.PI);
      return [-1.625+.027*Math.sin(u*Math.PI)+relief,r*Math.cos(p),r*Math.sin(p)];},64,256);
    if(cut){const uv=geometry.getAttribute('uv');for(let i=0;i<uv.count;i++)uv.setY(i,.5+uv.getY(i)*.5);}
    return geometry;
  }
  for(const cut of [false,true]){
    const mode=cut?'cut':'whole';mesh(irisGeo(cut),appearance.tissue('iris'),'iris',mode);
    // The pupil's dark appearance in the intact view. No solid pupil in the section.
    if(!cut){const pg=new T.CircleGeometry(.321,64);pg.rotateY(Math.PI/2);pg.translate(-1.613,0,0);mesh(pg,new T.MeshBasicMaterial({color:'#061116',side:T.DoubleSide}),'pupil','whole');}
    const rim=[];const start=cut?Math.PI:0;for(let j=0;j<=100;j++){const a=start+j/100*(cut?Math.PI:Math.PI*2);rim.push([-1.628,.323*Math.cos(a),.323*Math.sin(a)]);}tube(rim,.016,'#382918','pupil',mode);
  }
  // Biconvex crystalline lens, behind iris, suspended by zonular fibres.
  for(const cut of [false,true]){
    mesh(spherePatch(1,0,cut?Math.PI:0,cut?Math.PI:Math.PI*2,[-1.17,0,0],[.31,.76,.76]),appearance.glass('lens'),'lens',cut?'cut':'whole');
  }
  const lensFace=parametric((u,v)=>{let a=v*Math.PI*2;return [-1.17+.31*u*Math.cos(a),.76*u*Math.sin(a),.004];},22,80);
  mesh(lensFace,mat('#eee5cc',{shininess:100,transparent:true,opacity:.24,depthWrite:false,clearcoat:1}),'lens','cut');
  for(const s of [.84,.65,.42]){
    const pts=[];for(let i=0;i<=72;i++){let a=i/72*Math.PI*2;pts.push([-1.17+.31*s*Math.cos(a),.76*s*Math.sin(a),.012]);}tube(pts,.002,'#c9c4b3','lens','cut',false);
  }

  function ciliaryGeo(cut){return parametric((u,v)=>{let a=(cut?Math.PI:0)+v*(cut?Math.PI:Math.PI*2),r=1.09+u*.30;return [-1.31+.11*Math.sin(u*Math.PI)+.027*Math.cos(56*a)*(1-u),r*Math.cos(a),r*Math.sin(a)];},12,168);}
  for(const cut of [false,true]){
    const mode=cut?'cut':'whole';mesh(ciliaryGeo(cut),mat('#8f4444',{shininess:38}),'ciliary',mode);
    const start=cut?28:0,end=56;
    for(let i=start;i<end;i++){
      const a=i/56*Math.PI*2,ca=Math.cos(a),sa=Math.sin(a);
      tube([[-1.27,1.14*ca,1.14*sa],[-1.20,.96*ca,.96*sa],[-1.17,.752*ca,.752*sa]],.006,'#e5dacc','ciliary',mode,false);
    }
  }
  // Vitreous is a clear gel, not a solid coloured organ.
  for(const cut of [false,true]){
    mesh(spherePatch(1.79,.985,cut?Math.PI:0,cut?Math.PI:Math.PI*2),mat('#87cadc',{transparent:true,opacity:.06,depthWrite:false,shininess:90}),'vitreous',cut?'cut':'whole',false);
  }
  // Posterior macula and separate optic disc, both on the inner retinal surface.
  function patchOnRetina(center,r,color,id){
    const c=new T.Vector3(...center).normalize().multiplyScalar(1.783),g=new T.CircleGeometry(r,48);
    const m=mesh(g,appearance.retinalSpot(color),id);m.position.copy(c);m.quaternion.setFromUnitVectors(new T.Vector3(0,0,1),c.clone().normalize().negate());return m;
  }
  patchOnRetina([1.70,.09,-.55],.22,'#ae703b','macula');
  const fovea=patchOnRetina([1.70,.09,-.55],.075,'#75472e','macula');fovea.position.multiplyScalar(.996);
  const disc=[1.65,-.38,-.59];
  patchOnRetina(disc,.14,'#f3dab0','nerve');
  const nervePts=[[1.67,-.38,-.61],[2.00,-.46,-.72],[2.45,-.57,-.85],[3.02,-.68,-.96]];
  tube(nervePts,.22,'#d6c5a1','nerve');
  tube(nervePts,.159,'#f1d390','nerve');
  const nerveEnd=new T.CircleGeometry(.22,40);const end=mesh(nerveEnd,mat('#eee0c2'),'nerve');end.position.set(...nervePts[3]);end.quaternion.setFromUnitVectors(new T.Vector3(0,0,1),new T.Vector3(.96,-.18,-.19).normalize());
  for(let i=0;i<16;i++){
    const a=i/16*Math.PI*2,r=.14*Math.sqrt((i%5+1)/5),g=new T.SphereGeometry(.014,8,6);g.translate(3.035,-.68+r*Math.cos(a),-.96+r*Math.sin(a));mesh(g,mat('#bf9d60'),'nerve','both',false);
  }
  // Schematic retinal vessels projected to the inner spherical retina.
  const norm=p=>new T.Vector3(...p).normalize().multiplyScalar(1.785).toArray();
  const vascular=[
    [disc,[1.5,-.22,-.85],[1.22,.3,-1.22],[.8,.8,-1.33],[.02,1.2,-1.30],[-.55,1.4,-.9]],
    [disc,[1.38,-.70,-.83],[.9,-1.12,-1.10],[.2,-1.45,-1.0],[-.70,-1.46,-.60]],
    [disc,[1.48,-.14,-.99],[1.03,-.03,-1.44],[.48,.03,-1.69],[-.70,.18,-1.60]],
    [[1.22,.3,-1.22],[.72,.35,-1.62],[.0,.55,-1.69],[-.55,.76,-1.53]],
    [[.8,.8,-1.33],[.55,1.28,-1.10],[.01,1.62,-.66]],
    [[.9,-1.12,-1.10],[.62,-.7,-1.52],[-.12,-.73,-1.61],[-.70,-.72,-1.50]],
    [[1.38,-.70,-.83],[1.3,-1.15,-.33],[.85,-1.52,-.23]],
    [[.48,.03,-1.69],[.22,-.3,-1.75],[-.65,-.38,-1.58]]
  ];
  // Tapered branching vessels follow the same retinal surface and optic disc.
  function vessel(points,radius,color,id,mode='both'){
    const curve=new T.CatmullRomCurve3(points.map(p=>new T.Vector3(...p))),segments=60;
    const frames=curve.computeFrenetFrames(segments,false),positions=[],indices=[];
    for(let i=0;i<=segments;i++){
      const c=curve.getPointAt(i/segments),r=radius*(1-.88*Math.pow(i/segments,.8));
      if(id==='sclera')c.normalize().multiplyScalar(2.009);
      if(id==='retina')c.normalize().multiplyScalar(1.779);
      for(let j=0;j<=6;j++){
        const a=j/6*Math.PI*2,p=c.clone().addScaledVector(frames.normals[i],Math.cos(a)*r).addScaledVector(frames.binormals[i],Math.sin(a)*r);
        positions.push(p.x,p.y,p.z);
        if(i<segments&&j<6){const k=i*7+j;indices.push(k,k+7,k+1,k+1,k+7,k+8);}
      }
    }
    const g=new T.BufferGeometry();g.setAttribute('position',new T.Float32BufferAttribute(positions,3));g.setIndex(indices);g.computeVertexNormals();
    mesh(g,mat(color,{shininess:18,clearcoat:0,envMapIntensity:.25}),id,mode,false);
  }
  vascular.forEach((p,i)=>{
    vessel(p.map(norm),i<3?.023:.013,i%2?'#953f3b':'#aa4537','retina');
    for(let j=1;j<p.length-1;j++){
      const q=p[j],next=p[j+1],sign=(j+i)%2?1:-1;
      const twig=[q,[(q[0]+next[0])*.5,q[1]+sign*.13,q[2]-.05],[next[0]-.10,next[1]+sign*.28,next[2]-.12]];
      vessel(twig.map(norm),.009,'#ad5044','retina');
    }
  });

  // Surface vessels: sparse, thin vessels in the anterior white coat.
  for(let n=0;n<22;n++){
    const a=n/22*Math.PI*2+.10*Math.sin(n*7),pts=[];
    const surface=(t,p)=>[-2.007*Math.cos(t),2.007*Math.sin(t)*Math.cos(p),2.007*Math.sin(t)*Math.sin(p)];
    for(let j=0;j<=10;j++){const t=1.36-j*.061,p=a+.035*Math.sin(j*.9+n);pts.push(surface(t,p));}
    vessel(pts,.008,n%2?'#c79490':'#ba8480','sclera','whole');
    if(pts.every(p=>p[2]<0))vessel(pts,.008,'#c79490','sclera','cut');
    for(let j=3;j<=6;j+=3){const t=1.36-j*.061,p=a+.035*Math.sin(j*.9+n),sign=n%2?1:-1;
      const branch=[surface(t,p),surface(t-.07,p+sign*.03),surface(t-.15,p+sign*.08),surface(t-.24,p+sign*.10)];
      vessel(branch,.0045,'#cda09b','sclera','whole');
      if(branch.every(p=>p[2]<0))vessel(branch,.0045,'#cda09b','sclera','cut');}
  }

  // Illustrative optical rays stop at retina; no light continues along optic nerve.
  const rays=new T.Group();rays.name='light-path';eye.add(rays);
  const lightTracks=[];
  for(const y of [-.43,0,.43]){
    const points=[[-3.00,y,-.05],[-2.17,y,-.05],[-1.63,y*.43,-.10],[-1.17,y*.37,-.13],[1.70,.09,-.55]];
    const geo=new T.BufferGeometry().setFromPoints(points.map(p=>new T.Vector3(...p)));
    const line=new T.Line(geo,new T.LineBasicMaterial({color:'#ffdf65',transparent:true,opacity:.92}));rays.add(line);
    lightTracks.push(points.map(p=>new T.Vector3(...p)));
  }
  const sparks=[];
  for(let i=0;i<6;i++){const dot=new T.Mesh(new T.SphereGeometry(.026,8,6),new T.MeshBasicMaterial({color:'#ffe894'}));rays.add(dot);sparks.push(dot);}
  rays.visible=false;
  let active='cornea',mode='whole';
  function setMode(next){mode=next;for(const m of modeObjects)m.visible=m.userData.mode===mode;}
  function highlight(id){
    active=id;
    for(const [key,g] of Object.entries(parts))g.traverse(obj=>{
      if(!obj.isMesh)return;const m=obj.material;
      if(m.emissive){m.emissive.set(key===id?'#22bad0':'#000000');m.emissiveIntensity=key===id&&key!=='cornea'?.045:0;}
    });
  }
  function animateLight(t){sparks.forEach((dot,i)=>{const pts=lightTracks[i%3],progress=((t*.22+i/6)%1)*(pts.length-1),seg=Math.floor(progress);dot.position.copy(pts[seg]).lerp(pts[Math.min(seg+1,pts.length-1)],progress-seg);});}
  setMode('whole');highlight(active);
  return {eye,parts,anchors,pickables,setMode,highlight,rays,animateLight,get mode(){return mode;}};
}
if(typeof module!=='undefined' && module.exports)module.exports=createEyeModel;

(() => {
  'use strict';
  const data = [
    {id:'cornea',name:'Giác mạc',en:'Cornea',color:'#90dce9',summary:'Lớp trong suốt, hình vòm ở phía trước mắt. Giác mạc bảo vệ mắt và bẻ cong ánh sáng để giúp mắt lấy nét.',location:'Phủ phía trước mống mắt và đồng tử.',fact:'Giống một ô cửa kính trong suốt: bạn nhìn thấy màu mống mắt qua giác mạc.'},
    {id:'sclera',name:'Củng mạc',en:'Sclera',color:'#d9d9ca',summary:'Lớp vỏ trắng, chắc bao quanh phần lớn nhãn cầu. Củng mạc bảo vệ và giúp giữ hình dạng của mắt.',location:'Lớp ngoài của thành nhãn cầu, nối với giác mạc ở phía trước.',fact:'Phần thường gọi là “lòng trắng” chủ yếu là củng mạc, được kết mạc phủ ở bề mặt phía trước.'},
    {id:'iris',name:'Mống mắt',en:'Iris',color:'#947743',summary:'Phần có màu của mắt. Các cơ trong mống mắt làm đồng tử nhỏ lại hoặc lớn lên, điều chỉnh lượng ánh sáng đi vào.',location:'Ở sau giác mạc, phía trước thủy tinh thể, bao quanh đồng tử.',fact:'Mống mắt là phần tạo nên màu nâu, xanh hoặc các màu mắt khác.'},
    {id:'pupil',name:'Đồng tử',en:'Pupil',color:'#193b47',summary:'Lỗ mở ở giữa mống mắt, cho ánh sáng đi vào trong mắt. Kích thước đồng tử thay đổi nhờ hoạt động của mống mắt.',location:'Chính giữa mống mắt.',fact:'Đồng tử là một lỗ mở, không phải một khối mô màu đen. Bình thường nó trông đen khi nhìn từ bên ngoài.'},
    {id:'aqueous',name:'Thủy dịch',en:'Aqueous humor',color:'#71c4de',summary:'Chất lỏng trong suốt nuôi dưỡng giác mạc và thủy tinh thể. Sự tạo thành và thoát lưu thủy dịch góp phần duy trì nhãn áp.',location:'Trong tiền phòng giữa giác mạc và mống mắt; cả khoang nhỏ sau mống mắt, trước thủy tinh thể.',fact:'Thủy dịch nằm bên trong mắt. Nước mắt phủ bề mặt bên ngoài là một chất dịch khác.'},
    {id:'lens',name:'Thủy tinh thể',en:'Crystalline lens',color:'#e7bc58',summary:'Thấu kính trong suốt, hai mặt lồi, cùng giác mạc hội tụ ánh sáng lên võng mạc. Thủy tinh thể thay đổi hình dạng khi mắt điều tiết.',location:'Ngay sau mống mắt và đồng tử, phía trước dịch kính.',fact:'Các sợi dây chằng treo nối thủy tinh thể với thể mi, giúp thay đổi hình dạng thấu kính khi lấy nét.'},
    {id:'ciliary',name:'Thể mi',en:'Ciliary body',color:'#ba7377',summary:'Vòng mô có cơ giúp điều chỉnh hình dạng thủy tinh thể khi nhìn gần hoặc xa. Các mỏm thể mi còn tạo ra thủy dịch.',location:'Ngay sau mống mắt, vòng quanh thủy tinh thể và liên tiếp với hắc mạc.',fact:'Thể mi tác động lên thủy tinh thể thông qua những sợi dây chằng treo rất mảnh.'},
    {id:'vitreous',name:'Dịch kính',en:'Vitreous',color:'#9ac8d8',summary:'Chất gel trong suốt chiếm phần lớn khoang trong mắt. Dịch kính cho ánh sáng truyền qua để đến võng mạc.',location:'Phía sau thủy tinh thể, trong khoang được võng mạc bao quanh.',fact:'Dịch kính có dạng gel; thủy dịch ở phía trước lỏng hơn. Đây là hai thành phần khác nhau.'},
    {id:'choroid',name:'Hắc mạc',en:'Choroid',color:'#a95260',summary:'Lớp mô giàu mạch máu, cung cấp oxy và chất dinh dưỡng cho phần ngoài võng mạc. Sắc tố trong lớp này giúp hấp thụ ánh sáng tán xạ.',location:'Nằm giữa củng mạc ở ngoài và võng mạc ở trong.',fact:'Trên mặt cắt, hắc mạc là dải màu đỏ sẫm nằm giữa lớp vỏ trắng và lớp võng mạc.'},
    {id:'retina',name:'Võng mạc',en:'Retina',color:'#eaa07d',summary:'Lớp mô nhạy với ánh sáng lót phía trong phần sau nhãn cầu. Các tế bào cảm thụ ánh sáng chuyển ánh sáng thành tín hiệu điện.',location:'Ở phía trong hắc mạc, tiếp giáp với dịch kính.',fact:'Có thể hình dung võng mạc như bộ phận cảm biến của máy ảnh. Não xử lý tín hiệu để bạn nhận biết hình ảnh.'},
    {id:'macula',name:'Hoàng điểm',en:'Macula',color:'#c58a3d',summary:'Vùng chuyên biệt của võng mạc giúp nhìn rõ ở trung tâm, nhận biết chi tiết và màu sắc; rất cần khi đọc hoặc nhận diện khuôn mặt.',location:'Gần trung tâm võng mạc phía sau mắt; tách biệt với đĩa thị, nơi dây thần kinh thị giác đi ra.',fact:'Hoàng điểm là một vùng của võng mạc, không phải một lớp riêng của thành nhãn cầu.'},
    {id:'nerve',name:'Dây thần kinh thị giác',en:'Optic nerve',color:'#d3ba85',summary:'Bó sợi thần kinh truyền tín hiệu từ võng mạc về não, góp phần tạo nên hình ảnh mà bạn nhìn thấy.',location:'Đi ra ở phía sau nhãn cầu, bắt đầu tại đĩa thị trên võng mạc.',fact:'Dây thần kinh thị giác truyền tín hiệu điện đến não. Ánh sáng không chạy dọc trong dây thần kinh này.'}
  ];
  const $=id=>document.getElementById(id);
  let selected=0,mode='whole',labelsOn=true,rotating=false,lightOn=false,model=null,renderer=null,camera=null,scene=null;
  let zoom=1,dirty=true,interacting=false,lastTime=0,canRender=false,animationId=0,hostVisible=true;
  window.addEventListener('eyecare-eye-visibility',event=>{
    hostVisible=event.detail.visible!==false;
    if(hostVisible)dirty=true;
  });
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const grid=$('parts-grid'),viewer=$('viewer'),canvas=$('eye-canvas');
  const buttons=data.map((d,i)=>{
    const b=document.createElement('button');b.className='part-button';b.dataset.id=d.id;
    b.setAttribute('aria-pressed',i===0?'true':'false');
    b.innerHTML='<span class="number">'+String(i+1).padStart(2,'0')+'</span><span class="dot" style="background:'+d.color+'" aria-hidden="true"></span><span class="name">'+d.name+'</span>';
    b.addEventListener('click',()=>selectPart(i));grid.append(b);return b;
  });
  function announce(text){$('announcement').textContent=text;}
  function stopRotation(){rotating=false;updateRotateButton();}
  function updateRotateButton(){
    const b=$('rotate-btn');b.setAttribute('aria-pressed',String(rotating));b.setAttribute('aria-label',rotating?'Dừng tự xoay':'Tự xoay mô hình');b.title=rotating?'Dừng tự xoay':'Tự xoay mô hình';
    b.innerHTML=rotating?'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg>':'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 5 11 7-11 7Z"/></svg>';
  }
  function selectPart(i,reset=true){
    selected=(i+data.length)%data.length;const d=data[selected];
    $('part-title').textContent=d.name;$('part-latin').textContent=d.en;$('part-summary').textContent=d.summary;$('part-location').textContent=d.location;$('part-fact').textContent=d.fact;$('part-color').style.background=d.color;$('counter').textContent=String(selected+1).padStart(2,'0')+' / 12';
    buttons.forEach((b,j)=>b.setAttribute('aria-pressed',String(j===selected)));
    $('prev-part').disabled=selected===0;$('next-part').disabled=selected===data.length-1;
    if(model){
      if(mode==='whole'&&!['cornea','sclera','iris','pupil'].includes(d.id))setMode('cut',false);
      if(reset){stopRotation();resetView(false);}
      model.highlight(d.id);dirty=true;
    }
    for(const l of labelObjects)l.el.classList.toggle('active',l.id===d.id);
    document.querySelectorAll('[data-label-id]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.labelId===d.id)));
    announce(d.name+'. '+d.summary);
  }
  function setMode(next,notify=true){
    mode=next;$('cut-btn').setAttribute('aria-pressed',String(mode==='cut'));$('whole-btn').setAttribute('aria-pressed',String(mode==='whole'));
    $('view-caption').textContent=mode==='cut'?'MẶT CẮT NHÃN CẦU':'NHÃN CẦU NGUYÊN VẸN';
    if(mode==='whole'&&lightOn)setLight(false);
    if(model){model.setMode(mode);stopRotation();resetView(false);}
    if(notify)announce(mode==='cut'?'Đã mở mặt cắt để xem các bộ phận bên trong.':'Đang xem bề mặt nguyên vẹn của nhãn cầu.');dirty=true;
  }
  function setLight(on){
    lightOn=on;if(on&&mode!=='cut')setMode('cut',false);
    $('light-btn').setAttribute('aria-pressed',String(on));$('light-explanation').hidden=!on;
    if(model){model.rays.visible=on;if(on){stopRotation();resetView(false);}dirty=true;}
  }
  function resetView(notify=true){
    if(!model)return;model.eye.rotation.set(0,0,0);zoom=1;setCamera();dirty=true;
    $('view-orientation').textContent='Phía trước ← → Phía sau';
    if(notify){stopRotation();announce('Đã đặt lại góc nhìn ban đầu.');}
  }
  function setCamera(){
    if(!camera)return;
    const w=viewer.clientWidth,h=viewer.clientHeight,aspect=w/h;
    const distance=(aspect<1?10.8:(aspect<1.35?9.8:8.9))/zoom;
    camera.aspect=aspect;camera.position.set(mode==='cut'?-5.4:-10,mode==='cut'?2.3:1.3,mode==='cut'?8.5:4.0).normalize().multiplyScalar(distance);
    camera.lookAt(.05,0,0);camera.updateProjectionMatrix();dirty=true;
    $('zoom-in').disabled=zoom>=1.64;$('zoom-out').disabled=zoom<=.70;
  }
  function changeZoom(factor){zoom=Math.max(.70,Math.min(1.65,zoom*factor));setCamera();}
  $('cut-btn').addEventListener('click',()=>setMode('cut'));$('whole-btn').addEventListener('click',()=>setMode('whole'));
  $('prev-part').addEventListener('click',()=>{if(selected>0)selectPart(selected-1);});$('next-part').addEventListener('click',()=>{if(selected<data.length-1)selectPart(selected+1);});
  $('labels-btn').addEventListener('click',()=>{labelsOn=!labelsOn;$('labels-btn').setAttribute('aria-pressed',String(labelsOn));$('model-labels').hidden=!labelsOn;$('mobile-model-labels').hidden=!labelsOn;dirty=true;});
  $('light-btn').addEventListener('click',()=>setLight(!lightOn));$('rotate-btn').addEventListener('click',()=>{if(!model)return;rotating=!rotating;updateRotateButton();dirty=true;});
  $('zoom-in').addEventListener('click',()=>changeZoom(1.15));$('zoom-out').addEventListener('click',()=>changeZoom(1/1.15));$('reset-btn').addEventListener('click',()=>resetView());$('retry-btn').addEventListener('click',()=>location.reload());
  const labelObjects=[];
  const labelPositions=[['cornea',4,27],['lens',5,64],['retina',78,24],['nerve',77,65]];
  const ns='http://www.w3.org/2000/svg';
  for(const [id,x,y] of labelPositions){
    const d=data.find(d=>d.id===id),el=document.createElement('button');el.className='callout';el.textContent=d.name;if(x>50)el.style.right='4%';else el.style.left=x+'%';el.style.top=y+'%';el.addEventListener('click',()=>selectPart(data.indexOf(d)));
    const line=document.createElementNS(ns,'line'),circle=document.createElementNS(ns,'circle');circle.setAttribute('r','3');$('leaders').append(line,circle);$('model-labels').append(el);labelObjects.push({id,el,line,circle});
    const mobileLabel=document.createElement('button');mobileLabel.type='button';mobileLabel.dataset.labelId=id;mobileLabel.textContent=d.name;
    mobileLabel.setAttribute('aria-pressed','false');mobileLabel.addEventListener('click',()=>selectPart(data.indexOf(d)));$('mobile-model-labels').append(mobileLabel);
  }
  selectPart(0,false);
  function fallback(){
    canRender=false;$('loader').hidden=true;$('fallback').hidden=false;$('model-labels').hidden=true;$('mobile-model-labels').hidden=true;
    for(const id of ['cut-btn','whole-btn','labels-btn','rotate-btn','zoom-in','zoom-out','reset-btn','light-btn'])$(id).disabled=true;
  }
  try {
    if(typeof THREE==='undefined')throw new Error('3D engine unavailable');
    const T=THREE;
    renderer=new T.WebGLRenderer({canvas,antialias:true,alpha:true,powerPreference:'low-power'});
    renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,1.75));renderer.setClearColor(0x000000,0);renderer.outputColorSpace=T.SRGBColorSpace;
    renderer.toneMapping=T.ACESFilmicToneMapping;renderer.toneMappingExposure=1.05;
    scene=new T.Scene();camera=new T.PerspectiveCamera(36,1,.1,80);
    scene.environment=createEyeStudio(T,renderer).texture;
    scene.add(new T.HemisphereLight(0xe5f2ff,0x4d4240,.7));
    const main=new T.DirectionalLight(0xfff5e9,2.7);main.position.set(-4,6,7);scene.add(main);
    const fill=new T.DirectionalLight(0xc7e1ff,.65);fill.position.set(4,1,5);scene.add(fill);
    const rim=new T.DirectionalLight(0xe2f8ff,1.8);rim.position.set(-1,-3,-5);scene.add(rim);
    model=createEyeModel(T);scene.add(model.eye);model.highlight(data[selected].id);
    const raycaster=new T.Raycaster(),pointer=new T.Vector2(),projected=new T.Vector3();
    const pointers=new Map();let down=null,pinchDistance=0;
    function resize(){const w=viewer.clientWidth,h=viewer.clientHeight;if(w&&h){renderer.setSize(w,h,false);setCamera();}}
    new ResizeObserver(resize).observe(viewer);resize();
    const localToScreen=p=>{projected.set(...p);model.eye.localToWorld(projected);projected.project(camera);return {x:(projected.x*.5+.5)*viewer.clientWidth,y:(-.5*projected.y+.5)*viewer.clientHeight,z:projected.z};};
    function updateLabels(){
      if(!labelsOn)return;
      const w=viewer.clientWidth,h=viewer.clientHeight;model.eye.updateMatrixWorld(true);camera.updateMatrixWorld(true);
      for(const l of labelObjects){
        const p=localToScreen(model.anchors[l.id]);let visible=mode==='cut'||l.id==='cornea'||l.id==='nerve';
        visible=visible&&p.z>-1&&p.z<1&&p.x>0&&p.x<w&&p.y>0&&p.y<h;
        l.el.hidden=!visible;l.line.style.display=visible?'':'none';l.circle.style.display=visible?'':'none';if(!visible)continue;
        const x=l.el.offsetLeft+l.el.offsetWidth/2,y=l.el.offsetTop+l.el.offsetHeight/2;
        l.line.setAttribute('x1',String(x));l.line.setAttribute('y1',String(y));l.line.setAttribute('x2',p.x.toFixed(1));l.line.setAttribute('y2',p.y.toFixed(1));l.circle.setAttribute('cx',p.x.toFixed(1));l.circle.setAttribute('cy',p.y.toFixed(1));
      }
      const d=data[selected],p=localToScreen(model.anchors[d.id]);const dot=$('selected-point');
      dot.hidden=p.x<6||p.x>w-6||p.y<6||p.y>h-6||p.z>1||(mode==='whole'&&!['cornea','sclera','iris','pupil','nerve'].includes(d.id));
      dot.style.left=p.x+'px';dot.style.top=p.y+'px';
    }
    function pick(clientX,clientY){
      const rect=canvas.getBoundingClientRect();pointer.set((clientX-rect.left)/rect.width*2-1,-(clientY-rect.top)/rect.height*2+1);
      raycaster.setFromCamera(pointer,camera);const hits=raycaster.intersectObjects(model.pickables.filter(o=>o.visible),false);
      if(hits.length){const hit=hits.find(h=>h.object.material.opacity>.35&&!(h.object.material.transmission>.5))||hits[0];const id=hit.object.userData.id,i=data.findIndex(d=>d.id===id);if(i>=0)selectPart(i,false);}
    }
    const distance=()=>{const p=Array.from(pointers.values());return p.length<2?0:Math.hypot(p[0].x-p[1].x,p[0].y-p[1].y);};
    canvas.addEventListener('pointerdown',e=>{
      if(e.pointerType==='mouse'&&e.button!==0)return;
      canvas.setPointerCapture(e.pointerId);pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});stopRotation();interacting=true;
      if(pointers.size===1)down={x:e.clientX,y:e.clientY,moved:false};else{if(down)down.moved=true;pinchDistance=distance();}
    });
    canvas.addEventListener('pointermove',e=>{
      const prior=pointers.get(e.pointerId);if(!prior)return;const dx=e.clientX-prior.x,dy=e.clientY-prior.y;pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});
      if(down&&Math.hypot(e.clientX-down.x,e.clientY-down.y)>5)down.moved=true;
      if(pointers.size>1){const next=distance();if(pinchDistance>0)changeZoom(next/pinchDistance);pinchDistance=next;}
      else{model.eye.rotation.y+=dx*.007;model.eye.rotation.x+=dy*.007;dirty=true;$('view-orientation').textContent='Góc nhìn tự do · Đặt lại để về góc ban đầu';}
    });
    function endPointer(e){
      if(e.type==='pointerup'&&down&&!down.moved&&pointers.size===1)pick(e.clientX,e.clientY);
      pointers.delete(e.pointerId);pinchDistance=distance();if(!pointers.size){interacting=false;down=null;}else if(down)down.moved=true;
    }
    canvas.addEventListener('pointerup',endPointer);canvas.addEventListener('pointercancel',endPointer);canvas.addEventListener('lostpointercapture',e=>{pointers.delete(e.pointerId);if(!pointers.size){interacting=false;down=null;}});
    canvas.addEventListener('wheel',e=>{e.preventDefault();changeZoom(Math.exp(-Math.max(-90,Math.min(90,e.deltaY))*.0017));},{passive:false});
    canvas.addEventListener('keydown',e=>{
      if(['ArrowLeft','ArrowRight','ArrowUp','ArrowDown','+','=','-','Home'].includes(e.key)){
        e.preventDefault();stopRotation();
        if(e.key==='Home'){resetView();return;}
        if(e.key==='+'||e.key==='=')changeZoom(1.1);else if(e.key==='-')changeZoom(1/1.1);else{
          if(e.key==='ArrowLeft')model.eye.rotation.y-=.12;if(e.key==='ArrowRight')model.eye.rotation.y+=.12;if(e.key==='ArrowUp')model.eye.rotation.x-=.12;if(e.key==='ArrowDown')model.eye.rotation.x+=.12;
          $('view-orientation').textContent='Góc nhìn tự do · Đặt lại để về góc ban đầu';
        }dirty=true;
      }
    });
    canvas.addEventListener('webglcontextlost',e=>{e.preventDefault();cancelAnimationFrame(animationId);fallback();});
    let inView=true;new IntersectionObserver(entries=>{inView=entries[0].isIntersecting;if(inView)dirty=true;}).observe(viewer);
    function frame(time){
      animationId=requestAnimationFrame(frame);
      if(!canRender||document.hidden||!inView||!hostVisible){lastTime=time;return;}
      const dt=Math.min((time-lastTime)/1000,.05);lastTime=time;
      if(rotating&&!interacting){model.eye.rotation.y+=dt*.22;dirty=true;$('view-orientation').textContent='Đang xoay · Bấm dừng để quan sát';}
      if(lightOn&&!reduced){model.animateLight(time/1000);dirty=true;}
      if(dirty){renderer.render(scene,camera);updateLabels();dirty=false;}
    }
    canRender=true;$('loader').hidden=true;model.animateLight(0);renderer.render(scene,camera);updateLabels();animationId=requestAnimationFrame(frame);
    document.addEventListener('visibilitychange',()=>{dirty=true;});
  } catch(error){console.error('Không thể hiển thị mô hình mắt 3D:',error);fallback();}
  // Progressive enhancement: the same selection action may be used by a supported assistant.
  const context=document.modelContext;
  if(context&&typeof context.registerTool==='function'){
    const lifetime=new AbortController();
    try{Promise.resolve(context.registerTool({
      name:'select_eye_structure',title:'Xem một bộ phận của mắt',
      description:'Chọn bộ phận trên mô hình giải phẫu mắt và hiển thị giải thích bằng tiếng Việt.',
      inputSchema:{type:'object',properties:{structure:{type:'string',enum:data.map(d=>d.id)}},required:['structure'],additionalProperties:false},
      annotations:{readOnlyHint:false,untrustedContentHint:false},
      execute(input){
        if(!input||typeof input!=='object'||Object.keys(input).some(k=>k!=='structure'))throw new Error('Cần một tên bộ phận hợp lệ.');
        const i=data.findIndex(d=>d.id===input.structure);if(i<0)throw new Error('Bộ phận không có trong mô hình.');selectPart(i);
        return {structure:data[i].id,name:data[i].name,description:data[i].summary,view:mode,modelAvailable:canRender};
      }
    },{signal:lifetime.signal})).catch(()=>{});}catch(error){/* Optional browser capability. */}
    window.addEventListener('pagehide',()=>lifetime.abort(),{once:true});
  }
})();
