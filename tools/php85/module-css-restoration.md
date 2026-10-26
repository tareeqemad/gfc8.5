# Payment module stylesheet restoration

Date: 2026-10-23

The payment accounts and payment requests views referenced 18 stylesheets under
`assets-n/css/modules/`, but these files were missing from the local checkout.
This caused the employee account page to render without its profile, statistics,
account card and modal styling. The PHP runtime and user permissions were not
responsible for the missing design.

## Original source

The styles were recovered from existing project commit
`6fba73210603fa39963581a50977496c323fa79a`, before the inline styles were replaced
with external stylesheet links. The original selectors, declarations and order
were preserved. No replacement design was created.

Only complete `<style>` blocks within PHP `T_INLINE_HTML` tokens were extracted.
JavaScript strings and PHP heredocs were excluded. In particular, two generated
style blocks in `payment_req_batch.php` and one generated print style block in
`payment_req_batch_history.php` must not be merged into the main screen CSS.
These three original JavaScript snippets were restored in their existing view
functions instead. The two print windows retain their own table, typography and
print rules. The batch preview retains its original hidden checkbox column;
checkbox markup and handlers were not changed.

All source views are under `application/modules/<module>/views/`. The restored
files are under `assets-n/css/modules/<module>/`.

| Module | Source view | Restored CSS | HTML style blocks |
| --- | --- | --- | ---: |
| payment_accounts | payment_accounts_bulk_import.php | bulk_import.css | 1 |
| payment_accounts | payment_accounts_dashboard.php | dashboard.css | 1 |
| payment_accounts | payment_accounts_emp.php | emp.css | 1 |
| payment_accounts | payment_accounts_health.php | health.css | 1 |
| payment_accounts | payment_accounts_index.php | index.css | 1 |
| payment_accounts | payment_accounts_page.php | page.css | 1 |
| payment_accounts | payment_accounts_providers.php | providers.css | 2 |
| payment_accounts | payment_accounts_providers_page.php | providers_page.css | 1 |
| payment_accounts | payment_accounts_validation.php | validation.css | 1 |
| payment_req | payment_req_batch.php | batch.css | 3 |
| payment_req | payment_req_batch_detail.php | batch_detail.css | 6 |
| payment_req | payment_req_batch_history.php | batch_history.css | 1 |
| payment_req | payment_req_batch_print.php | batch_print.css | 1 |
| payment_req | payment_req_emp_statement.php | emp_statement.css | 1 |
| payment_req | payment_req_import_modal.php | import_modal.css | 1 |
| payment_req | payment_req_index.php | index.css | 1 |
| payment_req | payment_req_page.php | page.css | 1 |
| payment_req | payment_req_show.php | show.css | 2 |

## Verification

- All 18 referenced CSS files exist and are nonempty.
- Recovered content matches the original HTML style blocks, allowing only
  surrounding whitespace and line-ending differences.
- `css-tree` parsed all 18 stylesheets: 861 rules, no parse errors or unparsed
  `Raw` nodes.
- The CSS contains no dynamic PHP, JavaScript, asset `url()` references or
  `@import` dependencies requiring path adjustments.
- Both restored print-style snippets match the same original commit exactly;
  their JavaScript-generated CSS parses successfully. Both affected PHP views
  pass syntax checks on PHP 7.4.30 and PHP 8.5.12.
- Live local Apache check: `payment_accounts/emp.css` changed from HTTP 404 to
  HTTP 200 (7,208 bytes). The employee profile changed from unstyled block layout
  to its original flex layout, gradient and 12px border radius. All four statistic
  cards align on one row; account cards have the original 10px radius.
- The payment accounts index cards have the original 10px radius. The payment
  requests query returned 173 rows with its page stylesheet loaded. Monthly
  summary cards require the corresponding month filter and are not expected in
  the default result.
- The deployment check reports all 18 module stylesheets present, and the full
  runtime check passes. Live checks were read-only.

The restored files must be included when distributing the project ZIP. Copying
only `application/modules/` does not include the referenced stylesheets.
