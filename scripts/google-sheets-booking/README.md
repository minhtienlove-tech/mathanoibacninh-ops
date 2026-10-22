# Google Sheets booking bridge

This source implements the Google Apps Script bridge deployed on 19 October 2026; deployment and verification are recorded in `docs/CHANGELOG.md`. Source edits require updating the existing web-app deployment. It only writes bookings to columns A:I of spreadsheet `1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk`, tab ID `0`. Its change monitor covers **every populated column and every tab in that file**, including tab insertion, deletion and renaming. It only calls `https://mathanoibacninh.com/wp-json/ec-booking/v1/sheet-change`.

The nine existing columns A:I must be:

1. Mã yêu cầu
2. Thời gian ghi nhận
3. Họ tên bệnh nhân
4. Số điện thoại
5. Ngày khám
6. Giờ khám
7. Trạng thái
8. Nguồn
9. Đồng ý lúc

## Setup

1. Create an Apps Script project under the Google account authorized to edit that spreadsheet. Copy `Code.gs`; show `appsscript.json` in project settings and use the supplied manifest.
2. The manifest declares the **Google Sheets API v4 advanced service**. Check it appears under Services. If this project uses a manually managed Google Cloud project, also enable Google Sheets API there. No service-account key or Google OAuth token is placed in WordPress.
3. In **Project Settings → Script Properties**, set `WEBHOOK_SECRET` to a newly generated random **64-character lowercase hexadecimal** secret (32 random bytes encoded as hex). Configure exactly the same secret privately on WordPress. Never put the value in this source, a URL, browser JavaScript or a Sheet cell.
4. Run `setupBookingBridge()` manually as the owner and authorize the scopes. It creates a baseline without notifying any historical rows, then installs one on-edit, one on-change and one five-minute scan trigger. Re-running preserves the baseline/outbox, existing bridge triggers and unrelated project triggers. Do not use multiple owners to install this project: project-trigger visibility is per installing account.
5. Deploy as a **Web app**, execute as the authorized owner, and allow the anonymous HTTPS caller needed for the server webhook. The signed request body supplies authentication; no public `doGet` data endpoint exists. Save the deployment `/exec` URL privately in WordPress. Keep spreadsheet sharing restricted to intended staff.
6. WordPress must implement the fixed callback endpoint before relying on change notifications. Deploy the web app only after reviewing the PHP side of the integration. Source edits require updating the deployed web-app version.

Scopes are spreadsheet access, installable trigger management and external requests. The code's spreadsheet and callback destinations are constants; HTTP payloads cannot override them. The owner must retain spreadsheet edit permission.

## Signed request and acknowledgment

POST JSON with a `payload` **string** and `signature`, the lowercase hexadecimal HMAC-SHA256 of the exact UTF-8 payload string with `WEBHOOK_SECRET`. Do not sign a reserialized version of the string. The signed payload includes an integer Unix `issued_at` within 300 seconds of server time.

Append payload:

```json
{
  "action": "append",
  "issued_at": 1780000000,
  "spreadsheet_id": "1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk",
  "sheet_id": 0,
  "key": "example-site:123",
  "values": ["example-site:123", "2026-10-19T09:30:00+07:00", "Fictional test name", "0912345678", "2026-10-20", "16:00", "Chờ xác nhận", "Website", "2026-10-19T09:30:00+07:00"]
}
```

`values` must contain exactly nine strings of at most 2,000 characters each; `values[0]` must equal the stable key. A key contains 1–160 ASCII letters, digits, colons, dots, underscores or hyphens. Use fictional values for tests. The timestamp above is illustrative and must be replaced with current Unix time.

A `probe` uses the same destination/time fields with `action: "probe"`; it verifies access and header identity without appending or installing triggers.

Confirmed append response:

```json
{"ok":true,"spreadsheet_id":"1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk","sheet_id":0,"key":"example-site:123","row":2,"fingerprint":"sha256-of-json-nine-values"}
```

Probe response has `key: ""` and `row: 0`. Failure returns only `{"ok":false,"code":"..."}`; Apps Script ContentService does not set custom HTTP error codes, so WordPress must validate the body even on HTTP 200. ContentService responses may use Google's response-serving redirect: the PHP receiver must handle Google's trusted HTTPS response location without forwarding the signed POST to arbitrary destinations. A generic HTTP 200 alone is never proof of a written row.

The append `fingerprint` is SHA-256 of the UTF-8 `JSON.stringify(values)` string. For these nine **string** fields, PHP matches this serialization with `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS` (reject invalid UTF-8 instead of substituting). `fingerprint-fixture.json` includes Vietnamese, emoji, slash/backslash, controls and Unicode line separators; its independently verified PHP 8.2 / JavaScript reference digest is `ef5ed10e8166dd49351d0631c83eaa1efcfd69fd2c2605053b1a2d9a86205482`.

Append serializes bridge writers with ScriptLock, checks duplicate keys/conflicting data, uses **Sheets API RAW** to keep all nine values as literal strings, flushes and reads back the key, all fields and absence of formulas. RAW avoids the formula interpretation and apostrophe-readback issues of `Range.setValues()`. Phone numbers retain leading zeroes. No callback or staff notification is sent directly by append; WordPress can release its existing staff queues after the confirmed response.

## Booking details in change notifications (22 October 2026)

External bookings written by Zalo were previously reported only as a generic Sheet-change alert. The change monitor now includes validated primary A:I booking details in the signed callback. WordPress encrypts the rendered details before acknowledging, then sends to the existing Zalo recipients and configured Gmail address with separate durable delivery states. This does not import Sheet rows into WordPress appointment posts.

