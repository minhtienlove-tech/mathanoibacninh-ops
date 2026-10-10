/* Admin-only visual demo rotates synthetic labels without fetching or storing bookings. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '..', 'assets', 'lich-kham-cong-khai-demo.js'), 'utf8');
const labels = Array.from({ length: 100 }, (_, index) => `Anh TênMẫu${index + 1} (mẫu)`);
labels[0] = 'Anh Tú (mẫu)';
labels[1] = 'Chị Ninh (mẫu)';
labels[99] = 'Chị Thủy (mẫu)';
const handlers = {};
const timers = [];
const name = { textContent: '' };
const counter = { textContent: '' };
const toggle = { textContent: 'Tạm dừng', addEventListener(event, fn) { handlers.toggle = fn; } };
const next = { addEventListener(event, fn) { handlers.next = fn; } };
const card = { querySelector() { return name; } };
const document = {
  getElementById(id) {
    return {
      'ec-public-proof-demo-card': card,
      'ec-public-proof-demo-counter': counter,
      'ec-public-proof-demo-toggle': toggle,
      'ec-public-proof-demo-next': next,
    }[id] || null;
  },
};
const window = {
  ecPublicProofDemo: { labels },
  setTimeout(fn) { timers.push(fn); return timers.length; },
  clearTimeout() {},
};
vm.runInNewContext(script, { window, document, Array });

assert.equal(name.textContent, 'Anh Tú (mẫu)');
assert.equal(counter.textContent, 'Mẫu 1/100');
assert.equal(timers.length, 1);
handlers.next();
assert.equal(name.textContent, 'Chị Ninh (mẫu)');
for (let n = 0; n < 98; n += 1) handlers.next();
assert.equal(name.textContent, 'Chị Thủy (mẫu)');
handlers.next();
assert.equal(name.textContent, 'Anh Tú (mẫu)');
handlers.toggle();
assert.equal(toggle.textContent, 'Tiếp tục');
const countWhenPaused = timers.length;
handlers.toggle();
assert.equal(toggle.textContent, 'Tạm dừng');
assert.equal(timers.length, countWhenPaused + 1);
console.log('PASS: admin preview rotates clearly synthetic entries and supports pause/next.');
