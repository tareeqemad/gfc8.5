# HMVC user and permission context audit

Audit date: 2026-10-23.

## Result

The payroll guard must read the authenticated user from `get_my_instance()`,
which returns the active `MY_Controller`. In this application, `get_instance()`
returns the shared CI object used for framework services. That object does not
own the module's authenticated user.

The existing fix in `application/helpers/payroll_access_helper.php` applies to
both `payment_req` and `payment_accounts`. Both controllers call the same guard.
The guard still passes the user ID to the same `Payroll_access_model::is_allowed()`
check and keeps the existing HTML/AJAX denial responses.

No additional instance-selection defects were found in the audit below. This
follow-up adds regression coverage, with no further production behavior changes.
No permission records, whitelist entries, roles, branch filters, route exceptions,
database procedures, or permission decision rules were changed.

## Scope and findings

Static searches and code tracing covered 2,812 PHP files in 69 module directories,
including historical module copies, plus shared helpers, core classes, libraries,
root controllers, MX, and the alternate `application/payment_req` tree.
This was a code audit, not an authenticated execution of every route.

| Area | Finding |
| --- | --- |
| Module controllers | Controller classes extend `MY_Controller`; constructors initialize the parent before using the authenticated user or access guards. |
| General route checks | `MY_Controller` loads the session user into its own `user` property and passes that ID to `check_permission()`. |
| Shared access helpers | `HaveAccess()`, `get_curr_user()` and `get_notify_count()` already use `get_my_instance()`. |
| Payroll management | `Payroll_access` uses its controller's user ID for the existing manager check. |
| Models | Permission methods receive explicit user IDs. No actual model subclass was found reading the authenticated user from the shared CI object. `Financial_mqasah.php` in a models directory declares a controller class. |
| Views | `MX_Loader::__get()` forwards user reads to the owning module controller. Nested `isset($this->user->id)` and `empty($this->user->id)` also work. |
| Remaining `get_instance()` calls | Reviewed calls use shared session, configuration, input or framework services. The stores balance view uses the shared session correctly. They do not need a blanket replacement. |
| Alternate payment tree | Uses the same payroll helper; configured module discovery points to `application/modules`. |

## Regression checks

All checks below use synthetic data and run without Oracle or the application
bootstrap. They neither read nor modify real users or permission records.

- `user_context_test.php`: 66 checks on PHP 7.4.30 and 8.5.12. Tests the real
  security helpers with separate core and module identities, allowed/denied/guest
  users, changing module contexts, exact permission URL arguments, and the existing
  positive permission-count rule.
- `payroll_access_context_test.php`: 70 checks on both PHP versions. Tests active
  identity selection and actual exit-based HTML/AJAX denial responses. A denied
  module user cannot inherit access from a privileged core fixture.
- `mx_compat_test.php`: 79 checks on PHP 8.5.12 using the real CI/MX stack with a
  disposable synthetic application. Added view checks verify user/branch values,
  guest identity, context changes, and nested `isset`/`empty` behavior when the
  shared core contains a different user.
- `helpers_compat_test.php`: 55 checks passed on PHP 8.5.12.
- `controller_test.php`: existing guest/member initialization checks passed on
  PHP 8.5.12.

From the project root in PowerShell:

```powershell
$php85 = 'C:\xampp744\php8512\php.exe'
& $php85 -n tests/php85/user_context_test.php
& $php85 -n tests/php85/payroll_access_context_test.php
& $php85 -n tests/php85/mx_compat_test.php
& $php85 -n tests/php85/helpers_compat_test.php
& $php85 -n tests/php85/controller_test.php

$php74 = 'C:\xampp744\php\php.exe'
& $php74 -n tests/php85/user_context_test.php
& $php74 -n tests/php85/payroll_access_context_test.php
```

## For future module changes

In a controller, use its authenticated `$this->user`. In a shared helper, use
`get_curr_user()` or `get_my_instance()` when reading the application user or
controller state. Pass that user ID explicitly to existing permission models.
Keep using the shared CI instance for shared services where appropriate.

Do not fall back to another object's user, grant access when identity is absent,
or remove a permission check to resolve a context error. Run the context tests
after changing authentication, authorization helpers, or MX integration.