The outbox keeps only row numbers and hashes, never patient values. Immediately before callback, the trigger rereads each referenced row and includes its nine string fields only if the content hash still matches, no formulas exist, and each cell is at most 250 characters. Concurrently moved/changed/deleted rows remain metadata-only for that older event; a later scan reports the latest change. WordPress validates the key, name, phone, calendar date, booking time (07:30–16:00), source Website/Zalo and allowed status before rendering. Invalid/partial/non-booking data remains metadata-only. No clinical findings or arbitrary extra columns are copied.

Changes are split into batches of at most three booking rows, with stable event IDs across retries and no silent omission of remaining rows. More than the 100-event outbox capacity fails visibly without advancing the baseline. Empty/other-tab/structural events remain metadata-only. Existing successful events and historical baseline rows are not automatically resent. A targeted backfill must use a distinct stable event ID and the verified row.

Trigger handlers use the saved editor source; the web-app append/probe interface is unchanged. Deploy the PHP receiver first, then save the updated trigger source. No new Google permissions or sharing changes are needed.

## Meaningful changes and durable retries

- The edit/change triggers and timed scan compare hashes of underlying values and formulas across **all populated cells of all tabs**, not display formatting. This includes J and later columns in tab 0, every column in other tabs, and tab insertion/deletion/renaming (even empty tabs). Non-booking changes produce metadata-only events; primary booking changes additionally use the bounded detail flow above. Formatting alone remains silent. Moving/sorting keyed A:I booking rows does not create content-change notifications; other tabs and extra columns use physical-row hashes, so reordering their contents is a change in cell placement.
- A successful form append updates the baseline without a generic change event. A durable expected-row marker saved before its write also suppresses that exact new row if the write succeeded but the acknowledgment was lost. Other changes seen before/after that append remain queued. A different value at that key is never suppressed.
- The first setup/first scan establishes the current whole-file baseline silently. Re-running setup does not reset it. Upgrading an older A:I-only baseline establishes the expanded baseline silently and preserves pending callbacks. Rows without primary booking keys are tracked by physical row; inserting/removing rows can therefore count additional changes among such unkeyed rows. Keep stable column-A IDs for reliable booking-row identification.
- Each callback has the same signed envelope. Payload fields are `event_id`, `spreadsheet_id`, `sheet_id`, `kind` (`insert`, `update`, `delete`, `mixed`), `changed_rows`, `range` and a fresh integer `issued_at`. For changes confined to primary A:I, `range` is A1 notation; any extra-column, other-tab, mixed-scope or tab-structure change uses the literal `DATA`. `sheet_id: 0` identifies this integration's primary booking tab, not a claim that a file-wide change happened in tab 0. Each changed physical row is counted once; a tab structural/name change contributes one structural unit, including when there are no data rows. Other tabs and structural changes send no cell contents or tab names. Primary booking events may add booking_rows (at most three entries with row and nine string values), authenticated by the same HMAC. Credentials and staff recipient IDs are never sent. Event IDs stay constant across retries.
- The fixed WordPress endpoint must accept only valid HMACs/current timestamps, deduplicate by event ID and return HTTP 2xx JSON `{"ok":true,"event_id":"the-same-id"}` **after durably accepting the event**. Receiving the same ID again must return the same acknowledgment without sending again.
- An outbox entry is retained on HTTP failure, malformed reply, wrong ID or lost acknowledgment. A short durable lease prevents overlapping scans from sending concurrently. Subsequent five-minute scans retry with backoff up to one hour; they refresh `issued_at` while preserving the event ID. Each scan attempts at most one due callback.
- State uses compressed, generation-chunked Script Properties; the pointer changes only after new chunks are written. Baseline state contains tab IDs, hashes of tab names/rows and row positions only. Pending callbacks contain approved metadata and row number/hash references only. Old generation cleanup touches only this bridge's properties.
- Guardrails apply **across the file**: maximum 20,000 aggregate populated-range rows and 180,000 aggregate cells in populated rectangles, 100 pending change events, and 170,000 encoded state characters. Empty tabs count a one-cell rectangle for bookkeeping. If exceeded, execution fails rather than truncating or dropping pending events. The compressed state quota may be reached before the row/cell limits. Monitor Apps Script execution failures and WordPress sync status; resolve callback failures before the outbox fills.
- ScriptLock coordinates this bridge, not simultaneous human/API edits. Stable keys, explicit conflict handling, insert-row append and readback reduce collisions; an unconfirmed/conflicting write must remain pending on WordPress rather than triggering staff notifications.

## Local verification

```powershell
node scripts/google-sheets-booking/test.mjs
```

Tests mock Apps Script, properties, Sheet cells, triggers, locks and all HTTP calls in memory. They do not read Google or production data and do not send real staff notifications.

For an Apps Script `state` error, run the read-only `diagnoseBookingBridge()` manually. Its log/return contains only stage flags, counts and numeric state version. It compares decoding a default binary blob against an explicitly named `application/x-gzip` blob, which avoids an Apps Script MIME-dependent `ungzip` failure after restoring compressed bytes from Script Properties. The diagnostic never writes state, appends rows, calls the callback, or prints property values, hashes, patient data, secrets or raw exception messages. Existing compressed state can be read without resetting the baseline/outbox.

Primary references: [Sheets RAW values](https://developers.google.com/workspace/sheets/api/guides/values), [advanced Sheets service](https://developers.google.com/apps-script/advanced/sheets), [installable triggers and API-write limitations](https://developers.google.com/apps-script/guides/triggers/installable), [Apps Script property quotas](https://developers.google.com/apps-script/guides/services/quotas), [ContentService redirect behavior](https://developers.google.com/apps-script/guides/content).
