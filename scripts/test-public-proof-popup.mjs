// Browser-free behavior checks for the public booking notice.
// Run: node scripts/test-public-proof-popup.mjs
import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const script = readFileSync(resolve(root, 'theme/eyecare-child/assets/lich-kham-cong-khai.js'), 'utf8');
const php = readFileSync(resolve(root, 'theme/eyecare-child/inc/lich-kham-cong-khai.php'), 'utf8');

// Synthetic patient names and the admin preview must be absent from deployable theme code.
assert.doesNotMatch(php, /ec_public_proof_demo_labels|ecPublicProofDemo|lich-kham-cong-khai-demo|DỮ LIỆU MẪU|\(mẫu\)/iu);
assert.doesNotMatch(script, /ecPublicProofDemo|DỮ LIỆU MẪU|\(mẫu\)/iu);
for (const file of ['lich-kham-cong-khai-demo.js', 'lich-kham-cong-khai-demo.css']) {
  assert.equal(existsSync(resolve(root, 'theme/eyecare-child/assets', file)), false, `${file} must not be deployed.`);
}

function storage(seed = {}) {
  const values = new Map(Object.entries(seed));
  return {
    values,
    getItem(key) { return values.has(key) ? values.get(key) : null; },
    setItem(key, value) { values.set(key, String(value)); },
  };
}

function element(initial = {}) {
  const listeners = new Map();
  return {
    hidden: true,
    textContent: '',
    ...initial,
    addEventListener(type, listener) { listeners.set(type, listener); },
    click() { listeners.get('click')?.(); },
  };
}

function setup(responses, { session = storage(), local = storage({ ec_public_proof_cursor_v2: '0' }) } = {}) {
  let now = 0;
  let nextId = 1;
  let bookingOpen = false;
  const timers = new Map();
  const calls = [];
  const close = element();
  const name = element();
  const real = element({ hidden: false });
  const prompt = element({ hidden: true });
  const icon = element({ textContent: '✓' });
  const controls = new Map([
    ['.ec-public-proof__close', close],
    ['.ec-public-proof__name', name],
    ['.ec-public-proof__real', real],
    ['.ec-public-proof__prompt', prompt],
    ['.ec-public-proof__icon', icon],
  ]);
  const card = element({ hidden: true, querySelector(selector) { return controls.get(selector); } });
  const documentListeners = new Map();
  const document = {
    visibilityState: 'visible',
    getElementById(id) { return id === 'ec-public-proof' ? card : null; },
    querySelector() { return bookingOpen ? {} : null; },
    addEventListener(type, listener) { documentListeners.set(type, listener); },
  };
  const window = {
    location: { href: 'https://mathanoibacninh.com/' },
    ecPublicProof: {
      endpoint: 'https://mathanoibacninh.com/wp-admin/admin-ajax.php',
      delayMs: 100,
      durationMs: 50,
      maxPerSession: 2,
    },
    sessionStorage: session,
    localStorage: local,
    setTimeout(fn, delay) { const id = nextId++; timers.set(id, { at: now + delay, fn }); return id; },
    clearTimeout(id) { timers.delete(id); },
    async fetch(url, options) {
      calls.push({ url, options });
      const response = responses.shift();
      if (response instanceof Error) throw response;
      return { ok: true, json: async () => ({ success: true, data: response }) };
    },
  };
  runInNewContext(script, { window, document, URL, fetch: window.fetch });

  async function advance(ms) {
    const target = now + ms;
    for (;;) {
      const due = [...timers].filter(([, timer]) => timer.at <= target).sort((a, b) => a[1].at - b[1].at)[0];
      if (!due) break;
      now = due[1].at;
      timers.delete(due[0]);
      await due[1].fn();
    }
    now = target;
  }

  return {
    card, close, name, real, prompt, icon, calls, session, local, advance,
    setBookingOpen(value) { bookingOpen = value; },
    setVisible(value) { document.visibilityState = value ? 'visible' : 'hidden'; documentListeners.get('visibilitychange')?.(); },
  };
}

function real(label, next, multiple = true) {
  return { mode: 'real_booking', label, next_cursor: next, has_multiple: multiple };
}

// The no-record state must show a truthful invitation, never an invented booking.
{
  const h = setup([{ mode: 'booking_prompt', label: '', next_cursor: 0, has_multiple: false }]);
  await h.advance(100);
  assert.equal(h.card.hidden, false);
  assert.equal(h.real.hidden, true);
  assert.equal(h.prompt.hidden, false);
  assert.equal(h.name.textContent, '');
  assert.equal(h.icon.textContent, '+');
  assert.equal(h.calls.length, 1);
  assert.match(h.calls[0].url, /action=ec_public_proof_next/);
  assert.equal(h.calls[0].options.cache, 'no-store');
  await h.advance(10000);
  assert.equal(h.card.hidden, true);
  await h.advance(120000);
  assert.equal(h.calls.length, 1, 'The generic prompt must not nag repeatedly.');
  assert.equal(h.session.getItem('ec_public_proof_shown'), '1');
  assert.match(php, /home_url\( '\/dat-lich-kham\/' \)/, 'The prompt must lead to the actual booking page.');
}

// Disabled or malformed API responses must not show a fabricated person or prompt.
for (const response of [
  { mode: 'disabled', label: '' },
  { label: '' },
  { mode: 'real_booking', label: '' },
]) {
  const h = setup([response]);
  await h.advance(100);
  assert.equal(h.card.hidden, true);
  assert.equal(h.name.textContent, '');
}

// Real, approved labels still rotate and respect the per-session cap.
{
  const h = setup([real('Anh Tú', 1), real('Chị Ninh', 0)]);
  await h.advance(100);
  assert.equal(h.card.hidden, false);
  assert.equal(h.real.hidden, false);
  assert.equal(h.prompt.hidden, true);
  assert.equal(h.name.textContent, 'Anh Tú');
  assert.equal(h.local.getItem('ec_public_proof_cursor_v2'), '1');
  await h.advance(50);
  assert.equal(h.card.hidden, true);
  await h.advance(45000);
  assert.equal(h.name.textContent, 'Chị Ninh');
  assert.equal(h.card.hidden, false);
  assert.equal(h.calls.length, 2);
  assert.equal(h.local.getItem('ec_public_proof_cursor_v2'), '0');
  await h.advance(120000);
  assert.equal(h.calls.length, 2);
}

// Dismissal persists for the tab session.
{
  const session = storage();
  const h = setup([real('Anh Tú', 1)], { session });
  await h.advance(100);
  h.close.click();
  assert.equal(h.card.hidden, true);
  await h.advance(120000);
  assert.equal(h.calls.length, 1);
  const reload = setup([real('Chị Ninh', 0)], { session });
  await reload.advance(120000);
  assert.equal(reload.calls.length, 0);
}

// Keep the notice out of the way when the tab is hidden or booking form is open.
{
  const h = setup([real('Anh Tú', 0, false)]);
  h.setBookingOpen(true);
  await h.advance(100);
  assert.equal(h.calls.length, 0);
  h.setBookingOpen(false);
  h.setVisible(false);
  await h.advance(10000);
  assert.equal(h.calls.length, 0);
  h.setVisible(true);
  await h.advance(10000);
  assert.equal(h.calls.length, 1);
  assert.equal(h.card.hidden, false);
  h.setVisible(false);
  assert.equal(h.card.hidden, true);
}

console.log('Public booking notice behavior checks passed.');
