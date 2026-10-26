# Shared-core performance review

Reviewed and implemented on 24 October 2026, before receiving the next
production modules. The first-controller connection optimization below is
applied and passed offline tests. PHP/Apache settings, database data,
permissions, transactions and module APIs were not changed. Implementation
and initial testing were offline. Subsequent read-only live acceptance used
the VPN enabled by the user; see the results below.

## Applied change

### Remove redundant Oracle connection initialization

Previously, `application/core/MY_Controller.php` called
`$this->load->library('DBConn')` and then `$this->conn = new DBConn()`.
The first library load already constructs DBConn. Its active constructor
uses `oci_new_connect`, which opens a separate connection on each call.
The standard first-controller path therefore constructed two connections;
`MY_Model` uses the controller's `conn` object.

No active `->dbconn` consumers or module-specific DBConn overrides were found
in the current tree. Recheck this after importing production modules.

The first controller now reuses the newly constructed library only if DBConn
was not already loaded or defined and the instance is exactly the global
application DBConn class. The source path is checked, so module overrides
and subclasses retain the previous behavior. The library alias stays
registered. Nested controllers, preloaded libraries and already-defined
classes retain fresh controller connections.

`controller_connection_test.php` exercises real CI/MX loading with a synthetic
application and inert DBConn. Its 244 checks pass on PHP 8.5.12 and 7.4.30:
guest/member initialization, construction counts, nested controllers,
changed user/database context, model ownership, preloaded/defined classes,
module overrides/config, permission calls, session auditing and notifications.
A disposable copy of the previous constructor fails the test with
`Expected 1 connection construction(s), got 2.`

The existing controller, MX, connection-binding, helper, user-context,
payroll-access and menu tests also passed on both runtimes. The MX test's
PHP-8-only prefix checks were replaced with equivalent `strncmp` checks so
its isolation assertions and cleanup run on PHP 7.4 as well. This does not
change application code or make the release's Composer dependencies support
PHP 7.4.

Live acceptance confirmed 3 to 2 connections on the tested complete pages,
with unchanged procedure and permission-call counts. Timing results were
mixed: the financial journal archive improved in this sample, payment
requests/accounts changed little, and attendance was slower. A reduction in
connection count does not establish an overall page-speed improvement. See
[24 October live measurements](live-screen-check-2026-10-24.md) for sample
counts, functional results and limitations.

Run from the project root (PowerShell):

```powershell
& 'C:\xampp744\php8512\php.exe' -n tests/php85/controller_connection_test.php
& 'C:\xampp744\php8512\php.exe' -n tests/php85/controller_test.php
& 'C:\xampp744\php8512\php.exe' -n tests/php85/mx_compat_test.php
```

On Ubuntu use `php8.5 -n` with the same test paths.

Reference: [PHP oci_new_connect documentation](https://www.php.net/manual/en/function.oci-new-connect.php).

## Deferred: permission lookup memoization

`HaveAccess()` in `application/helpers/my_security_helper.php` calls the
permission procedure each time. For example, `payment_req_page.php` repeats
`HaveAccess($delete_url)` inside its row loop as well as outside that loop.

No permission memo was added. Inspection found that `Salarycalculation_model`
extracts an Oracle handle through Reflection and executes procedures directly,
bypassing DBConn. A DBConn-only invalidation counter would miss those operations.
Models can also retain a different connection from the active nested controller,
and DBConn errors do not consistently throw. A helper-only cache cannot safely
infer execution success and invalidation from the returned permission count.

Keep live checks until an explicit opt-in design accounts for the actual
model connection, execution success and all intervening database operations.
Do not persist permission results across requests.

## Deferred: asset delivery

A local request for `assets-n/css/modules/payment_accounts/emp.css` returned
HTTP 200 with ETag and Last-Modified, but no Cache-Control or gzip encoding.
This file is only 7,208 bytes, so it does not establish a significant delay.
Measure larger shared assets before changing server compression or caching.
Any cache policy must let programmers receive changed CSS/JS reliably.
Do not apply public caching to authenticated pages or financial reports.

## Already effective

- The existing menu optimization reduced measured menu procedure calls from
  242 to 2. One attendance sample improved from 44.69 s to 5.28 s, with five
  authorization calls retained. Preserve it during the module merge; see
  [navigation-performance.md](navigation-performance.md) for measurement limits.
- A temporary loopback-only diagnostic verified PHP 8.5.12 under Apache
  (`apache2handler`) with OPcache enabled. It reported 160 cached scripts,
  12,382 hits, 191 misses, a 98.48% hit rate, no full-cache flag and no
  out-of-memory/hash restarts. Current settings include 128 MB, timestamp
  validation enabled, a two-second revalidation interval and JIT disabled.
  These counters provide no reason to recommend simply enabling OPcache
  again or increasing its memory blindly. The returned used-memory counter
  was negative, so it is not a reliable capacity measurement. The temporary
  diagnostic was removed immediately; no OPcache reset was performed.

Reference: [PHP OPcache configuration](https://www.php.net/manual/en/opcache.configuration.php).

## Preserve behavior

Keep financial commits and transaction boundaries, session status checks,
session audit updates, notification updates and module session writes.
Globally removing commits, switching to persistent Oracle connections or
closing sessions early can change these behaviors and is outside this
assessment's recommended initial changes.

Shared-core improvements also cannot remove every page-specific cost: the
previous live financial accounts test returned 4,905 rows in approximately
7.9 MB of HTML. Paging that screen would require a separate module change.

## Live acceptance completed on 24 October 2026

The current signed-in account successfully opened and queried payment requests,
payment accounts, financial archives and attendance. Existing-record modals
and two Jasper PDF responses were checked. The accounts table filter also
worked. The existing type-4 request history mismatch remains unresolved.
No save, approve, delete, import or permission-change action was invoked;
normal application session/audit activity was retained.

The voucher archive still made 703 permission queries while rendering 200
rows and took 70-96 seconds in the optimized samples. The financial accounts
table loaded 4,889 rows in about 44 seconds. These are remaining module-level
costs; they were not changed during acceptance. All temporary timing hooks
were removed and exact source restoration was verified with SHA-256.

Recheck incoming production modules for `dbconn` alias consumers and DBConn
overrides before release. Keep this optimization with `MY_Controller`; do
not overwrite the whole file from an older production copy. To roll back
only this optimization, restore the original loader call followed by the
singleton assignment and `new DBConn()`; retain the PHP 8.5 compatibility
changes elsewhere in the file.
