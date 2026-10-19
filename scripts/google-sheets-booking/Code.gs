/* Fixed-destination booking bridge. No patient data or credentials are logged. */
const EC_BRIDGE = Object.freeze({
  spreadsheetId: '1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk',
  sheetId: 0,
  callbackUrl: 'https://mathanoibacninh.com/wp-json/ec-booking/v1/sheet-change',
  headers: ['Mã yêu cầu', 'Thời gian ghi nhận', 'Họ tên bệnh nhân', 'Số điện thoại', 'Ngày khám', 'Giờ khám', 'Trạng thái', 'Nguồn', 'Đồng ý lúc'],
  statePrefix: 'EC_BOOKING_STATE_',
  stateIndex: 'EC_BOOKING_STATE_INDEX',
  chunkSize: 7000,
  maxEncodedState: 170000,
  maxOutbox: 100,
  maxRows: 20000,
  maxCells: 180000,
  replaySeconds: 300
});

function ecFail_(code) {
  const error = new Error(code);
  error.ecCode = code;
  throw error;
}

function ecSecret_() {
  const value = PropertiesService.getScriptProperties().getProperty('WEBHOOK_SECRET');
  if (typeof value !== 'string' || !/^[a-f0-9]{64}$/.test(value)) ecFail_('not_configured');
  return value;
}

function ecHex_(bytes) {
  return bytes.map(value => ('0' + ((value + 256) % 256).toString(16)).slice(-2)).join('');
}

function ecHash_(value) {
  return ecHex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, value, Utilities.Charset.UTF_8));
}

function ecSignature_(payload) {
  return ecHex_(Utilities.computeHmacSha256Signature(payload, ecSecret_(), Utilities.Charset.UTF_8));
}

function ecEqual_(left, right) {
  if (typeof left !== 'string' || typeof right !== 'string' || left.length !== right.length) return false;
  let different = 0;
  for (let i = 0; i < left.length; i++) different |= left.charCodeAt(i) ^ right.charCodeAt(i);
  return different === 0;
}

function ecRequest_(event) {
  const text = event && event.postData && event.postData.contents;
  if (typeof text !== 'string' || text.length > 30000) ecFail_('invalid_request');
  let envelope;
  try { envelope = JSON.parse(text); } catch (_) { ecFail_('invalid_request'); }
  if (!envelope || typeof envelope.payload !== 'string' || typeof envelope.signature !== 'string' || !/^[a-f0-9]{64}$/.test(envelope.signature)) ecFail_('unauthorized');
  if (!ecEqual_(ecSignature_(envelope.payload), envelope.signature)) ecFail_('unauthorized');
  let data;
  try { data = JSON.parse(envelope.payload); } catch (_) { ecFail_('invalid_request'); }
  if (!data || !Number.isInteger(data.issued_at) || Math.abs(Math.floor(Date.now() / 1000) - data.issued_at) > EC_BRIDGE.replaySeconds) ecFail_('expired');
  if (data.spreadsheet_id !== EC_BRIDGE.spreadsheetId || data.sheet_id !== EC_BRIDGE.sheetId || !['append', 'probe'].includes(data.action)) ecFail_('destination');
  return data;
}

function ecSheet_() {
  const document = SpreadsheetApp.openById(EC_BRIDGE.spreadsheetId);
  const sheet = document.getSheets().find(item => item.getSheetId() === EC_BRIDGE.sheetId);
  if (!sheet) ecFail_('sheet_missing');
  return sheet;
}

function ecHeaders_(sheet) {
  const values = sheet.getRange(1, 1, 1, 9).getValues()[0];
  const formulas = sheet.getRange(1, 1, 1, 9).getFormulas()[0];
  if (JSON.stringify(values) !== JSON.stringify(EC_BRIDGE.headers) || formulas.some(Boolean)) ecFail_('headers_changed');
}

function ecCell_(value) {
  if (value instanceof Date) return value.toISOString();
  return value === null || value === undefined ? '' : String(value);
}

function ecRowHash_(values, formulas) {
  return ecHash_(JSON.stringify([values.map(ecCell_), formulas]));
}

