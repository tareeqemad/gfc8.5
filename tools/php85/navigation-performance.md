# Navigation performance fix

Validated locally on 22 October 2026 with PHP 8.5.12, CodeIgniter 3.1.13,
Apache and the existing Oracle connection over VPN.

## Measured bottleneck and change

The recursive menu renderer called `SYSTEM_MENU_TB_GET_LIST` for each menu
node, including leaf nodes and repeated branches. The attendance page made
242 calls to this procedure. Most of its server response time was spent
waiting for these calls to Oracle.

This establishes where the slow requests spent their time. It does **not**
establish why the user observed the slowdown after upgrading: the recursive
queries and disabled menu-cache read already existed in the earlier code.

`Sysmenus_model::getList()` now uses the existing procedure's `parent=-1`
branch to fetch rows once for each filter combination, then groups them by
parent in PHP. User, visibility, main-menu, menu-code and system filters are
preserved. Special parent/code cases continue to use the original call.
The procedure definition in the connected schema was checked before using
this existing branch; no database objects were changed.

Rows are reused only within the current model instance/request. Keys include
all arguments, the database selection and signed-in user. Menu mutations
clear the local data. Authorization checks still execute normally.

The database's `ORDER BY SORT` order is retained. Items with equal `SORT`
values may change relative position because the procedure defines no
tie-breaker. Give siblings distinct `SORT` values in menu administration if
their exact positions must be fixed.

## Measurements

Server timings for `/gfc/hr_attendance/clock_data/index/1/my`:

| Measurement | Before | Final implementation |
| --- | ---: | ---: |
| Total request time | 44.69 s | 5.28 s |
| Menu procedure calls | 242 | 2 |
| Time in menu procedure calls | 40.91 s | 0.73 s |
| Authorization procedure calls | 5 | 5 |

The final sample reduced total server time by about 88%. These are measured
samples, not a guaranteed response time: VPN/database latency varies.
Another baseline request took 78.77 seconds. A separate dashboard request
after the fix spent 10.07 seconds opening one Oracle connection; the menu
change does not eliminate connection/network delays.

Browser verification found the same 255 menu links before and after the
change. The separate notification link/count was excluded from that
comparison. The attendance page returned HTTP 200. This is not a test of
every module or business operation.

## Validation

- 98 isolated menu checks passed under PHP 8.5 and PHP 7.4: filter forwarding,
  empty results, parent grouping, result isolation, context separation,
  procedure result order and invalidation after create/edit/sort/delete.
- 55 helper checks and 75 CI/MX checks passed under PHP 8.5.
- The isolated `MY_Controller` initialization test passed under PHP 8.5.
- Temporary live timing hooks and the experimental cross-request HTML cache
  were removed. No runtime configuration or Oracle client changes were made
  as part of this performance fix.

Run the focused regression test from the project directory:

```powershell
& 'C:\xampp744\php8512\php.exe' -n tests/php85/menu_list_memo_test.php
```

On Linux with PHP 8.5 installed:

```bash
php8.5 -n tests/php85/menu_list_memo_test.php
```

## Follow-up: was PHP 8.5 the regression trigger?

A read-only review found no added Oracle calls in `DBConn`, `MY_Controller`
or `New_rmodel`, and the exclusive session lock already existed before the
upgrade. Compatibility edits preserve the earlier execute/fetch modes and
make previous null coercions explicit.

The same existing menu procedure was then executed through the current
`DBConn::excuteProcedures()` on both installed PHP versions. Each batch made
eight calls using the same root-menu filters and a new connection. Returned
row counts and hashes matched in every batch. No Apache settings were
changed, and the procedure definition contains only a SELECT/cursor open.

Default-configuration results, in execution order within each group:

| Group | Runtime | Mean procedure time per call |
| --- | --- | ---: |
| First comparison | PHP 7.4.30 / OCI8 2.2.0 | 125.53 ms |
| First comparison | PHP 8.5.12 / OCI8 3.4.1 | 229.08 ms |
| First comparison | PHP 8.5.12 / OCI8 3.4.1 | 250.41 ms |
| First comparison | PHP 7.4.30 / OCI8 2.2.0 | 142.39 ms |
| Later comparison | PHP 8.5.12 / OCI8 3.4.1 | 140.34 ms |
| Later comparison | PHP 8.5.12 / OCI8 3.4.1 | 104.36 ms |
| Later comparison | PHP 7.4.30 / OCI8 2.2.0 | 130.86 ms |

Minimal-configuration runs and the locally built Client 19 package also
returned matching results. Those runs did not establish a consistent
configuration/client winner. The first comparison's slower PHP 8.5 result
did not persist in the later comparison.

These are isolated procedure tests, not a controlled comparison of complete
old and new application requests. They neither prove nor exclude every
upgrade regression. The exact trigger remains unconfirmed; differences in
the earlier network route/environment and full-request behavior need to be
controlled before attributing the regression to PHP, OCI8, CI or the VPN.

## Full menu-sequence comparison on 23 October 2026

The user confirmed that the earlier PHP 7.4 experience used the same
computer and VPN. After the Oracle listener became reachable and the user
signed in, a temporary localhost-only hook captured the 242 original menu
argument sets from the attendance page. The optimization's internal bulk
call was excluded. The hook was removed immediately after capture.

The exact sequence was replayed without the menu optimization. The first
comparison used the original Git version of DBConn on PHP 7.4, the current
compatibility version on PHP 8.5, and then the compatibility version on PHP
7.4. A second comparison reversed the runtime order.

| Run | Runtime and DBConn | Replay time |
| --- | --- | ---: |
| 1 | PHP 7.4.30, original DBConn | 15.54 s |
| 2 | PHP 8.5.12, compatible DBConn | 16.39 s |
| 3 | PHP 7.4.30, compatible DBConn | 16.48 s |
| 4 | PHP 8.5.12, compatible DBConn | 16.11 s |
| 5 | PHP 7.4.30, original DBConn | 16.76 s |

All five runs completed 242 calls and returned the same 240 rows across
those calls, with identical aggregate and per-call result hashes. Preparing
and binding all 242 calls took only 16–22 milliseconds per run. Nearly all
the remaining time was inside Oracle procedure execution, cursor fetching
and the existing post-read commits (roughly five seconds for each phase).
These phases include client, transport and database work; their timings
alone cannot assign the delay to the VPN or database server.

The replay used one fresh connection with the existing default application
connection settings and the captured per-user procedure filters. It did not
recreate the full authenticated Apache/CI request, its user-specific
connections, page rendering or concurrent session activity. Statement
cleanup was explicit and consistent across runs. An optional eight-second
OCI call timeout was available on the new extension; the old build reported
it unsupported and ran without it. No timeout fired in a successful run.

This test found no substantial, repeatable slowdown attributable to either
the PHP version or DBConn compatibility edits in the measured menu path.
It does not reconstruct the exact pre-upgrade environment or prove what
triggered the initial report. The historical trigger remains unconfirmed.

An independent `application/modules.zip` snapshot contains Sysmenus files
dated 2 March 2026 that match the original Git versions: recursive fetching
and the commented-out menu cache predate the upgrade. No complete snapshot
of the uncommitted application immediately before the October upgrade was
found, so that historical limitation remains.

The existing menu optimization remains in place. This follow-up added only
diagnostics/documentation; it did not switch the active PHP/OCI installation,
change Oracle objects or alter transaction behavior in the application.
