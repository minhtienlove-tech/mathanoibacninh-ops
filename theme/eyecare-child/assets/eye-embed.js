(function () {
 'use strict';
 document.querySelectorAll('[data-eye-frame]').forEach(function (frame) {
  var origin = new URL(frame.src, location.href).origin;
  function visibility(visible) {
   if (frame.contentWindow) frame.contentWindow.postMessage({type:'eyecare-eye-visibility', visible:visible}, origin);
  }
  window.addEventListener('message', function (event) {
   if (event.origin !== origin || event.source !== frame.contentWindow) return;
   var data = event.data;
   if (!data || data.type !== 'eyecare-eye-height' || !Number.isFinite(data.height)) return;
   var height = Math.ceil(data.height);
   if (height >= 300 && height <= 16000) frame.style.height = height + 'px';
  });
  var inView = true;
  if ('IntersectionObserver' in window) {
   new IntersectionObserver(function (entries) {
    inView = entries[0].isIntersecting;
    visibility(inView && !document.hidden);
   }, {rootMargin:'150px'}).observe(frame);
  }
  frame.addEventListener('load', function () { visibility(inView && !document.hidden); });
  document.addEventListener('visibilitychange', function () { visibility(inView && !document.hidden); });
 });
})();
