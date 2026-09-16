/* Same-origin iframe sizing; does not change hospital page content. */
(() => {
  'use strict';
  if (window.parent === window) return;
  const parentOrigin = window.location.origin;
  const content = document.getElementById('eye-anatomy-content');
  if (!content) return;
  let previousHeight = 0;
  let scheduled = false;
  function measure() {
    scheduled = false;
    // Measure the content box, never the viewport-sized document element.
    const height = Math.ceil(content.getBoundingClientRect().height + content.offsetTop);
    if (height > 0 && height !== previousHeight) {
      previousHeight = height;
      window.parent.postMessage({ type: 'eyecare-eye-height', height }, parentOrigin);
    }
  }
  function schedule() {
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(measure);
  }
  if ('ResizeObserver' in window) new ResizeObserver(schedule).observe(content);
  if ('MutationObserver' in window) {
    new MutationObserver(schedule).observe(content, {
      subtree: true,
      childList: true,
      characterData: true,
      attributes: true,
      attributeFilter: ['hidden', 'open']
    });
  }
  window.addEventListener('message', event => {
    if (event.source !== window.parent || event.origin !== parentOrigin) return;
    const message = event.data;
    if (!message || message.type !== 'eyecare-eye-visibility' || typeof message.visible !== 'boolean') return;
    window.dispatchEvent(new CustomEvent('eyecare-eye-visibility', { detail: { visible: message.visible } }));
    // The parent may attach its listener after our first measurement.
    if (message.visible) { previousHeight = 0; schedule(); }
  });
  window.addEventListener('resize', schedule);
  window.addEventListener('load', schedule);
  document.addEventListener('toggle', schedule, true);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(schedule);
  schedule();
})();
