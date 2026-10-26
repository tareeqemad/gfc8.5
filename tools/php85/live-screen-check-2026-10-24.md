# Live financial and attendance checks — 24 October 2026

Environment: local Apache on port 8084, PHP 8.5.12, the user's authenticated
browser session, and Oracle/Jasper reached over the user-enabled VPN.

## Scope and limits

The checks used navigation, read-only query buttons, existing-record details,
client-side filtering, modal open/close actions and Jasper PDF reports. No
save, approve, pay, delete, copy, import, upload or permission-change actions
were invoked. The application's normal session and audit activity still occurs
when authenticated requests run.

This is representative acceptance for the current account, selected system,
database and tested filters. It does not establish every role, financial
operation, import/export format or report. No personal/account records or
report contents are included in this document.

## What the timing comparison means

Both modes ran **the current PHP 8.5.12 application** with the same existing
compatibility and menu fixes. The temporary baseline mode disabled only the
new first-controller connection reuse, reproducing the earlier extra
connection. Optimized mode kept the new connection reuse. This is **not** a
PHP 7.4-versus-8.5 benchmark.

Temporary capture was restricted to one explicitly armed loopback path and
HTTP method at a time. It recorded aggregate durations, connection/procedure
counts, HTTP status and PDF validation metadata. It did not record SQL,
credentials, request parameters or report contents.

Durations measure server-side request handling through the capture's shutdown
callback, including PHP view generation and Oracle work/waiting. They do not
measure browser rendering or the complete end-to-end transfer time. Browser
automation sometimes timed out while the slow server request continued;
completion was checked separately against the rendered page and server capture.

Before/after page requests used the same current account, selected system and
default filters. Returned record identities were not hashed or frozen, so
this is not proof that concurrent database content was identical. Small
sample counts and varying service/network latency limit causal conclusions.

## Page-opening measurements

Arithmetic means are shown for screens with two samples per mode. Individual
samples are included to expose variability. All recorded requests below ended
with HTTP 200 and no captured fatal PHP error.

| Screen | Before samples (s) | After samples (s) | Before mean (s) | After mean (s) | Observed mean change |
| --- | --- | --- | ---: | ---: | ---: |
| Financial journal archive | 5.814, 3.827 | 4.182, 3.605 | 4.820 | 3.893 | 19.2% lower |
| Payment accounts | 4.841, 4.303 | 4.595, 4.444 | 4.572 | 4.519 | 1.1% lower |
| Payment requests | 4.468, 3.937 | 4.731, 3.517 | 4.203 | 4.124 | 1.9% lower |
| Employee attendance | 4.571, 4.439 | 6.162, 5.428 | 4.505 | 5.795 | 28.6% higher |

The connection count changed from **3 to 2** on these complete page requests.
The procedure and permission-call counts stayed the same in both modes:

| Screen | Procedures | Permission checks |
| --- | ---: | ---: |
| Financial journal archive | 18 | 4 |
| Payment accounts | 18 | 4 |
| Payment requests | 20 | 4 |
| Employee attendance | 21 | 5 |

The isolated first controller uses one connection instead of two; complete
pages can also initialize nested controllers or other independent connections.
For example, the voucher archive uses `modules::run()` to render its row
fragment. Those independent connections were retained.

These results verify removal of one connection, but **do not demonstrate a
general page-speed improvement**. Attendance was slower in this sample even
with fewer connections. Payment request/account differences are small relative
to the variation between requests.

Two heavier pages had different sample counts and are listed separately:

| Screen | Before | After | Sample counts and interpretation |
| --- | ---: | ---: | --- |
| Financial voucher archive, 200 rows | 74.775 s | 70.148–95.990 s | One baseline, two optimized samples; no reliable improvement established. |
| Financial accounts table, 4,889 rows | 45.910 s | 44.272 s | One sample per mode; approximately 1.64 s lower in this pair, not a repeatable performance claim. |

Both heavy pages also changed from 3 to 2 connections. Voucher calls stayed
at 719 procedures / 703 permission checks; the financial accounts table stayed
at 15 procedures / 2 permission checks.

