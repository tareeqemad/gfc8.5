# GFC

GFC uses CodeIgniter 3 with MX modules and Oracle. This release contains the PHP 8.5 compatibility fixes, updated dependencies, and the Windows OCI8 extension built for Oracle Client 19.

Production modules supplied on 26 October 2026 have been merged with the compatibility fixes. See the [merge and acceptance record](tools/php85/production-merge-2026-10-26.md).

## Git checkout

This repository starts from the sanitized, tested developer release. Its first
commit intentionally does not import the previous repository's private history.
`vendor/` is not committed: run `composer install` with PHP 8.5.12 from this
directory before running the project checks. Runtime directories are retained
with guards; private uploads, logs, backups and database maintenance scripts
are excluded.

Configure the documented `REQUIRED_...` placeholders locally before sign-in.
Never commit real credentials or encryption keys. The original ZIP's verified
acceptance is recorded in [the release acceptance record](tools/php85/release-acceptance-2026-10-26.md).

## Installation

Open **[INSTALL.md](INSTALL.md)** and follow the section for your environment:

- **[Windows + XAMPP](INSTALL.md#windows-xampp):** upgrade an existing PHP 7.4 installation, install the ready OCI8 DLL, and run the local checks.
- **[Ubuntu 24.04 + Nginx](INSTALL.md#ubuntu-nginx):** install PHP-FPM, build OCI8, deploy the project ZIP, configure permissions, and switch the existing site.
- **[Application settings](INSTALL.md#application-settings):** preserve the database and service settings used by your environment.

The guide includes download links, commands, expected results, and rollback steps. Run only the section for your operating system. Keep the old application and runtime until the new installation is accepted.

The developer ZIP uses `REQUIRED_...` placeholders for private settings. Complete the [package configuration checklist](tools/php85/developer-package-setup.md) before signing in. There is no automatic `.env` loader.

## Release versions

| Component | Version |
|---|---|
| PHP | 8.5.12; later PHP 8.5 security releases require validation before rollout |
| CodeIgniter | 3.1.13 with the included PHP 8.5 fixes |
| HMVC modules | The included MX implementation and compatibility fixes |
| OCI8 | 3.4.1 |
| Oracle Instant Client | 19.32, full Basic package, 64-bit |
| Oracle Database | 11.2 or later; confirm the minor version if described as "11g" |
| PhpSpreadsheet | 5.10.0, locked in `composer.lock` |
| Jasper REST Client | 2.0.0, with the included Composer patch |

Use `composer install` from the project root. Keep `composer.lock` and allow the Composer scripts to run. Do not replace `system`, MX, or the compatibility files with unmodified upstream copies.

## Ready Windows OCI8 package

Use the [included OCI8 ZIP](tools/php85/packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip). Developers do **not** need Visual Studio or Oracle SDK. The Windows installation guide explains how to copy its `php_oci8_19.dll` into PHP's `ext` directory and install Oracle Basic separately.

This DLL was built with Client 19.32 and tested on **PHP 8.5.12 TS / VS17 / x64**, using both CLI and Apache. The upstream Windows DLL inspected during this upgrade was compiled with Client 23.8, despite its filename; use the included build for the legacy Oracle environment. See the [build report](tools/php85/oci8-client19-build.md) and [OCI8 requirements](https://www.php.net/manual/en/oci8.requirements.php).

## Quick checks after installation

These checks do not connect to Oracle. Complete `INSTALL.md` before running them.

**Windows PowerShell** — change `C:\xampp` if your XAMPP folder is different:

```powershell
Set-Location 'C:\xampp\htdocs\gfc'
$Php = 'C:\xampp\php8512\php.exe'
& $Php -v
& $Php --ri oci8
& $Php tools/php85/check.php
```

**Ubuntu Bash** — change the application path if you chose a different release folder:

```bash
cd /srv/gfc/releases/php-8.5.12/gfc || exit 1
php8.5 -v
php8.5 --ri oci8
sudo -u www-data php8.5 tools/php85/check.php
```

Expected: PHP **8.5.12**, OCI8 **3.4.1**, Oracle compile-time **19.32**, Oracle runtime **19.32.0.0.0**, and no failed project checks. Also run the Apache or PHP-FPM check in `INSTALL.md`; a successful CLI check does not prove the web server uses the same configuration.

## Preparing the project ZIP

Include the updated application, assets, `system`, MX, root `composer.json` and `composer.lock`, `tools`, `tests`, `README.md`, and `INSTALL.md`. Keep `tools/php85/packages` so Windows developers receive the ready DLL. You may include the root `vendor` directory, but still run the documented Composer installation and platform checks.

Include the entire `assets-n/css/modules` folder. Payment request and payment account screens load their page styles from it. `tools/php85/check.php` now checks that every module stylesheet referenced by the views is present and nonempty.

Exclude `.git`, `temp`, backups, local logs, and test output. Preserve the production settings and uploaded files separately. Do not overwrite server settings with a developer's credentials or copy entire old connection classes over the updated code.

Use the reviewed [package builder](tools/php85/build-developer-package.py) and its [instructions](tools/php85/developer-package-setup.md#rebuilding-the-package) to create the sanitized developer ZIP. It includes locked Composer dependencies, excludes database maintenance scripts and private runtime data, and verifies the extracted file hashes. Run the offline acceptance checks on the extracted copy before sharing it.

## Validation status

Application compatibility and Windows OCI8 loading checks passed locally. Representative read-only screens, modals and two real Jasper reports passed before this production merge; known failures and timing limits are documented below. Repeat live acceptance on the merged tree. The Ubuntu commands and templates have been reviewed but have **not** been executed on the target server. Full Excel import/export, every role and external integrations are not established by offline checks.

Documentation updates and the OCI8 build did not enable VPN. Building the DLL did not replace the DLL or Oracle Client used by the running local XAMPP instance; installation is a separate step in `INSTALL.md`.

Additional references:

- [Shared-core performance changes and checks](tools/php85/core-performance-review.md) — first-controller connection reuse passed offline and live checks; timing results are mixed.
- [Live financial, attendance and Jasper checks — 24 October 2026](tools/php85/live-screen-check-2026-10-24.md) — before/after measurements, working queries/modals/PDFs and remaining issues.
- [OCI8 build report](tools/php85/oci8-client19-build.md)
- [Optional Windows OCI8 rebuild instructions](tools/php85/windows-oci8-client19.md)
- [PHP-FPM template](tools/php85/deploy/gfc-fpm.conf) and [Nginx template](tools/php85/deploy/nginx-gfc.conf)
- [Earlier upgrade and test log](tools/php85/README.md) and [CodeIgniter merge record](tools/php85/ci3113-upgrade.md) — historical records in Arabic
