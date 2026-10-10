(function () {
  'use strict';

  const config = window.ecPublicProofDemo;
  const card = document.getElementById('ec-public-proof-demo-card');
  const label = card && card.querySelector('.ec-public-proof__name');
  const counter = document.getElementById('ec-public-proof-demo-counter');
  const toggle = document.getElementById('ec-public-proof-demo-toggle');
  const next = document.getElementById('ec-public-proof-demo-next');
  if (!config || !Array.isArray(config.labels) || config.labels.length !== 100 || !label || !counter || !toggle || !next) return;

  let index = 0;
  let timer = null;
  let playing = true;

  function showNext() {
    label.textContent = config.labels[index];
    counter.textContent = 'Mẫu ' + (index + 1) + '/100';
    index = (index + 1) % config.labels.length;
  }
  function schedule() {
    window.clearTimeout(timer);
    if (playing) timer = window.setTimeout(function () { showNext(); schedule(); }, 3500);
  }

  toggle.addEventListener('click', function () {
    playing = !playing;
    toggle.textContent = playing ? 'Tạm dừng' : 'Tiếp tục';
    schedule();
  });
  next.addEventListener('click', function () { showNext(); schedule(); });
  showNext();
  schedule();
}());
