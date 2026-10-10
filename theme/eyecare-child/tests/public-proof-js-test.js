/* Browserless rotation check: random first entry, persistent cursor thereafter. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const script = fs.readFileSync(require('node:path').join(__dirname, '..', 'assets', 'lich-kham-cong-khai.js'), 'utf8');

function storage(backing) {
  return {
    getItem(key) { return backing.has(key) ? backing.get(key) : null; },
    setItem(key, value) { backing.set(key, String(value)); },
  };
}
function page(localBacking, random) {
  const timers = [];
  const requests = [];
  const name = { textContent: '' };
  const close = { addEventListener() {} };
  const realText = { hidden: false };
  const prompt = { hidden: true };
  const icon = { textContent: '✓' };
  const elements = {
    '.ec-public-proof__name': name,
    '.ec-public-proof__close': close,
    '.ec-public-proof__real': realText,
    '.ec-public-proof__prompt': prompt,
    '.ec-public-proof__icon': icon,
  };
  const card = {
    hidden: true,
    querySelector(selector) { return elements[selector]; },
  };
  const fakeMath = Object.create(Math);
  fakeMath.random = () => random;
  const fetch = async url => {
    requests.push(url);
    return { ok: true, json: async () => ({ success: true, data: { mode: 'real_booking', label: 'Anh Tú', next_cursor: 1, has_multiple: true } }) };
  };
  const window = {
    ecPublicProof: { endpoint: 'https://example.test/wp-admin/admin-ajax.php', delayMs: 20000, durationMs: 5000, maxPerSession: 2 },
    fetch,
    location: { href: 'https://example.test/' },
    localStorage: storage(localBacking),
    sessionStorage: storage(new Map()),
    setTimeout(callback) { timers.push(callback); return timers.length; },
    clearTimeout() {},
  };
  const document = {
    visibilityState: 'visible',
    getElementById() { return card; },
    querySelector() { return null; },
    addEventListener() {},
  };
  vm.runInNewContext(script, { window, document, fetch, URL, Math: fakeMath });
  return { timers, requests, card, name };
}

(async () => {
  const local = new Map();
  const first = page(local, 0.5);
  await first.timers[0]();
  const firstCursor = Number(new URL(first.requests[0]).searchParams.get('cursor'));
  assert(firstCursor > 0, 'A fresh visitor must not always start at the oldest booking');
  assert.equal(first.name.textContent, 'Anh Tú');
  assert.equal(first.card.hidden, false);
  assert.equal(local.get('ec_public_proof_cursor_v2'), '1');

  const second = page(local, 0.9);
  await second.timers[0]();
  assert.equal(new URL(second.requests[0]).searchParams.get('cursor'), '1', 'Next session resumes rotation rather than reseeding');
  console.log('PASS: public proof rotation starts randomly and persists between sessions.');
})().catch(error => { console.error(error); process.exitCode = 1; });
