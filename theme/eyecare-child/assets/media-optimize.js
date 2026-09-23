/* global ecMedia */
(() => {
  'use strict';
  const start = document.getElementById('ec-media-start');
  if (!start) return;
  const stop = document.getElementById('ec-media-stop');
  const rename = document.getElementById('ec-media-rename');
  const progress = document.getElementById('ec-media-progress');
  const rows = [...document.querySelectorAll('tr[data-media-id]')];
  let busy = false, stopping = false;
  const status = (row, message, error = false) => {
    const target = row.querySelector('.ec-media-result');
    target.textContent = message;
    target.dataset.error = String(error);
  };
  async function request(row, mode, extra = {}) {
    const body = new URLSearchParams({ action: 'ec_media_optimize', nonce: ecMedia.nonce, id: row.dataset.mediaId, mode, ...extra });
    const response = await fetch(ecMedia.url, { method: 'POST', credentials: 'same-origin', body });
    let result;
    try { result = await response.json(); } catch (_) { throw new Error('Phiên đăng nhập hoặc máy chủ không phản hồi hợp lệ. Tải lại trang để kiểm tra.'); }
    if (!response.ok || !result.success) throw new Error(result.data?.message || 'Không xử lý được. Hãy tải lại trang.');
    return result.data;
  }
  function setBusy(value) {
    busy = value;
    start.disabled = value;
    rename.disabled = value;
    stop.disabled = !value;
    document.getElementById('ec-media-all').disabled = value;
    rows.forEach(row => row.querySelectorAll('input,select,button').forEach(el => {
      if (value) { el.dataset.wasDisabled = String(el.disabled); el.disabled = true; }
      else { el.disabled = el.dataset.wasDisabled === 'true'; }
    }));
  }
  rows.forEach(row => {
    const owner = row.querySelector('.ec-media-owner');
    const manual = row.querySelector('.ec-media-owner-id');
    owner.addEventListener('change', () => {
      if (manual) manual.value = '';
      row.dataset.ownerId = owner.value;
      row.querySelector('.ec-media-filename').textContent = owner.selectedOptions[0]?.dataset.filename || 'Chưa chọn bài viết';
      status(row, '');
    });
    row.dataset.ownerId = owner.value;
    manual?.addEventListener('input', () => {
      row.dataset.ownerId = '';
      row.querySelector('.ec-media-filename').textContent = 'Bấm “Xem tên” để kiểm tra bài viết';
    });
    row.querySelector('.ec-media-preview')?.addEventListener('click', async () => {
      if (busy) return;
      if (!manual.value || Number(manual.value) < 1) { status(row, 'Nhập ID bài viết trước.', true); return; }
      const requestedId = manual.value;
      setBusy(true);
      try {
        const result = await request(row, 'preview', { post_id: requestedId });
        row.dataset.ownerId = String(result.post_id);
        row.querySelector('.ec-media-filename').textContent = result.filename;
        status(row, `Bài viết: ${result.title}`);
      } catch (error) { status(row, error.message, true); }
      finally { setBusy(false); }
    });
    row.querySelector('.ec-media-restore')?.addEventListener('click', async () => {
      if (busy) return;
      setBusy(true);
      status(row, 'Đang khôi phục…');
      try {
        const result = await request(row, 'restore');
        status(row, result.message);
        row.querySelector('.ec-media-restore').dataset.wasDisabled = 'true';
        progress.textContent = 'Khôi phục xong. Tải lại danh sách để xem trạng thái mới.';
      } catch (error) { status(row, error.message, true); }
      finally { setBusy(false); }
    });
  });
  document.getElementById('ec-media-all').addEventListener('change', event => {
    rows.forEach(row => { const box = row.querySelector('.ec-media-select'); if (!box.disabled) box.checked = event.target.checked; });
  });
  stop.addEventListener('click', () => { stopping = true; stop.disabled = true; progress.textContent = 'Sẽ dừng khi ảnh hiện tại hoàn tất.'; });
  async function processSelected(mode) {
    if (busy) return;
    const selected = rows.filter(row => { const box = row.querySelector('.ec-media-select'); return box.checked && !box.disabled; });
    if (!selected.length) { progress.textContent = 'Hãy chọn ít nhất một ảnh.'; return; }
    const invalid = selected.filter(row => !row.dataset.ownerId);
    if (invalid.length) {
      invalid.forEach(row => status(row, 'Chọn bài viết hoặc nhập ID rồi bấm “Xem tên” trước.', true));
      progress.textContent = 'Chưa xử lý: một số ảnh chưa có bài viết/tên dự kiến.';
      return;
    }
    const width = document.getElementById('ec-media-width').value;
    const qualityInput = document.getElementById('ec-media-quality');
    if (mode === 'optimize' && !qualityInput.reportValidity()) return;
    const quality = qualityInput.value;
    setBusy(true); stopping = false;
    let done = 0, failed = 0;
    try {
      for (const row of selected) {
        if (stopping) break;
        progress.textContent = `Đang xử lý ${done + failed + 1}/${selected.length}…`;
        status(row, mode === 'rename' ? 'Đang tạo bản đổi tên…' : 'Đang tạo bản WebP…');
        try {
          const result = await request(row, mode, { post_id: row.dataset.ownerId, width, quality });
          status(row, result.message);
          row.querySelectorAll('input,select,button').forEach(el => { el.dataset.wasDisabled = 'true'; });
          row.querySelector('.ec-media-select').checked = false;
          done++;
        } catch (error) { status(row, error.message, true); failed++; }
      }
    } finally {
      setBusy(false);
      progress.textContent = `${stopping ? 'Đã dừng. ' : ''}Thành công ${done}; chưa xử lý được ${failed}; còn ${selected.length - done - failed}. Tải lại danh sách để xem ảnh mới và nút khôi phục.`;
    }
  }
  start.addEventListener('click', () => processSelected('optimize'));
  rename.addEventListener('click', () => processSelected('rename'));
  window.addEventListener('beforeunload', event => { if (busy) { event.preventDefault(); event.returnValue = ''; } });
})();
