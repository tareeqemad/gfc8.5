# Configure the developer package before sign-in

The Git checkout and developer ZIP contain sanitized application settings. The running source
installation was not modified while packaging. Values beginning with
`REQUIRED_` are placeholders and **must be configured before real use**.
Do not try to connect using those values. Obtain the approved settings through
your company's normal private channel; do not put passwords in chat, commits,
shared ZIPs or command history.

Follow the Windows or Ubuntu steps in [INSTALL.md](../../INSTALL.md), then
transfer the settings below from the approved environment into this new copy.
Copy values only: retain the updated PHP, CI/MX and Oracle binding code. There
is no automatic `.env` loader in this application.

| Location | Required settings |
| --- | --- |
| `application/libraries/DBConn.php` | Default `$user`, `$pass`, `$instance`; retain `AL32UTF8` and authenticated-user password derivation. |
| `application/core/GISConn.php` | GIS `$user`, `$pass`, `$instance`, if GIS is used. |
| `application/core/BillsConn.php` | Billing `$user`, `$pass`, active `$instance`; review the commented alternative separately. |
| `application/config/database.php` | `default` connection `hostname`, `username`, `password`, `database`. This file does not replace DBConn settings. |
| `application/config/config.php` | Existing approved `encryption_key`; preserve the existing application's key when migrating encrypted data. Review the base URL/session settings for your environment. |
| `application/controllers/Login.php` | Commented `smtp_user`/`smtp_pass` values are sanitized too. Keep them commented unless the approved mail configuration requires authentication. |
| `application/controllers/Jsperreport.php` | Active Jasper client constructor username/password and server address. The obsolete commented constructor is sanitized too; do not enable it. |
| `application/controllers/Reports.php` | Crystal report `$username`, `$password`, `$instance`, if this integration is used. |
| `application/modules/archive/controllers/Archive.php` | FTP username/password in both upload and deletion configurations, if archive integration is used. |
| `application/modules/treasury/controllers/Dxncharging.php` | Approved bearer token, if charging/SMS integration is used. |
| `application/modules/treasury/controllers/Workfield.php` | SMS `$user` and `$password_app` in both existing call sites. |
| `application/modules/payment/controllers/Carmovements.php` | Car service `$apiKey`. |
| `assets/js/mainFunction.js` | Optional car-tracking XMPP `jid` and `password`; retain the approved transport/server settings. Configure these before using the integration, which connects automatically. These browser-side values are visible to users who load this asset. |
| `application/modules/payment/views/carmovements_jsview.php` | SMS URL `user`/`password` values. Review the existing client-visible integration before enabling it. |
| `application/modules/technical/controllers/Requests.php` | SMS URL username/password. The commented diagnostic URL is sanitized too. |
| `application/modules/interviews/controllers/Interviews_result.php` | SMS URL user/password. |
| `application/modules/settings/views/users_index.php` | Identity service URL token. |
| `application/modules/hr_attendance/controllers/Exit_edit.php` | Two separate direct Oracle connection user/password/instance values. These are distinct connections; do not assume one credential fits both. This legacy maintenance endpoint can change attendance data and is not an acceptance-test entry point. |
| Map views | Google Maps keys and historical Mapbox tokens. See [sanitized-settings.json](sanitized-settings.json) for every exact file (also recorded in the developer ZIP manifest). Restrict replacement browser keys to the approved origins. |

Configure only integrations you intend to use. Keep placeholder integrations
unused until configured; this package does not grant access or create accounts.
Never run business updates, SQL maintenance scripts or imports as an installation
test. Syntax/offline fixture tests do not require service credentials or VPN.

The ZIP excludes private data, uploads, sessions, cache/log contents, development
metadata, source backups, historical dependency copies and the old workstation
installer. The root `database/` maintenance and one-off SQL directory is also
excluded. This modules-only release supplies no database migration and requires
the company's existing database. Database packages/migrations remain a separate,
approved DBA workflow; do not deploy or execute SQL as part of this ZIP install.
Obsolete Chrome 38 and Firefox 33 installers are excluded. Application/tutorial
videos remain included. The package regenerates `uploads/.htaccess` with
`Options -Indexes` while excluding the uploaded data itself.
Restore production uploads/settings separately as described in
`INSTALL.md`. Root Composer dependencies are included in the developer ZIP for offline
acceptance, but are excluded from Git. Run the documented locked
`composer install` and platform checks before using a Git checkout.
The legacy `application/third_party/phpoffice/vendor/autoload.php` bridge stays.

## Rebuilding the package

The builder requires Python 3 and never edits the source installation. It fails
when an expected sanitization location changes or an unreviewed credential-like
literal remains. Review failures privately rather than weakening the checks.

```powershell
python tools/php85/build-developer-package.py --audit
# Only after the production merge and source checks have passed:
python tools/php85/build-developer-package.py --build --output temp/release-prep/developer-build-20261026
```

Choose a fresh output directory for each build; the builder never overwrites an
existing output. It creates the ZIP with a `gfc/` root, a SHA-256 sidecar, a file
manifest, a staged copy and a separately extracted copy verified against SHA-256.
Run the full offline acceptance commands on that extracted copy before sharing.
The builder itself does not run application tests or contact external services.

`excluded-path-audit.private.json` is outside the ZIP because excluded filenames
can be private. Share only the accepted ZIP and its checksum, not the staging
directory, exclusions audit or workspace backups. Archive metadata uses a fixed
timestamp and sorted paths for repeatable builds from unchanged source bytes.
The build output directory is protected by `Require all denied` for Apache;
keep it private. The Nginx deployment template blocks the application's `temp/`.