function ecA1_(sheet, address) {
  return "'" + sheet.getName().replace(/'/g, "''") + "'" + (address ? '!' + address : '');
}

function ecRaw_(sheet, address) {
  return Sheets.Spreadsheets.Values.get(EC_BRIDGE.spreadsheetId, ecA1_(sheet, address), {valueRenderOption: 'UNFORMATTED_VALUE', dateTimeRenderOption: 'SERIAL_NUMBER'}).values || [];
}

function ecIdentity_(key, row) {
  return ecHash_(key ? 'key:' + key : 'unkeyed-row:' + row);
}

function ecGenericRowHash_(values, formulas) {
  let length = values.length;
  while (length && ecCell_(values[length - 1]) === '' && !formulas[length - 1]) length--;
  return length ? ecRowHash_(values.slice(0, length), formulas.slice(0, length)) : null;
}

function ecSnapshot_() {
  const document = SpreadsheetApp.openById(EC_BRIDGE.spreadsheetId);
  const snapshot = {version: 2, header: '', rows: {}, tabs: {}};
  let totalRows = 0;
  let totalCells = 0;
  document.getSheets().forEach(sheet => {
    const declaredRows = Math.max(1, sheet.getLastRow());
    const declaredColumns = Math.max(1, sheet.getLastColumn());
    if (declaredRows > EC_BRIDGE.maxRows || declaredRows * declaredColumns > EC_BRIDGE.maxCells) ecFail_('capacity');
    /* Read every populated column with raw serial values, independent of formatting. */
    const raw = ecRaw_(sheet, '');
    const last = Math.max(declaredRows, raw.length);
    const columns = Math.max(declaredColumns, raw.reduce((maximum, row) => Math.max(maximum, row.length), 1));
    totalRows += last;
    totalCells += last * columns;
    if (totalRows > EC_BRIDGE.maxRows || totalCells > EC_BRIDGE.maxCells) ecFail_('capacity');
    const formulas = sheet.getRange(1, 1, last, columns).getFormulas();
    const values = Array.from({length: last}, (_, row) => Array.from({length: columns}, (_, column) => raw[row] && raw[row][column] !== undefined ? raw[row][column] : ''));
    const id = String(sheet.getSheetId());
    const tab = {name: ecHash_(sheet.getName()), rows: {}};
    snapshot.tabs[id] = tab;
    if (sheet.getSheetId() === EC_BRIDGE.sheetId) {
      for (let index = 0; index < values.length; index++) {
        const cells = Array.from({length: 9}, (_, column) => values[index][column] === undefined ? '' : values[index][column]);
        const rowFormulas = Array.from({length: 9}, (_, column) => formulas[index][column] || '');
        if (index === 0) snapshot.header = ecRowHash_(cells, rowFormulas);
        else if (cells.some(value => ecCell_(value) !== '') || rowFormulas.some(Boolean)) {
          let identity = ecIdentity_(ecCell_(cells[0]), index + 1);
          if (snapshot.rows[identity]) identity = ecHash_('duplicate:' + identity + ':' + (index + 1));
          snapshot.rows[identity] = [ecRowHash_(cells, rowFormulas), index + 1];
        }
        const extra = ecGenericRowHash_(values[index].slice(9), formulas[index].slice(9));
        if (extra) tab.rows[index + 1] = extra;
      }
    } else {
      values.forEach((row, index) => {
        const hash = ecGenericRowHash_(row, formulas[index]);
        if (hash) tab.rows[index + 1] = hash;
      });
    }
  });
  return snapshot;
}

function ecReadState_() {
  const properties = PropertiesService.getScriptProperties();
  const raw = properties.getProperty(EC_BRIDGE.stateIndex);
  if (!raw) return null;
  try {
    const index = JSON.parse(raw);
    if (!/^[a-zA-Z0-9-]{1,80}$/.test(index.generation) || !Number.isInteger(index.count) || index.count < 1 || index.count > 30) ecFail_('state');
    let encoded = '';
    for (let i = 0; i < index.count; i++) {
      const chunk = properties.getProperty(EC_BRIDGE.statePrefix + index.generation + '_' + i);
      if (typeof chunk !== 'string') ecFail_('state');
      encoded += chunk;
    }
    const bytes = Utilities.base64Decode(encoded);
    /* Recreated binary blobs need gzip MIME/name metadata for the Apps Script decoder. */
    const state = JSON.parse(Utilities.ungzip(Utilities.newBlob(bytes, 'application/x-gzip', 'booking-state.json.gz')).getDataAsString('UTF-8'));
    if (![1, 2].includes(state.version) || !state.snapshot || !state.snapshot.rows || !Array.isArray(state.outbox) || !state.expected) ecFail_('state');
    return state;
  } catch (_) { ecFail_('state'); }
}

/* New chunks are committed before a one-property pointer swap. Keep old state on failure. */
function ecSaveState_(state) {
  const properties = PropertiesService.getScriptProperties();
  const encoded = Utilities.base64Encode(Utilities.gzip(Utilities.newBlob(JSON.stringify(state), 'application/json', 'booking-state.json'), 'booking-state.json.gz').getBytes());
  if (encoded.length > EC_BRIDGE.maxEncodedState) ecFail_('capacity');
  const generation = Utilities.getUuid();
  const chunks = {};
  let count = 0;
  for (let offset = 0; offset < encoded.length; offset += EC_BRIDGE.chunkSize) chunks[EC_BRIDGE.statePrefix + generation + '_' + count++] = encoded.slice(offset, offset + EC_BRIDGE.chunkSize);
  properties.setProperties(chunks, false);
  properties.setProperty(EC_BRIDGE.stateIndex, JSON.stringify({generation: generation, count: count}));
  const keep = EC_BRIDGE.statePrefix + generation + '_';
  Object.keys(properties.getProperties()).forEach(key => {
    if (key !== EC_BRIDGE.stateIndex && key.indexOf(EC_BRIDGE.statePrefix) === 0 && key.indexOf(keep) !== 0) properties.deleteProperty(key);
  });
}

function ecInitialState_(snapshot) {
  return {version: 2, snapshot: snapshot, expected: {}, outbox: []};
}

/* Read-only manual diagnostic. Logs only stage flags, types and aggregate counts. */
function diagnoseBookingBridge() {
  const result = {
    stage: 'properties', index_present: false, index_parse: false,
    generation_valid: false, chunk_count: null, chunks_present: 0,
    encoded_characters: 0, base64_success: false, base64_bytes: 0,
    gzip_magic: false, ungzip_default_success: false, ungzip_mime_success: false,
    json_parse: false, state_version: null, primary_rows: null, tabs: null, outbox_events: null
  };
  const finish = () => { console.log(JSON.stringify(result)); return result; };
  try {
    const properties = PropertiesService.getScriptProperties();
    const raw = properties.getProperty(EC_BRIDGE.stateIndex);
    result.index_present = typeof raw === 'string' && raw.length > 0;
    if (!result.index_present) { result.stage = 'no_state'; return finish(); }
    result.stage = 'index';
    const index = JSON.parse(raw);
    result.index_parse = true;
    result.generation_valid = typeof index.generation === 'string' && /^[a-zA-Z0-9-]{1,80}$/.test(index.generation);
    result.chunk_count = Number.isInteger(index.count) && index.count >= 1 && index.count <= 30 ? index.count : null;
    if (!result.generation_valid || result.chunk_count === null) return finish();
    result.stage = 'chunks';
    let encoded = '';
    for (let i = 0; i < result.chunk_count; i++) {
      const chunk = properties.getProperty(EC_BRIDGE.statePrefix + index.generation + '_' + i);
      if (typeof chunk !== 'string') continue;
      result.chunks_present++;
      encoded += chunk;
    }
    result.encoded_characters = encoded.length;
    if (result.chunks_present !== result.chunk_count) return finish();
    result.stage = 'base64';
    const bytes = Utilities.base64Decode(encoded);
    result.base64_success = true;
    result.base64_bytes = bytes.length;
    result.gzip_magic = bytes.length >= 2 && (bytes[0] & 255) === 31 && (bytes[1] & 255) === 139;
    result.stage = 'ungzip';
    try { Utilities.ungzip(Utilities.newBlob(bytes)).getDataAsString('UTF-8'); result.ungzip_default_success = true; } catch (_) { /* No raw exception output. */ }
    let text;
    try {
      text = Utilities.ungzip(Utilities.newBlob(bytes, 'application/x-gzip', 'booking-state.json.gz')).getDataAsString('UTF-8');
      result.ungzip_mime_success = true;
    } catch (_) { return finish(); }
    result.stage = 'json';
    const state = JSON.parse(text);
    result.json_parse = true;
    result.state_version = Number.isInteger(state.version) ? state.version : null;
    result.primary_rows = state.snapshot && state.snapshot.rows && typeof state.snapshot.rows === 'object' ? Object.keys(state.snapshot.rows).length : null;
    result.tabs = state.snapshot && state.snapshot.tabs && typeof state.snapshot.tabs === 'object' ? Object.keys(state.snapshot.tabs).length : null;
    result.outbox_events = Array.isArray(state.outbox) ? state.outbox.length : null;
    result.stage = 'complete';
    return finish();
  } catch (_) { return finish(); }
}

/* Snapshot digests contain no cell values; own append markers suppress only an exact new row. */
function ecCapture_(state, snapshot) {
  const previous = state.snapshot;
  /* A scope upgrade establishes the all-tab baseline silently, preserving pending callbacks. */
  if (!previous.tabs) { state.version = 2; state.snapshot = snapshot; return; }
  const affected = [];
  const changedIdentities = new Set();
  let globalRange = false;
  let inserted = 0;
  let edited = 0;
  let deleted = 0;
  const now = Math.floor(Date.now() / 1000);
  Object.keys(snapshot.rows).forEach(identity => {
    const current = snapshot.rows[identity];
    const old = previous.rows[identity];
    const expected = state.expected[identity];
    const ownAppend = !old && expected && expected.hash === current[0];
    if (!old && !ownAppend) { inserted++; affected.push(current[1]); changedIdentities.add('0:' + current[1]); }
    if (old && old[0] !== current[0]) { edited++; affected.push(current[1]); changedIdentities.add('0:' + current[1]); }
    if (expected) delete state.expected[identity];
  });
  Object.keys(previous.rows).forEach(identity => {
    if (!snapshot.rows[identity]) { deleted++; affected.push(previous.rows[identity][1]); changedIdentities.add('0:' + previous.rows[identity][1]); }
  });
  if (previous.header !== snapshot.header) { edited++; affected.push(1); changedIdentities.add('0:1'); }
  const ids = new Set(Object.keys(previous.tabs).concat(Object.keys(snapshot.tabs)));
  ids.forEach(id => {
    const old = previous.tabs[id];
    const current = snapshot.tabs[id];
    if (!old || !current) {
      if (current) inserted++; else deleted++;
      changedIdentities.add('tab:' + id);
      globalRange = true;
      return;
    }
    if (old.name !== current.name) {
      edited++;
      changedIdentities.add('tab:' + id);
      globalRange = true;
    }
    const rows = new Set(Object.keys(old.rows).concat(Object.keys(current.rows)));
    rows.forEach(row => {
      if (old.rows[row] === current.rows[row]) return;
      if (!old.rows[row]) inserted++; else if (!current.rows[row]) deleted++; else edited++;
      changedIdentities.add(id + ':' + row);
      globalRange = true;
    });
  });
  Object.keys(state.expected).forEach(identity => {
    if (state.expected[identity].expires < now) delete state.expected[identity];
  });
  if (changedIdentities.size) {
    if (state.outbox.length >= EC_BRIDGE.maxOutbox) ecFail_('outbox_full');
    const kinds = [inserted ? 'insert' : '', edited ? 'update' : '', deleted ? 'delete' : ''].filter(Boolean);
    state.outbox.push({
      event_id: Utilities.getUuid(),
      kind: kinds.length === 1 ? kinds[0] : 'mixed',
      changed_rows: changedIdentities.size,
      range: globalRange ? 'DATA' : 'A' + Math.min.apply(null, affected) + ':I' + Math.max.apply(null, affected),
      attempts: 0,
      next_attempt: 0,
      lease_until: 0
    });
  }
  state.snapshot = snapshot;
}

function ecFindRow_(sheet, key) {
  const keys = ecRaw_(sheet, 'A2:A');
  if (keys.length + 1 > EC_BRIDGE.maxRows) ecFail_('capacity');
  const matches = [];
  keys.forEach((row, index) => { if (row[0] === key) matches.push(index + 2); });
  if (matches.length > 1) ecFail_('conflict');
  return matches[0] || 0;
}

function ecVerifyRow_(sheet, row, values) {
  const range = sheet.getRange(row, 1, 1, 9);
  const raw = ecRaw_(sheet, 'A' + row + ':I' + row);
  const readback = Array.from({length: 9}, (_, column) => raw[0] && raw[0][column] !== undefined ? raw[0][column] : '');
  if (JSON.stringify(readback) !== JSON.stringify(values) || range.getFormulas()[0].some(Boolean)) ecFail_('conflict');
}

function ecAppend_(sheet, request) {
  if (typeof request.key !== 'string' || !/^[A-Za-z0-9:._-]{1,160}$/.test(request.key) || !Array.isArray(request.values) || request.values.length !== 9 || request.values.some(value => typeof value !== 'string' || value.length > 2000) || request.values[0] !== request.key) ecFail_('invalid_values');
  ecHeaders_(sheet);
  const snapshot = ecSnapshot_(sheet);
  const state = ecReadState_() || ecInitialState_(snapshot);
  let row = ecFindRow_(sheet, request.key);
  if (row) ecVerifyRow_(sheet, row, request.values);
  /* Durable before write: even a successful write with a lost response is excluded from scans. */
  if (!row) {
    const identity = ecIdentity_(request.key, 0);
    state.expected[identity] = {hash: ecRowHash_(request.values, Array(9).fill('')), expires: Math.floor(Date.now() / 1000) + 86400};
  }
  ecCapture_(state, snapshot);
  ecSaveState_(state);
  if (!row) {
    /* RAW is mandatory: strings beginning =, +, -, @ and leading zeroes stay literal. */
    Sheets.Spreadsheets.Values.append({values: [request.values]}, EC_BRIDGE.spreadsheetId, ecA1_(sheet, 'A1:I'), {valueInputOption: 'RAW', insertDataOption: 'INSERT_ROWS'});
    SpreadsheetApp.flush();
    row = ecFindRow_(sheet, request.key);
    if (!row) ecFail_('write_unconfirmed');
    ecVerifyRow_(sheet, row, request.values);
  }
  ecCapture_(state, ecSnapshot_(sheet));
  ecSaveState_(state);
  return {ok: true, spreadsheet_id: EC_BRIDGE.spreadsheetId, sheet_id: EC_BRIDGE.sheetId, key: request.key, row: row, fingerprint: ecHash_(JSON.stringify(request.values))};
}

function doPost(event) {
  let lock;
  let result;
  try {
    const request = ecRequest_(event);
    lock = LockService.getScriptLock();
    if (!lock.tryLock(3000)) ecFail_('busy');
    const sheet = ecSheet_();
    if (request.action === 'append') result = ecAppend_(sheet, request);
    else {
      ecHeaders_(sheet);
      result = {ok: true, spreadsheet_id: EC_BRIDGE.spreadsheetId, sheet_id: EC_BRIDGE.sheetId, key: '', row: 0};
    }
  } catch (error) {
    result = {ok: false, code: error && error.ecCode ? error.ecCode : 'unavailable'};
  } finally { if (lock && lock.hasLock()) lock.releaseLock(); }
  return ContentService.createTextOutput(JSON.stringify(result)).setMimeType(ContentService.MimeType.JSON);
}

/* Run once manually. Re-running preserves the baseline/outbox and unrelated project triggers. */
function setupBookingBridge() {
  ecSecret_();
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) ecFail_('busy');
  try {
    const sheet = ecSheet_();
    ecHeaders_(sheet);
    if (!ecReadState_()) ecSaveState_(ecInitialState_(ecSnapshot_(sheet)));
    const triggers = ScriptApp.getProjectTriggers();
    const exists = (handler, type, source) => triggers.some(trigger => trigger.getHandlerFunction() === handler && trigger.getEventType() === type && (!source || trigger.getTriggerSourceId() === source));
    if (!exists('bookingSheetEdited', ScriptApp.EventType.ON_EDIT, EC_BRIDGE.spreadsheetId)) ScriptApp.newTrigger('bookingSheetEdited').forSpreadsheet(EC_BRIDGE.spreadsheetId).onEdit().create();
    if (!exists('bookingSheetChanged', ScriptApp.EventType.ON_CHANGE, EC_BRIDGE.spreadsheetId)) ScriptApp.newTrigger('bookingSheetChanged').forSpreadsheet(EC_BRIDGE.spreadsheetId).onChange().create();
    if (!exists('bookingSheetScan', ScriptApp.EventType.CLOCK)) ScriptApp.newTrigger('bookingSheetScan').timeBased().everyMinutes(5).create();
  } finally { lock.releaseLock(); }
  return {ok: true, spreadsheet_id: EC_BRIDGE.spreadsheetId, sheet_id: EC_BRIDGE.sheetId};
}

