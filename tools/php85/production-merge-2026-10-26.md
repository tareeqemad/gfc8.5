# Production modules merge — 26 October 2026

The supplied production source was `C:\xampp744\htdocs\modules` (2,921 files).
It was copied into a separate review area and compared against the existing
2,820-file local modules tree and the Git baseline. Both source snapshots have
private ZIP backups and SHA-256 manifests.

The installed merge adds **88 files** and updates **57 files**, resulting in
2,908 files under `application/modules`. Files present only in the existing
local tree were retained; absence from the supplied folder was not treated as
an instruction to delete them.

## Merge decisions

- Retained production changes to budget programs, planning/KPI screens,
  attendance analysis, financial voucher filters, account-tree refresh on
  branch changes, user/profile screens and the other changed modules.
- Preserved the PHP 8.5 signatures and opening tags, current PhpSpreadsheet
  calls, explicit CSV escaping, financial Jasper print handlers, print-window
  styles and the separate payment CSS assets.
- Kept CI 3.1.13, MX, shared user-context fixes, Oracle binding fixes and the
  first-controller connection reuse. These live outside the supplied modules.
- Kept the optimized `Sysmenus_model`: the production copy matched the older
  Git baseline and did not include the measured menu optimization.
- Used production's updated attendance initializer and its handling of an
  empty movement type. Updated the offline JavaScript regression to check
  initial page loading, AJAX replacement and repeated binding.
- Retained production's permission checks and business query parameters.
  No database data, user permissions, approval state or SQL migrations were
  changed or executed as part of the merge.

The production folder contained three nested historical `budg_pb/budg_pb/...`
copies, 17 files each. These 51 files are preserved in the private incoming
snapshot and were not added to the active module tree. The package also omits
the already-existing historical `budg_pb/controllers/budg_pb` copy. Active
outer budget module files were reviewed separately; nested copies were never
used to overwrite them.

## Additional defects corrected in incoming files

1. Removed ineffective optional defaults preceding required parameters in
   the new indicator copy and new planning actions. Parameter order and
   effective required arguments remain unchanged.
2. Closed a missing outer condition in `planning/views/show_all_kpi_plan.php`
   and expanded the short PHP tag in the budget activities view.
3. Fixed the program-card activity partial's undefined `page_rows` access.
   It now uses the selected program's already-fetched activities. The view
   selects each activity's units with its own `ACTIVITY_ID` and reads `SER`
   without the erroneous leading space. Existing authorization and model
   query arguments are retained.

The budget regression reproduced the original warning, then passed 36 checks
under E_ALL against the real action and view with synthetic models. It covers
distinct units, row identifiers, allowed/denied add controls and empty results,
without bootstrapping the database connection.

## Validation

Before installation, all 2,951 PHP files in the complete candidate (including
the preserved incoming historical copies) compiled without errors or
deprecations on PHP 8.5.12. A token-aware scan found zero short opening tags.
The focused attendance and budget regressions passed.

Installed-source acceptance passed all 30 commands after using the required
isolated GD/mbstring/ZIP extension settings for their fixtures. This includes
3,335 active PHP files compiled without errors/deprecations, 484 controller
declarations checked without failures, 1,091 financial binding checks, 475
report-contract checks, 244 controller-connection checks and the new budget and
attendance regressions. Package-builder regression tests also passed.

The financial binding fixture now explicitly selects its synthetic reader
schema instead of relying on the machine's default DBConn username. This lets
the same 1,091 checks run with sanitized package settings while retaining the
stub that forbids a real Oracle connection.

The spreadsheet/dependency test encountered a Windows restricted-process
`is_readable()` discrepancy. The generated workbook existed and was readable
with `fopen()`; the same discrepancy occurred on PHP 7.4. Running the identical
offline test with normal process permissions passed. No application/vendor
change or disabled assertion was needed.

Extracted-package acceptance is the final packaging gate. These offline checks
do not connect to Oracle, run an authenticated application request, enable a
VPN or execute business update actions.

## Developer package

Use [INSTALL.md](../../INSTALL.md) for Windows XAMPP or Ubuntu 24.04/Nginx.
The ZIP has a single `gfc/` root, locked Composer dependencies, the ready Windows
OCI8 Client 19 package, required assets and tests. Connection passwords, service
tokens and the application encryption key are replaced only in the packaged
copy; the local installation keeps its existing configuration.

Before sign-in, follow the explicit
[package configuration checklist](developer-package-setup.md). Values beginning
with `REQUIRED_` must be supplied privately for the intended environment.
Copy settings values, not old connection/controller files.

The package excludes uploads, sessions, caches/logs, private diagnostics,
backups, historical dependency copies, the obsolete workstation installer and
the root `database/` maintenance scripts. Database package deployment remains
a separate DBA task; this modules-only delivery does not supply or authorize
a database migration. Use a database matching the supplied production modules.

## Remaining acceptance limits

- The type-4 payment-request history mismatch is also present in the supplied
  production controller: its endpoint still accepts only type 2. It was not
  widened as part of this merge.
- The 24 October live measurements and Jasper checks describe the previous
  tree. They do not establish post-merge live acceptance. Repeat read-only
  financial, payment, attendance and report checks on the company network.
- The previously measured repeated permission-query cost in the financial
  voucher archive remains. This merge does not claim a new speed improvement.
- Ubuntu 24.04/Nginx deployment and the newly supplied module workflows need
  acceptance in their actual target environments. Offline fixtures cannot
  validate Oracle package versions, every role or every report/export.

Keep the private before/production snapshots and merge decision manifests
outside the developer distribution. Do not use a blanket Git reset or replace
the entire shared core to undo an individual module change.
