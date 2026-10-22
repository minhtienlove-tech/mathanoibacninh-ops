import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createHash, createHmac, randomUUID} from 'node:crypto';
import {gzipSync, gunzipSync} from 'node:zlib';
import vm from 'node:vm';

const source = readFileSync(new URL('./Code.gs', import.meta.url), 'utf8');
const ID = '1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk';
const SECRET = createHash('sha256').update('fictional-in-memory-test-secret').digest('hex');
const HEADERS = ['Mã yêu cầu', 'Thời gian ghi nhận', 'Họ tên bệnh nhân', 'Số điện thoại', 'Ngày khám', 'Giờ khám', 'Trạng thái', 'Nguồn', 'Đồng ý lúc'];
const NOW = Math.floor(Date.now() / 1000);
let assertions = 0;
function check(condition, message) { assertions++; assert.ok(condition, message); }
function equal(actual, expected, message) { assertions++; assert.deepEqual(actual, expected, message); }

function harness(initialRows = []) {
  const state = {
    properties: new Map([['WEBHOOK_SECRET', SECRET]]), cells: [HEADERS.slice(), ...initialRows.map(row => row.slice())], formulas: {},
    appendCalls: [], fetchCalls: [], rawReads: [], triggers: [], locked: false, lockUnavailable: false,
    appendFailure: '', callbackMode: 'success', callbackDuringFetch: null, flushes: 0, propertyFailure: false,
    extraTabs: [], primaryName: "Lịch 'khám'", primaryPresent: true, diagnosticLogs: []
  };
  function makeSheet(id, data, name) {
    const sheet = {
      getSheetId: () => id, getName: name,
      getLastRow() { let last = data.cells.length; while (last > 1 && !data.cells[last - 1].some(value => value !== '')) last--; return last; },
      getLastColumn() { return data.cells.reduce((maximum, row) => { let length = row.length; while (length && row[length - 1] === '') length--; return Math.max(maximum, length); }, 0); },
      getRange(row, column, rows, columns) { return {
        getValues() { return Array.from({length: rows}, (_, r) => Array.from({length: columns}, (_, c) => data.cells[row + r - 1]?.[column + c - 1] ?? '')); },
        getFormulas() { return Array.from({length: rows}, (_, r) => Array.from({length: columns}, (_, c) => data.formulas[`${row + r}:${column + c}`] ?? '')); },
        getSheet() { return sheet; }
      }; }
    };
    return sheet;
  }
  const sheet = makeSheet(0, state, () => state.primaryName);
  const allTabs = () => (state.primaryPresent ? [{sheet, data: state}] : []).concat(state.extraTabs);
  const properties = {
    getProperty(key) { return state.properties.has(key) ? state.properties.get(key) : null; },
    setProperty(key, value) { if (state.propertyFailure) throw Error('fictional storage failure'); state.properties.set(key, value); },
    setProperties(values) { if (state.propertyFailure) throw Error('fictional storage failure'); Object.entries(values).forEach(([key, value]) => state.properties.set(key, value)); },
    getProperties() { return Object.fromEntries(state.properties); },
    deleteProperty(key) { state.properties.delete(key); }
  };
  const blob = (value, contentType = null, name = null) => {
    const bytes = typeof value === 'string' ? Buffer.from(value) : Buffer.from(value);
    return {getBytes: () => [...bytes], getDataAsString: () => bytes.toString('utf8'), getContentType: () => contentType, getName: () => name};
  };
  const makeTrigger = (handler, type, sourceId) => ({getHandlerFunction: () => handler, getEventType: () => type, getTriggerSourceId: () => sourceId});
  const context = vm.createContext({
    Date, console: {log(value) { state.diagnosticLogs.push(value); }},
    PropertiesService: {getScriptProperties: () => properties},
    Utilities: {
      DigestAlgorithm: {SHA_256: 'sha256'}, Charset: {UTF_8: 'utf8'},
      computeDigest: (algorithm, value) => [...createHash(algorithm).update(value).digest()],
      computeHmacSha256Signature: (value, secret) => [...createHmac('sha256', secret).update(value).digest()],
      base64Encode: value => Buffer.from(value).toString('base64'), base64Decode: value => [...Buffer.from(value, 'base64')],
      newBlob: blob,
      gzip: (value, name) => blob(gzipSync(Buffer.from(value.getBytes())), 'application/x-gzip', name || 'blob.gz'),
      ungzip(value) {
        if (value.getContentType() !== 'application/x-gzip') throw Error('Invalid argument: gzip MIME metadata required');
        return blob(gunzipSync(Buffer.from(value.getBytes())), 'application/json', 'booking-state.json');
      },
      getUuid: randomUUID
    },
    SpreadsheetApp: {
      openById(id) { assert.equal(id, ID); return {getSheets: () => allTabs().map(tab => tab.sheet)}; },
      flush() { state.flushes++; }
    },
    Sheets: {Spreadsheets: {Values: {
      get(id, a1, options) {
        assert.equal(id, ID); assert.equal(options.valueRenderOption, 'UNFORMATTED_VALUE'); assert.equal(options.dateTimeRenderOption, 'SERIAL_NUMBER');
        state.rawReads.push({a1, options});
        const tab = allTabs().find(item => {
          const quoted = "'" + item.sheet.getName().replace(/'/g, "''") + "'";
          return a1 === quoted || a1.startsWith(quoted + '!');
        });
        assert.ok(tab, 'Read targets an existing tab in fixed spreadsheet');
        const quoted = "'" + tab.sheet.getName().replace(/'/g, "''") + "'";
        const address = a1 === quoted ? '' : a1.slice(quoted.length + 1);
        const parts = address ? address.match(/^A(\d+):([AI])(\d+)?$/) : null;
        if (address) assert.ok(parts, 'Expected fixed primary read range');
        const start = parts ? Number(parts[1]) : 1; const end = parts?.[3] ? Number(parts[3]) : tab.data.cells.length; const width = parts ? parts[2] === 'A' ? 1 : 9 : Infinity;
        const selected = tab.data.cells.slice(start - 1, end).map(row => row.slice(0, width));
        while (selected.length && !selected.at(-1).some(value => value !== '')) selected.pop();
        return {values: selected};
      },
      append(body, id, a1, options) {
        assert.equal(id, ID); assert.equal(options.valueInputOption, 'RAW'); assert.equal(options.insertDataOption, 'INSERT_ROWS');
        if (state.appendFailure === 'before') { state.appendFailure = ''; throw Error('fictional failure before write'); }
        state.appendCalls.push({body: JSON.parse(JSON.stringify(body)), id, a1, options: {...options}});
        state.cells.push([...body.values[0]]);
        if (state.appendFailure === 'after') { state.appendFailure = ''; throw Error('fictional response lost after write'); }
        return {updates: {updatedRows: 1, updatedRange: a1}};
      }
    }}},
    LockService: {getScriptLock() {
      let owns = false;
      return {tryLock() { if (state.lockUnavailable || state.locked) return false; state.locked = owns = true; return true; }, hasLock: () => owns, releaseLock() { if (owns) state.locked = false; owns = false; }};
    }},
    ContentService: {MimeType: {JSON: 'application/json'}, createTextOutput: text => ({text, setMimeType() { return this; }})},
    ScriptApp: {
      EventType: {ON_EDIT: 'ON_EDIT', ON_CHANGE: 'ON_CHANGE', CLOCK: 'CLOCK'},
      getProjectTriggers: () => state.triggers.slice(),
      newTrigger(handler) {
        let type = ''; let sourceId = '';
        const builder = {
          forSpreadsheet(id) { sourceId = id; return builder; }, onEdit() { type = 'ON_EDIT'; return builder; }, onChange() { type = 'ON_CHANGE'; return builder; },
          timeBased() { type = 'CLOCK'; return builder; }, everyMinutes(minutes) { assert.equal(minutes, 5); return builder; },
          create() { const trigger = makeTrigger(handler, type, sourceId); state.triggers.push(trigger); return trigger; }
        };
        return builder;
      }
    },
    UrlFetchApp: {fetch(url, options) {
      state.fetchCalls.push({url, options});
      if (state.callbackDuringFetch) state.callbackDuringFetch();
      if (state.callbackMode === 'throw') throw Error('fictional uncertain HTTP error');
      const envelope = JSON.parse(options.payload); const payload = JSON.parse(envelope.payload);
      const status = state.callbackMode === 'http500' ? 500 : 200;
      const body = state.callbackMode === 'malformed' ? 'not-json' : JSON.stringify({ok: true, event_id: state.callbackMode === 'wrong_id' ? 'wrong' : payload.event_id});
      return {getResponseCode: () => status, getContentText: () => body};
    }}
  });
  vm.runInContext(source, context, {filename: 'Code.gs'});
  return {
    state, context, sheet, makeTrigger,
    addTab(id, name, rows = []) {
      const data = {name, cells: rows.map(row => row.slice()), formulas: {}};
      const added = {data, sheet: makeSheet(id, data, () => data.name)};
      state.extraTabs.push(added);
      return added;
    },
    run: expression => vm.runInContext(expression, context),
    setup: () => JSON.parse(JSON.stringify(context.setupBookingBridge())),
    scan: () => context.bookingSheetScan(),
    readState: () => JSON.parse(JSON.stringify(context.ecReadState_())),
    saveState(value) { context.ecSaveState_(value); },
    post(data, signatureOverride) {
      const payload = JSON.stringify({issued_at: NOW, spreadsheet_id: ID, sheet_id: 0, ...data});
      const signature = signatureOverride ?? createHmac('sha256', SECRET).update(payload).digest('hex');
      return JSON.parse(context.doPost({postData: {contents: JSON.stringify({payload, signature})}}).text);
    }
  };
}

const values = key => [key, '2026-10-19T09:30:00+07:00', 'Nguyễn Văn Kiểm Thử', '0912345678', '2026-10-20', '16:00', 'Chờ xác nhận', 'Website', '2026-10-19T09:30:00+07:00'];
const h = harness([values('historic-1')]);
h.state.triggers.push(h.makeTrigger('unrelatedSync', 'CLOCK', ''), h.makeTrigger('bookingSheetEdited', 'ON_EDIT', 'another-document'));
equal(h.setup(), {ok: true, spreadsheet_id: ID, sheet_id: 0}, 'Setup identifies the fixed destination');
h.setup();
equal(h.state.triggers.length, 5, 'Setup adds three correct triggers once and preserves unrelated triggers');
equal(h.readState().outbox.length, 0, 'Initial baseline never notifies historical rows');
h.scan();
equal(h.state.fetchCalls.length, 0, 'An unchanged first scan is quiet');
equal(h.post({action: 'probe'}).ok, true, 'Signed probe verifies document and headers');
equal(h.post({action: 'probe'}, '0'.repeat(64)).code, 'unauthorized', 'Bad HMAC rejected');
equal(h.post({action: 'probe', issued_at: NOW - 301}).code, 'expired', 'Stale signed request rejected');
equal(h.post({action: 'probe', issued_at: NOW + 301}).code, 'expired', 'Far-future signed request rejected');
equal(h.post({action: 'probe', sheet_id: 9}).code, 'destination', 'Different tab rejected');
equal(h.post({action: 'probe', spreadsheet_id: 'wrong'}).code, 'destination', 'Different spreadsheet rejected');
equal(h.post({action: 'send'}).code, 'destination', 'Unsupported action rejected');
equal(h.post({action: 'append', key: 'valid', values: ['invalid']}).code, 'invalid_values', 'Exactly nine bounded strings required');
equal(h.post({action: 'append', key: 'different', values: values('wrong')}).code, 'invalid_values', 'Key must match column A');
const tooLong = values('length'); tooLong[2] = 'x'.repeat(2001);
equal(h.post({action: 'append', key: 'length', values: tooLong}).code, 'invalid_values', 'Oversized field rejected');
h.state.lockUnavailable = true;
equal(h.post({action: 'append', key: 'busy', values: values('busy')}).code, 'busy', 'Concurrent append fails closed');
h.state.lockUnavailable = false;

const literal = values('new-1'); literal[2] = '=HYPERLINK("https://example.test")'; literal[3] = '00123456789'; literal[7] = '+SUM(1,1)'; literal[8] = "'literal";
const written = h.post({action: 'append', key: 'new-1', values: literal});
equal(written.row, 3, 'Append returns the confirmed row');
equal(written.fingerprint, createHash('sha256').update(JSON.stringify(literal)).digest('hex'), 'Acknowledgment binds exact row values');
equal(h.state.cells[2], literal, 'Leading zeroes, apostrophe and formula-looking values remain exact strings');
equal(h.state.appendCalls[0].options.valueInputOption, 'RAW', 'Sheets API is explicitly RAW');
check(h.state.appendCalls[0].a1.startsWith("'Lịch ''khám'''!"), 'Sheet title is safely quoted');
equal(h.state.fetchCalls.length, 0, 'Append never calls the staff callback directly');
equal(h.readState().outbox.length, 0, 'Own append produces no duplicate generic change event');
h.scan();
equal(h.state.fetchCalls.length, 0, 'Timed scan stays quiet after own append');
equal(h.post({action: 'append', key: 'new-1', values: literal}).row, 3, 'Identical retry returns existing row');
equal(h.state.appendCalls.length, 1, 'Identical retry never appends twice');
const changed = literal.slice(); changed[6] = 'Đã xác nhận';
equal(h.post({action: 'append', key: 'new-1', values: changed}).code, 'conflict', 'Conflicting duplicate never overwrites existing record');
equal(h.state.cells[2], literal, 'Conflict leaves the original row intact');
const formulaHostile = harness(); formulaHostile.setup();
formulaHostile.state.cells.push(values('formula-key')); formulaHostile.state.formulas['2:3'] = '=1';
equal(formulaHostile.post({action: 'append', key: 'formula-key', values: values('formula-key')}).code, 'conflict', 'Existing formula cells cannot masquerade as matching raw text');

const lost = harness(); lost.setup(); lost.state.appendFailure = 'after';
equal(lost.post({action: 'append', key: 'lost-1', values: values('lost-1')}).ok, false, 'Lost acknowledgment never reports append success');
equal(lost.state.cells.length, 2, 'Simulated provider committed the row before failure');
lost.scan();
equal(lost.state.fetchCalls.length, 0, 'Durable expected-row marker suppresses post-timeout duplicate change notification');
equal(lost.post({action: 'append', key: 'lost-1', values: values('lost-1')}).row, 2, 'Retry resolves lost acknowledgment by exact key');
equal(lost.state.appendCalls.length, 1, 'Timeout retry still has one row');
const preWrite = harness(); preWrite.setup(); preWrite.state.appendFailure = 'before';
equal(preWrite.post({action: 'append', key: 'failed-1', values: values('failed-1')}).ok, false, 'Write failure remains unconfirmed');
equal(preWrite.state.cells.length, 1, 'Failed write creates no row');
preWrite.scan();
equal(preWrite.state.fetchCalls.length, 0, 'Failed own append has no generic notification');
equal(preWrite.post({action: 'append', key: 'failed-1', values: values('failed-1')}).row, 2, 'Safe retry after definite failure succeeds');

/* Unrelated edits immediately before a form append must not be swallowed by baseline refresh. */
h.state.cells[1][6] = 'Đã gọi xác nhận';
const beforeAppendFetch = h.state.fetchCalls.length;
h.post({action: 'append', key: 'new-2', values: values('new-2')});
equal(h.state.fetchCalls.length, beforeAppendFetch, 'Append only queues an unrelated change for later delivery');
equal(h.readState().outbox.length, 1, 'Unrelated pre-append edit is retained');
h.scan();
equal(h.state.fetchCalls.length, beforeAppendFetch + 1, 'Later scan delivers the unrelated edit once');
const call = h.state.fetchCalls.at(-1); const envelope = JSON.parse(call.options.payload); const event = JSON.parse(envelope.payload);
equal(call.url, 'https://mathanoibacninh.com/wp-json/ec-booking/v1/sheet-change', 'Callback destination is fixed');
equal(envelope.signature, createHmac('sha256', SECRET).update(envelope.payload).digest('hex'), 'Callback signs the exact payload');
equal(Object.keys(event).sort(), ['booking_rows', 'changed_rows', 'event_id', 'issued_at', 'kind', 'range', 'sheet_id', 'spreadsheet_id'].sort(), 'Callback contains metadata and bounded primary booking rows');
equal(event.changed_rows, 1, 'Own append is excluded while unrelated edit is counted');
equal(event.kind, 'update', 'Meaningful edit classified correctly');
equal(event.range, 'A2:I2', 'Changed range has no patient values');
equal(event.booking_rows[0].row, 2, 'Callback identifies the changed primary row');
equal(event.booking_rows[0].values, h.state.cells[1].slice(0, 9), 'Signed callback carries the current exact nine booking fields');
check(call.options.followRedirects === false && call.options.validateHttpsCertificates === true, 'Callback verifies TLS and refuses redirect destinations');
equal(h.readState().outbox.length, 0, 'Only confirmed callback acknowledgment removes outbox entry');
h.scan();
equal(h.state.fetchCalls.length, beforeAppendFetch + 1, 'Repeated trigger for same state does not duplicate the event');

const retry = harness([values('manual-1')]); retry.setup(); retry.state.cells[1][6] = 'Đã đổi'; retry.state.callbackMode = 'wrong_id'; retry.scan();
const firstPending = retry.readState().outbox[0];
check(firstPending && firstPending.attempts === 1, 'Wrong acknowledgment retains durable retry');
const firstEvent = JSON.parse(JSON.parse(retry.state.fetchCalls[0].options.payload).payload);
retry.scan(); equal(retry.state.fetchCalls.length, 1, 'Backoff prevents tight retry loops');
for (const mode of ['throw', 'http500', 'malformed']) {
  const next = retry.readState(); next.outbox[0].next_attempt = 0; retry.saveState(next); retry.state.callbackMode = mode; retry.scan();
  equal(retry.readState().outbox[0].event_id, firstPending.event_id, 'Uncertain callback preserves event ID: ' + mode);
}
const next = retry.readState(); next.outbox[0].next_attempt = 0; retry.saveState(next); retry.state.callbackMode = 'success'; retry.scan();
equal(retry.readState().outbox.length, 0, 'Later confirmed callback clears the retained event');
equal(JSON.parse(JSON.parse(retry.state.fetchCalls.at(-1).options.payload).payload).event_id, firstEvent.event_id, 'All retries use one stable deduplication ID');

/* A second scan during callback delivery must see the durable lease. */
const concurrent = harness([values('concurrent')]); concurrent.setup(); concurrent.state.cells[1][6] = 'edited';
concurrent.state.callbackDuringFetch = () => concurrent.scan(); concurrent.scan();
equal(concurrent.state.fetchCalls.length, 1, 'Concurrent timer/edit delivery shares a durable lease');

const deletion = harness([values('row-a'), values('row-b')]); deletion.setup(); deletion.state.cells.splice(1, 1); deletion.scan();
const deletedEvent = JSON.parse(JSON.parse(deletion.state.fetchCalls[0].options.payload).payload);
equal(deletedEvent.kind, 'delete', 'Deleted keyed row detected'); equal(deletedEvent.changed_rows, 1, 'Row movement after deletion does not falsely mark remaining keyed rows');
deletion.state.cells.reverse(); deletion.state.cells.reverse(); deletion.scan();
equal(deletion.state.fetchCalls.length, 1, 'Unchanged underlying values are quiet');
const reorder = harness([values('r-a'), values('r-b')]); reorder.setup(); [reorder.state.cells[1], reorder.state.cells[2]] = [reorder.state.cells[2], reorder.state.cells[1]]; reorder.scan();
equal(reorder.state.fetchCalls.length, 0, 'Reordering keyed rows does not notify as content edits');
const renameKey = harness([values('old-key')]); renameKey.setup(); renameKey.state.cells[1][0] = 'new-key'; renameKey.scan();
equal(JSON.parse(JSON.parse(renameKey.state.fetchCalls[0].options.payload).payload).changed_rows, 1, 'Replacing a key counts the actual row once');
const filter = harness(); filter.setup();
filter.context.bookingSheetEdited({source: {getId: () => 'other'}, range: {getSheet: () => ({getSheetId: () => 0})}});
filter.context.bookingSheetEdited({source: {getId: () => ID}, range: {getSheet: () => ({getSheetId: () => 99})}});
filter.context.bookingSheetChanged({source: {getId: () => ID}, changeType: 'FORMAT'});
equal(filter.state.fetchCalls.length, 0, 'Other documents and formatting-only trigger do nothing');
filter.state.cells.push(values('api-row'));
filter.scan();
equal(filter.state.fetchCalls.length, 1, 'Timed scan discovers a write made through another API');

const callbackPayload = fixture => JSON.parse(JSON.parse(fixture.state.fetchCalls.at(-1).options.payload).payload);
const wholeFile = harness([values('all-file')]);
const secondary = wholeFile.addTab(7, 'Staff-only table', [['Column', 'Other'], ['value', 'before']]);
wholeFile.setup(); wholeFile.scan();
equal(wholeFile.state.fetchCalls.length, 0, 'Initial baseline includes other tabs without historical notifications');
wholeFile.state.cells[1][9] = 'J-column changed';
wholeFile.scan();
equal(callbackPayload(wholeFile).range, 'DATA', 'J-column edit uses generic DATA range');
equal(callbackPayload(wholeFile).changed_rows, 1, 'J-column edit counts one row');
equal(callbackPayload(wholeFile).kind, 'insert', 'First populated J cell is a meaningful insertion');
secondary.data.cells[1][1] = 'after';
wholeFile.context.bookingSheetEdited({source: {getId: () => ID}, range: {getSheet: () => secondary.sheet}});
equal(callbackPayload(wholeFile).kind, 'update', 'Installable edit trigger monitors a second tab');
equal(callbackPayload(wholeFile).range, 'DATA', 'Second-tab edit has no identifying cell content or tab name');
check(!wholeFile.state.fetchCalls.at(-1).options.payload.includes('Staff-only table') && !wholeFile.state.fetchCalls.at(-1).options.payload.includes('after'), 'All-tab callback stays metadata-only');
const newTab = wholeFile.addTab(23, 'New empty tab');
wholeFile.context.bookingSheetChanged({source: {getId: () => ID}, changeType: 'INSERT_GRID'});
equal(callbackPayload(wholeFile).kind, 'insert', 'Even an empty new tab is a meaningful insertion');
equal(callbackPayload(wholeFile).changed_rows, 1, 'Empty-tab structural change has a positive bounded count');
equal(callbackPayload(wholeFile).range, 'DATA', 'New-tab event uses DATA');
newTab.data.name = 'Renamed tab';
wholeFile.context.bookingSheetChanged({source: {getId: () => ID}, changeType: 'OTHER'});
equal(callbackPayload(wholeFile).kind, 'update', 'Tab name change is meaningful');
equal(callbackPayload(wholeFile).range, 'DATA', 'Tab rename does not expose the tab name');
wholeFile.state.extraTabs = wholeFile.state.extraTabs.filter(tab => tab !== newTab);
wholeFile.context.bookingSheetChanged({source: {getId: () => ID}, changeType: 'REMOVE_GRID'});
equal(callbackPayload(wholeFile).kind, 'delete', 'Tab deletion is meaningful');
equal(callbackPayload(wholeFile).range, 'DATA', 'Tab deletion uses DATA');
const beforeOwnAppend = wholeFile.state.fetchCalls.length;
wholeFile.state.cells[1][9] = 'Unrelated J edit before own append';
equal(wholeFile.post({action: 'append', key: 'all-file-new', values: values('all-file-new')}).ok, true, 'Own append still writes only the primary nine-column table');
equal(wholeFile.state.fetchCalls.length, beforeOwnAppend, 'Own append does not directly notify unrelated file edits');
equal(wholeFile.readState().outbox.length, 1, 'All-tab baseline update retains unrelated J edit');
wholeFile.scan();
equal(callbackPayload(wholeFile).range, 'DATA', 'Own-append suppression preserves the unrelated J event');
equal(callbackPayload(wholeFile).changed_rows, 1, 'Own appended row is excluded from file-change count');
const afterOwnAppend = wholeFile.state.fetchCalls.length;
wholeFile.scan();
equal(wholeFile.state.fetchCalls.length, afterOwnAppend, 'No delayed duplicate after own append and unrelated edit');
wholeFile.state.primaryName = 'Renamed primary'; wholeFile.scan();
equal(callbackPayload(wholeFile).range, 'DATA', 'Primary tab rename also monitored');
equal(callbackPayload(wholeFile).kind, 'update', 'Primary tab rename has update kind');
const beforeFormat = wholeFile.state.fetchCalls.length;
wholeFile.context.bookingSheetChanged({source: {getId: () => ID}, changeType: 'FORMAT'}); wholeFile.scan();
equal(wholeFile.state.fetchCalls.length, beforeFormat, 'Formatting-only events and later time scan remain silent');
const formulaChange = harness([values('formula-change')]);
formulaChange.state.cells[1][9] = 2; formulaChange.state.formulas['2:10'] = '=1+1'; formulaChange.setup();
formulaChange.state.formulas['2:10'] = '=2'; formulaChange.scan();
equal(callbackPayload(formulaChange).kind, 'update', 'Formula edit outside A:I is detected even with unchanged calculated value');
const deletedPrimary = harness([values('deleted-primary')]); deletedPrimary.addTab(9, 'Other', [['still here']]); deletedPrimary.setup(); deletedPrimary.state.primaryPresent = false; deletedPrimary.scan();
equal(callbackPayload(deletedPrimary).range, 'DATA', 'Deleting primary tab still produces a file-change callback');
equal(deletedPrimary.post({action: 'append', key: 'no-primary', values: values('no-primary')}).code, 'sheet_missing', 'Deleting primary tab does not redirect writes to another tab');
const migration = harness([values('legacy-baseline')]); migration.setup();
const legacy = migration.readState(); legacy.version = 1; delete legacy.snapshot.tabs;
legacy.outbox.push({event_id: 'keep-existing-event', kind: 'update', changed_rows: 1, range: 'A2:I2', attempts: 0, next_attempt: NOW + 10000, lease_until: 0});
migration.saveState(legacy); migration.addTab(14, 'Historical second tab', [['historical']]); migration.scan();
equal(migration.readState().version, 2, 'Older primary-only baseline migrates to whole-file state');
equal(migration.readState().outbox.length, 1, 'Scope upgrade preserves pending callbacks without historical notification flood');
equal(migration.readState().outbox[0].event_id, 'keep-existing-event', 'Scope upgrade preserves the original pending event ID');
equal(migration.state.fetchCalls.length, 0, 'Scope migration is silent for historical nonprimary data');
const cellLimit = harness(); cellLimit.addTab(1, 'Too wide', [Array(180001).fill('x')]);
assertions++; assert.throws(() => cellLimit.setup(), error => error.ecCode === 'capacity', 'Whole-file populated-cell bound fails visibly');
const rowLimit = harness(); rowLimit.addTab(1, 'Too many rows', Array.from({length: 20000}, () => ['x']));
assertions++; assert.throws(() => rowLimit.setup(), error => error.ecCode === 'capacity', 'Row bound applies across tabs');

const headers = harness(); headers.setup(); headers.state.cells[0][0] = 'Wrong header';
equal(headers.post({action: 'append', key: 'header', values: values('header')}).code, 'headers_changed', 'Append refuses unexpected column structure');
headers.scan();
equal(JSON.parse(JSON.parse(headers.state.fetchCalls[0].options.payload).payload).range, 'A1:I1', 'Header change is reported without contents');
const secret = harness(); secret.state.properties.delete('WEBHOOK_SECRET');
equal(secret.post({action: 'probe'}).code, 'not_configured', 'Missing secret fails closed');
check(!JSON.stringify(secret.post({action: 'probe'})).includes(SECRET), 'Failure response contains no secret');
for (const invalidSecret of ['x'.repeat(64), SECRET.toUpperCase(), SECRET.slice(1), SECRET + '0', 'short']) {
  secret.state.properties.set('WEBHOOK_SECRET', invalidSecret);
  equal(secret.post({action: 'probe'}).code, 'not_configured', 'Secret must be exactly 64 lowercase hexadecimal characters');
}
const duplicate = harness([values('dup'), values('dup')]); duplicate.setup();
equal(duplicate.post({action: 'append', key: 'dup', values: values('dup')}).code, 'conflict', 'Duplicate existing keys are rejected instead of choosing an arbitrary row');
const storage = harness(); storage.setup(); storage.state.propertyFailure = true;
equal(storage.post({action: 'append', key: 'no-write', values: values('no-write')}).ok, false, 'State storage failure aborts before external append');
equal(storage.state.appendCalls.length, 0, 'No append without durable expected-row marker');
check(h.state.properties.get('WEBHOOK_SECRET') === SECRET, 'State cleanup preserves the configured secret');
check([...h.state.properties.keys()].filter(key => key.startsWith('EC_BOOKING_STATE_') && key !== 'EC_BOOKING_STATE_INDEX').every(key => key.includes(JSON.parse(h.state.properties.get('EC_BOOKING_STATE_INDEX')).generation)), 'State commits clean only obsolete own chunks');
const fingerprintFixture = JSON.parse(readFileSync(new URL('./fingerprint-fixture.json', import.meta.url), 'utf8'));
const fingerprint = harness(); fingerprint.setup();
// This reference was independently matched against PHP 8.2 using the flags in README.
const phpReferenceFingerprint = 'ef5ed10e8166dd49351d0631c83eaa1efcfd69fd2c2605053b1a2d9a86205482';
equal(createHash('sha256').update(JSON.stringify(fingerprintFixture)).digest('hex'), phpReferenceFingerprint, 'JSON.stringify fixture matches the independently verified PHP serialization');
equal(fingerprint.post({action: 'append', key: fingerprintFixture[0], values: fingerprintFixture}).fingerprint, phpReferenceFingerprint, 'Fingerprint covers Unicode, slashes, controls, apostrophes and Unicode line separators');
equal(h.state.diagnosticLogs.length, 0, 'Normal receiver and worker execution do not log anything');
const diagnostics = JSON.parse(JSON.stringify(h.context.diagnoseBookingBridge()));
equal(diagnostics.stage, 'complete', 'Read-only state diagnostic reaches the parsed state');
check(diagnostics.index_present && diagnostics.index_parse && diagnostics.generation_valid && diagnostics.chunks_present === diagnostics.chunk_count, 'Diagnostic confirms state index and generation chunks without exposing them');
check(diagnostics.base64_success && diagnostics.base64_bytes > 0 && diagnostics.gzip_magic, 'Diagnostic reports valid gzip bytes as counts and flags only');
equal(diagnostics.ungzip_default_success, false, 'Strict Apps Script mock reproduces missing-MIME ungzip failure');
equal(diagnostics.ungzip_mime_success, true, 'Explicit application/x-gzip blob metadata fixes state decompression');
equal(diagnostics.state_version, 2, 'Diagnostic reports only numeric state version');
equal(diagnostics.primary_rows, 3, 'Diagnostic reports aggregate primary row count');
const diagnosticText = h.state.diagnosticLogs.at(-1);
check(!diagnosticText.includes(SECRET) && !diagnosticText.includes('Nguyễn') && !diagnosticText.includes('0912345678') && !diagnosticText.includes('generation":') && !diagnosticText.includes('fingerprint'), 'Diagnostic output contains no secret, record, hash or property value');
const noStateDiagnostic = harness();
equal(noStateDiagnostic.context.diagnoseBookingBridge().stage, 'no_state', 'Missing baseline diagnostic is safe');
noStateDiagnostic.state.properties.set('EC_BOOKING_STATE_INDEX', '{invalid-' + SECRET);
const badIndexDiagnostic = noStateDiagnostic.context.diagnoseBookingBridge();
equal(badIndexDiagnostic.stage, 'index', 'Invalid index diagnostic stops at its safe stage');
check(!noStateDiagnostic.state.diagnosticLogs.at(-1).includes(SECRET), 'Raw parsing errors/property data never reach diagnostic output');
const detail = harness([values('zalo-detail')]); detail.setup(); detail.state.cells[1][7] = 'Zalo'; detail.state.callbackMode = 'wrong_id'; detail.scan();
equal(callbackPayload(detail).booking_rows.length, 1, 'External Zalo row update carries booking details');
check(!JSON.stringify(detail.readState()).includes('Nguyễn') && !JSON.stringify(detail.readState()).includes('0912345678'), 'Durable baseline and outbox never retain patient values');
const detailEvent = JSON.parse(JSON.stringify(detail.readState().outbox[0]));
detail.state.cells[1][2] = 'A different row after concurrent edit';
equal(detail.context.ecChangeRows_(detailEvent).length, 0, 'A changed/reordered row cannot supply details for an older event');
const batchDetails = harness(); batchDetails.setup();
for (let i = 0; i < 5; i++) batchDetails.state.cells.push(values('batch-' + i));
batchDetails.scan();
equal(callbackPayload(batchDetails).booking_rows.length, 3, 'Large edits bound each notification to three primary rows');
equal(batchDetails.readState().outbox.length, 1, 'Remaining rows stay durably queued instead of being dropped');
batchDetails.scan();
equal(callbackPayload(batchDetails).booking_rows.length, 2, 'Next scan sends details of the remaining changed rows');
const formulaDetails = harness([values('formula-detail')]); formulaDetails.setup(); formulaDetails.state.cells[1][2] = 'Formula result'; formulaDetails.state.formulas['2:3'] = '=SECRET()'; formulaDetails.scan();
equal(callbackPayload(formulaDetails).booking_rows.length, 0, 'Formula-backed rows remain metadata-only');
console.log(`PASS: ${assertions} Apps Script bridge, row-detail, retry and privacy checks; no network or Google access.`);
