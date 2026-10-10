(function () {
  'use strict';

  const config = window.ecPublicProof;
  const card = document.getElementById('ec-public-proof');
  if (!config || !card || !window.fetch) return;

  const name = card.querySelector('.ec-public-proof__name');
  const close = card.querySelector('.ec-public-proof__close');
  if (!name || !close) return;

  const prefix = 'ec_public_proof_';
  let memory = { shown: 0, dismissed: false };
  let cursorFallback = null;
  let timer = null;
  let hideTimer = null;
  let pending = false;
  let hasMultiple = true;

  function read(key, fallback) {
    try {
      const value = window.sessionStorage.getItem(prefix + key);
      return value === null ? fallback : value;
    } catch (_) {
      return memory[key];
    }
  }
  function write(key, value) {
    memory[key] = value;
    try { window.sessionStorage.setItem(prefix + key, String(value)); } catch (_) { /* Private browsing fallback. */ }
  }
  function randomSeed() { return Math.floor(Math.random() * 2147483647); }
  function readCursor() {
    if (cursorFallback === null) cursorFallback = randomSeed();
    try {
      const saved = window.localStorage.getItem(prefix + 'cursor_v2');
      const number = saved === null ? NaN : Number(saved);
      if (Number.isSafeInteger(number) && number >= 0) {
        cursorFallback = number;
      } else {
        window.localStorage.setItem(prefix + 'cursor_v2', String(cursorFallback));
      }
    } catch (_) { /* Use the random per-page fallback when storage is blocked. */ }
    return cursorFallback;
  }
  function writeCursor(value) {
    cursorFallback = value;
    try { window.localStorage.setItem(prefix + 'cursor_v2', String(value)); } catch (_) { /* Storage is optional. */ }
  }
  function shown() { return Math.max(0, parseInt(read('shown', 0), 10) || 0); }
  function dismissed() { return read('dismissed', false) === 'true' || read('dismissed', false) === true; }
  function schedule(ms) {
    window.clearTimeout(timer);
    if (!dismissed() && shown() < config.maxPerSession) timer = window.setTimeout(showNext, ms);
  }
  function bookingOpen() {
    return !!document.querySelector('#ec-booking-dialog[open], body.ec-booking-open');
  }

  async function showNext() {
    if (pending || dismissed() || shown() >= config.maxPerSession) return;
    if (document.visibilityState === 'hidden' || bookingOpen()) { schedule(10000); return; }
    pending = true;
    try {
      const cursor = readCursor();
      const url = new URL(config.endpoint, window.location.href);
      url.searchParams.set('action', 'ec_public_proof_next');
      url.searchParams.set('cursor', String(cursor));
      const response = await fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) throw new Error('Unavailable');
      const payload = await response.json();
      const result = payload && payload.success && payload.data;
      if (!result || typeof result.label !== 'string' || !result.label) return;
      if (document.visibilityState === 'hidden' || bookingOpen() || dismissed()) { schedule(10000); return; }
      name.textContent = result.label;
      writeCursor(Math.max(0, parseInt(result.next_cursor, 10) || 0));
      hasMultiple = result.has_multiple === true;
      write('shown', shown() + 1);
      card.hidden = false;
      window.clearTimeout(hideTimer);
      hideTimer = window.setTimeout(function () {
        card.hidden = true;
        if (hasMultiple) schedule(45000);
      }, config.durationMs);
    } catch (_) {
      schedule(60000);
    } finally {
      pending = false;
    }
  }

  close.addEventListener('click', function () {
    card.hidden = true;
    write('dismissed', true);
    window.clearTimeout(timer);
    window.clearTimeout(hideTimer);
  });
  const bookingDialog = document.getElementById('ec-booking-dialog');
  if (bookingDialog && window.MutationObserver) {
    const observer = new MutationObserver(function () {
      if (bookingDialog.open && !card.hidden) {
        card.hidden = true;
        window.clearTimeout(hideTimer);
      }
    });
    observer.observe(bookingDialog, { attributes: true, attributeFilter: ['open'] });
  }
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden' && !card.hidden) {
      card.hidden = true;
      window.clearTimeout(hideTimer);
    }
  });
  schedule(config.delayMs);
}());