## Functional acceptance

| Screen/action | Observed result |
| --- | --- |
| Financial voucher archive | Opened with 200 rows. A filtered query returned one row, HTTP 200, in 1.766 s. This narrower query is not comparable to the full 200-row opening time. |
| Financial voucher Jasper report | Modal opened/closed with no observed JavaScript error. HTTP 200, `application/pdf`, 160,862 bytes; `%PDF-` at byte zero. No warning marker in the inspected payload prefix, no bytes buffered before PDF output and headers had not already been sent. Captured request duration: 0.671 s. |
| Financial journal archive | Opened with 200 rows. Query returned 200 rows, HTTP 200, in 1.140 s. |
| Financial journal Jasper report | Modal opened/closed. HTTP 200, `application/pdf`, 170,011 bytes; `%PDF-` at byte zero, no warning marker in the inspected prefix, no prior buffered output or already-sent headers. Captured request duration: 0.542 s. |
| Payment requests | Opened successfully; query returned 173 rows, HTTP 200, in 1.584 s. |
| Existing type-2 request history | Modal opened, returned a one-row table and closed. HTTP 200; captured history request took 1.779 s. |
| Existing type-4 monthly-dues history | **Known failure reproduced:** the modal opened but displayed the type-2-only rejection described below. HTTP 200 here does not mean business-level success. |
| Payment accounts | Opened successfully; query returned 200 rows, HTTP 200, in 1.919 s. |
| Existing employee account details | Opened with no observed PHP/Oracle error; captured request took 3.387 s. |
| Account change-log modal | Opened/closed and displayed a valid empty-log result. JSON response HTTP 200; 0.995 s. |
| Employee attendance | Opened with 25 rows; query POST returned HTTP 200 and populated the table. No observed JavaScript error. |
| Financial accounts table | Opened with 4,889 rows. Filtering for `نقد` reduced visible rows to 242; clearing the filter restored all 4,889. No observed JavaScript error. |

PDF checks validate the actual report response and modal/iframe flow. They do
not assert visual correctness of every PDF page or export format; the PDF
contents were not attached to the conversation.

## Remaining problems

### Voucher archive repeats permission queries for each row

The 200-row voucher archive made **703 permission queries** out of 719
procedure calls. Optimized samples spent approximately 68.75 s and 94.64 s
inside procedures, while connection setup took approximately 1.23 s. Removing
one connection cannot resolve this dominant repeated-query cost.

`application/modules/payment/views/financial_payment_page.php` repeats
`HaveAccess($print_url)` inside the row loop. In particular, lines 78 and 94
contain PHP inside HTML comments: PHP still executes even though those links
are invisible. Typical eligible transfer rows perform four identical print
permission checks, including these two commented links.

A later scoped change can remove obsolete executable PHP from HTML comments
and evaluate reuse of the same print permission during this read-only table
render. It must retain endpoint authorization and verify equivalent visible
controls. No permission caching or permission-rule changes were applied here.

### Monthly-dues request history remains inconsistent

The view exposes history for saved request types 2 and 4, but
`Payment_req::public_employee_month_payments()` accepts only type 2. The type-4
test reproduced:

> تعذر العثور على طلب صرف من النوع 2.

The type guard and business rules were left unchanged. This is the same
functional mismatch documented in the [23 October checks](live-screen-check-2026-10-23.md),
not an Oracle connection failure or a new connection-reuse regression.

## Finalization

- All temporary capture hooks were removed and capture was disarmed. SHA-256
  checks confirmed exact restoration of the pre-instrumentation `index.php`,
  `MY_Controller.php`, `DBConn.php` and `Jsperreport.php`. A source search also
  confirmed that none of the temporary hook references remained in those files.
- The restored application passed all 244 isolated CI/MX controller connection
  checks on PHP 8.5.12, with OCI disabled and no database/network access.
- The retained permanent connection change is documented in
  [the shared-core performance review](core-performance-review.md).

Private aggregate evidence is in `temp/php85/live-20261024/requests.jsonl`;
exclude the entire `temp` directory and its source backups from the developer ZIP.
