# Developer package acceptance — 26 October 2026

Archive: `gfc-php8512-production-20261026.zip`

SHA-256: `359593ec8968ec51caf498259da75687777f6db4ae6c03f813bcca9598a6c475`

Size: 106,346,796 bytes. The ZIP contains 11,114 files under one `gfc/` root.

The supplied production modules were merged into the existing PHP 8.5.12 / CI
3.1.13 / MX-compatible application: 88 files added, 57 updated. Original input
and pre-merge snapshots are retained privately; no database changes were run.

## Acceptance result: PASS

All **31 offline acceptance commands passed on the final ZIP's independently
extracted copy**, with PHP 8.5.12. They cover syntax, controller declarations,
CI/MX, user context, permission fixtures, connections, menu behavior, views,
helpers/libraries, CSV, spreadsheet round trips, barcodes/CAPTCHA, Jasper client
behavior, financial bindings, report fixtures, attendance initialization,
budget activity rendering and packaging regressions.

Selected results:

- 3,300 packaged PHP files compiled; zero errors/deprecations.
- 480 packaged controller declarations checked; zero failures.
- 1,091 financial binding checks and 475 report-contract checks passed.
- 244 controller-connection checks and 36 budget-partial checks passed.
- Eight package-builder regression tests passed.
- All 18 required payment module CSS files are present and nonempty.
- The bundled OCI8 ZIP matches its documented SHA-256.
- Archive/extraction file hashes were verified. `uploads/.htaccess` retains
  `Options -Indexes`.

Normal Windows process permissions were used for extracted acceptance because
the restricted test token returned an incorrect `is_readable()` result. No
application/vendor assertion was disabled. The fixtures do not connect to
Oracle, change user permissions, execute real financial actions or enable VPN.

## Before developers sign in

Read `README.md`, `INSTALL.md` and
`tools/php85/developer-package-setup.md` inside the ZIP. Supply the approved
private values for `REQUIRED_...` placeholders. The package sanitizes 29 files;
active workstation credentials and the original production modules folder
were not changed by packaging.

The ZIP excludes uploaded data, sessions, caches/logs, private diagnostics,
backups, obsolete browser installers and database maintenance scripts. Share
the accepted ZIP/checksum and this acceptance record, not the private build
directories or earlier rejected candidate.

## Remaining limits

Post-merge live Oracle/Jasper/business acceptance and Ubuntu 24.04/Nginx
deployment have not been run. Prior live results do not certify this merge.
The existing type-4 payment-request history mismatch and the measured financial
archive permission-query bottleneck remain; see the included merge record.