function bookingSheetEdited(event) {
  if (!event || !event.source || event.source.getId() !== EC_BRIDGE.spreadsheetId || !event.range) return;
  bookingSheetScan();
}

function bookingSheetChanged(event) {
  if (!event || !event.source || event.source.getId() !== EC_BRIDGE.spreadsheetId || event.changeType === 'FORMAT') return;
  bookingSheetScan();
}

/* API / Apps Script writes do not invoke installable edit triggers, hence the timed scan. */
function bookingSheetScan() {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return;
  let selected = null;
  try {
    const snapshot = ecSnapshot_();
    const state = ecReadState_();
    if (!state) { ecSaveState_(ecInitialState_(snapshot)); return; }
    ecCapture_(state, snapshot);
    const now = Math.floor(Date.now() / 1000);
    selected = state.outbox.find(item => item.next_attempt <= now && item.lease_until <= now) || null;
    if (selected) selected.lease_until = now + 120;
    ecSaveState_(state);
  } finally { lock.releaseLock(); }
  if (selected) ecDeliverChange_(selected);
}

function ecDeliverChange_(event) {
  const payload = JSON.stringify({
    event_id: event.event_id,
    spreadsheet_id: EC_BRIDGE.spreadsheetId,
    sheet_id: EC_BRIDGE.sheetId,
    kind: event.kind,
    changed_rows: event.changed_rows,
    range: event.range,
    issued_at: Math.floor(Date.now() / 1000)
  });
  let acknowledged = false;
  try {
    const response = UrlFetchApp.fetch(EC_BRIDGE.callbackUrl, {
      method: 'post', contentType: 'application/json',
      payload: JSON.stringify({payload: payload, signature: ecSignature_(payload)}),
      followRedirects: false, validateHttpsCertificates: true, muteHttpExceptions: true
    });
    const code = response.getResponseCode();
    const body = JSON.parse(response.getContentText());
    acknowledged = code >= 200 && code < 300 && body && body.ok === true && body.event_id === event.event_id;
  } catch (_) { /* Retain the same event ID. Never log raw errors or response contents. */ }
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return;
  try {
    const state = ecReadState_();
    if (!state) return;
    const pending = state.outbox.find(item => item.event_id === event.event_id);
    if (!pending) return;
    if (acknowledged) state.outbox = state.outbox.filter(item => item.event_id !== event.event_id);
    else {
      pending.attempts++;
      pending.lease_until = 0;
      pending.next_attempt = Math.floor(Date.now() / 1000) + Math.min(3600, 60 * Math.pow(2, Math.min(pending.attempts - 1, 6)));
    }
    ecSaveState_(state);
  } finally { lock.releaseLock(); }
}
