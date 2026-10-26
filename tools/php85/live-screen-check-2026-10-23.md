# Live screen checks on PHP 8.5.12

Date: 2026-10-23. Environment: local Apache on port 8084, existing authenticated
browser session, Oracle/Jasper reachable through the user's VPN.

**Visual follow-up:** the user subsequently identified missing account-page
styling that the functional checks below did not detect. The referenced module
CSS files were absent from the local release. See
[the stylesheet restoration](module-css-restoration.md) for the original-design
recovery and browser validation. Functional success below does not establish
visual equivalence with production.

## Scope

Used page navigation, existing-record display, query buttons, client-side search,
and opening/closing read-only dialogs. No business records or permissions were
changed. No save, approve, pay, cancel, copy, upload, delete, or attendance-update
actions were invoked. Normal application session/audit activity still occurs
when authenticated pages are requested.

This is a representative live check of the requested screens, not an exhaustive
test of every financial action, export format, report, or role. Results below are
for the current account and filters. No report files or personal/account data
are included in this document.

## Results

| Screen / action | Live result |
| --- | --- |
| Financial journal archive | Opened; query returned 200 rows; no visible PHP/Oracle error or console error. |
| Financial journal Jasper report | Report modal opened and closed. Local report response: HTTP 200, `application/pdf`, 168,846 bytes, `%PDF-` at byte 0, no PHP warning in the inspected prefix. |
| Financial payment voucher archive | Opened; search returned 200 rows. |
| Existing financial voucher details | Opened with the expected voucher-detail title and tables, no visible PHP/Oracle error. |
| Voucher attachment dialog | Modal opened and closed; its read-only list endpoint returned HTTP 200. No files selected, uploaded, or deleted. |
| Voucher Jasper report | Initially failed with an undefined JavaScript handler. After the fix below, modal and iframe opened with no console error. Report response: HTTP 200, `application/pdf`, 160,862 bytes, PDF signature at byte 0, no warning in the inspected prefix. |
| Financial accounts index | Opened with 4,905 table rows. Client-side search reduced visible rows to 242; no console error. |
| Payroll payment requests | Opened; query returned 173 rows; no PHP/Oracle/console error. |
| Existing type-2 request history dialog | Opened, fetched a result table with one row, and closed successfully. |
| Existing monthly-dues request history dialog (type 4) | **Failed:** dialog opened but the endpoint returned the type-2-only error. See remaining issue below. |
| Employee payment accounts | Opened; query returned 200 rows; no PHP/Oracle/console error. |
| Existing employee account / change-log dialog | Details opened. Log request returned HTTP 200; dialog displayed the valid empty-log message and closed successfully. |
| Employee attendance | Opened and query returned 25 rows. Initial console load-order error was fixed; fresh page and query then had no console errors. |

Jasper checks verify the modal/iframe flow and the returned PDF response. The PDF
viewer contents were not exported, screenshotted, or attached to the conversation.

## Technical fixes made during the check

1. **Voucher archive report handlers** — added the missing
   `print_financial_payment_report()` and `print_cash_report()` functions in
   `application/modules/payment/views/financial_payment_archive.php`. Their report
   paths, parameter names and serial use the existing index-page implementation.
   Existing permission gates and query logic are unchanged.
2. **Jasper PHP warning** — extended
   `application/third_party/php85/patch_jasper.php` to replace the deprecated null
   numeric prefix in `http_build_query()` with an empty string. Applied through
   the existing Composer patch mechanism; report parameter encoding is unchanged.
   The warning was logged rather than included in the PDF response.
3. **Attendance script order** — delayed the two existing event registrations in
   `application/modules/hr_attendance/views/clock_data_page.php` until jQuery is
   available on the initial page. AJAX table replacements bind immediately.
   Event actions, attendance values and permissions were not changed.

## Remaining issue: monthly-dues history dialog

`payment_req_show.php` renders the history button for saved request types 2 and 4.
`Payment_req::public_employee_month_payments()` currently accepts only a saved
type-2 request and rejects type 4 with:

> تعذر العثور على طلب صرف من النوع 2.

This mismatch was reproduced through the UI. Type-2 history works. The request
type condition and permission checks were left unchanged during this read-only
screen test. The intended supported request types need to be reconciled between
the button and endpoint; this is not an Oracle connection failure.

## Other observations

The financial accounts index response is about 7.9 MB and contains thousands of
rows, so opening it is a heavier operation than a paginated query. This run did
not establish a PHP 7.4 versus 8.5 timing comparison for that page.

## Repeatable offline checks

From the project root in PowerShell:

```powershell
& 'C:\xampp744\php8512\php.exe' -n tests/php85/jasper_query_test.php
node tests/payment_req/financial_payment_reports_test.js
node tests/php85/clock_data_script_order_test.js
```

All passed. Jasper has 9 parameter-encoding cases; payment reports have 6 actual
handler cases across the index/archive scripts. Attendance checks cover initial
load, AJAX replacement, preservation of both handlers and zero data requests
during initialization. PHP 7.4/8.5 lint checks passed for the changed PHP sources;
Jasper's encoding test also passed on PHP 7.4.
