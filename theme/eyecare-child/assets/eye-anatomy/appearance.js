/* Local, deterministic tissue textures. No photographs or external requests. */
function createEyeAppearance(T) {
  const clamp=(v,a=0,b=1)=>Math.max(a,Math.min(b,v));
  const smooth=(a,b,v)=>{const t=clamp((v-a)/(b-a));return t*t*(3-2*t);};
  const hash=(x,y)=>{const v=Math.sin(x*127.1+y*311.7)*43758.5453;return v-Math.floor(v);};
  function noise(x,y){
    const i=Math.floor(x),j=Math.floor(y),u=smooth(0,1,x-i),v=smooth(0,1,y-j);
    return (hash(i,j)*(1-u)+hash(i+1,j)*u)*(1-v)+(hash(i,j+1)*(1-u)+hash(i+1,j+1)*u)*v;
  }
  const textures={};
  function texture(kind){
    if(textures[kind])return textures[kind];
    const canvas=document.createElement('canvas');canvas.width=512;canvas.height=1024;
    const ctx=canvas.getContext('2d'),image=ctx.createImageData(canvas.width,canvas.height);
    for(let y=0;y<canvas.height;y++)for(let x=0;x<canvas.width;x++){
      const u=x/(canvas.width-1),v=y/(canvas.height-1),a=v*Math.PI*2;
      const n=noise(u*48,Math.cos(a)*32+Math.sin(a)*17),fine=hash(x,y)-.5;
      let rgb;
      if(kind==='iris'){
        // Radial fibres, irregular collarette, crypts, and a dark limbal rim.
        const bend=.011*Math.sin(u*13+a*9)+.008*Math.sin(u*28+a*17);
        const fibre=noise((a+bend)*110, u*8)*.6+noise((a-bend)*260,u*17)*.4;
        const ridges=Math.pow(.5+.5*Math.sin((a+bend)*213),9);
        const collarette=Math.exp(-Math.pow((u-.22-.028*Math.sin(a*23))/.06,2));
        const crypt=Math.pow(noise(a*63,u*23),5)*smooth(.02,.12,u)*(1-smooth(.35,.6,u));
        const limbus=1-.80*smooth(.80,1,u),pupil=.4+.6*smooth(0,.065,u);
        const shade=(.48+fibre*.73+ridges*.16+collarette*.17-crypt*1.2)*limbus*pupil;
        rgb=[137*shade+9,91*shade+5,43*shade+5];
      }else if(kind==='sclera'){
        const pink=Math.exp(-u*10)*(.4+.6*noise(a*7,u*9));
        rgb=[238+n*13,232+n*14-pink*33,220+n*19-pink*26];
      }else if(kind==='retina'){
        const tissue=noise(u*15,v*60)*.65+n*.35;
        rgb=[192+tissue*20,83+tissue*22,53+tissue*17];
      }else if(kind==='choroid'){
        rgb=[87+n*45,29+n*16,27+n*16];
      }else{rgb=[220+n*20,197+n*24,161+n*24];}
      const offset=(y*canvas.width+x)*4;
      for(let c=0;c<3;c++)image.data[offset+c]=clamp(rgb[c]+fine*3,0,255);
      image.data[offset+3]=255;
    }
    ctx.putImageData(image,0,0);
    const map=new T.CanvasTexture(canvas);map.colorSpace=T.SRGBColorSpace;map.anisotropy=4;
    textures[kind]=map;return map;
  }
  function tissue(kind){return new T.MeshPhysicalMaterial({
    color:'#ffffff',map:texture(kind),roughness:kind==='sclera'?.33:.69,
    metalness:0,envMapIntensity:.45,clearcoat:kind==='sclera'?.28:.035,clearcoatRoughness:.30,
    side:T.DoubleSide,bumpMap:texture(kind),bumpScale:kind==='sclera'?.006:.002
  });}
  function glass(kind){return new T.MeshPhysicalMaterial({
    color:kind==='lens'?'#f2e9d0':'#ffffff',roughness:kind==='lens'?.075:.025,metalness:0,
    clearcoat:1,clearcoatRoughness:.04,transparent:kind==='lens',
    opacity:kind==='lens'?.32:1,transmission:kind==='lens'?0:.98,thickness:.04,depthWrite:false,side:T.DoubleSide,
    envMapIntensity:kind==='lens'?.6:.9,ior:1.38,specularIntensity:1
  });}
  function retinalSpot(color){
    const canvas=document.createElement('canvas');canvas.width=128;canvas.height=128;
    const ctx=canvas.getContext('2d'),gradient=ctx.createRadialGradient(64,64,8,64,64,64);
    gradient.addColorStop(0,color);gradient.addColorStop(.45,color);
    gradient.addColorStop(1,'rgba(0,0,0,0)');ctx.fillStyle=gradient;ctx.fillRect(0,0,128,128);
    const map=new T.CanvasTexture(canvas);map.colorSpace=T.SRGBColorSpace;
    return new T.MeshStandardMaterial({map,transparent:true,depthWrite:false,roughness:.75,side:T.DoubleSide});
  }
  return {tissue,glass,retinalSpot};
}

function createEyeStudio(T,renderer){
  // Soft photographic light panels reflected on the wet corneal surface.
  const room=new T.Scene();room.background=new T.Color('#647781');
  function panel(x,y,z,w,h,intensity){
    const p=new T.Mesh(new T.PlaneGeometry(w,h),new T.MeshBasicMaterial({color:new T.Color().setScalar(intensity),side:T.DoubleSide}));
    p.position.set(x,y,z);p.lookAt(0,0,0);room.add(p);
  }
  panel(-5,6,5,4,6,4);panel(5,2,1,2,6,1.5);panel(0,5,-5,5,3,2);
  const generator=new T.PMREMGenerator(renderer),target=generator.fromScene(room,.02);
  generator.dispose();room.traverse(o=>{if(o.isMesh){o.geometry.dispose();o.material.dispose();}});
  return target;
}


