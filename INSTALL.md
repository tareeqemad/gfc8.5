# GFC installation guide

This guide upgrades the existing PHP 7.4 application to **PHP 8.5.12**, with **CodeIgniter 3.1.13**, the included MX fixes, **OCI8 3.4.1**, and **Oracle Instant Client 19.32**.

Choose your environment and run its numbered steps in order:

- [Windows / XAMPP — for developers](#windows-xampp)
- [Ubuntu 24.04 / Nginx — for the server administrator](#ubuntu-nginx)
- [Application and database settings — both environments](#application-settings)
- [Troubleshooting](#troubleshooting)

Commands use example paths. Change the settings at the start of your section before running them. Keep the same terminal window open so those variables remain available. Stop on an error and resolve it before continuing. Run only the commands for your operating system.

The project ZIP must include `composer.json`, `composer.lock`, `tools`, `tests`, and the ready Windows OCI8 package. See [README.md](README.md#preparing-the-project-zip) for packaging details. Save the existing application, its settings, uploaded files, and runtime configuration before replacing anything.

Downloads and Composer need internet access. Offline checks do not need Oracle or VPN. Run the final database checks only when access to the company network is available and permitted. This guide does not start a VPN.

**Who builds OCI8?** Windows developers use the DLL already built and included in the ZIP. On Ubuntu, the administrator builds `oci8.so` by running step 4; a Windows DLL cannot be used on Linux. Oracle Client 19 supports Oracle Database **11.2 and later**. Confirm the exact database version if it is described only as "11g".

The instructions target the versions checked on **22 October 2026**. Windows CLI and Apache loading were tested. The Ubuntu instructions have not been run on the target server; complete the runtime and application checks before accepting deployment.

<a id="windows-xampp"></a>

## Windows: XAMPP

Use this section to upgrade an existing **64-bit XAMPP** installation from PHP 7.4 to PHP 8.5.12. Apache, PHP, OCI8, and Oracle Instant Client must all be x64. For a 32-bit XAMPP installation, prepare a separate 64-bit installation first.

The project includes a ready-to-use [OCI8 package for PHP 8.5 TS, VS17, x64 and Oracle Client 19.32](tools/php85/packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip). Developers do not need Visual Studio, the Oracle SDK, or a C++ build. This package passed local CLI and Apache loading checks; database testing still requires the company network.

Oracle Client 19 supports Oracle Database **11.2 and later**, including 12c. Confirm that an 11g database is 11.2, not 11.1. Use the bundled OCI8 DLL: the official prebuilt DLL inspected during this upgrade used Client 23.8 despite its `_19` filename. See the [build report](tools/php85/oci8-client19-build.md) and [OCI8 requirements](https://www.php.net/manual/en/oci8.requirements.php).

### 1. Download the files and set your paths

Download these files to your Windows Downloads folder:

| File | Download |
|---|---|
| PHP 8.5.12, Thread Safe, VS17, x64 | [Official PHP ZIP](https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip) |
| Oracle Instant Client 19.32, Basic, x64 | [Official Oracle ZIP](https://download.oracle.com/otn_software/nt/instantclient/1932000/instantclient-basic-windows.x64-19.32.0.0.0dbru.zip) |
| Microsoft Visual C++ runtime, x64 | [Microsoft installer](https://aka.ms/vc14/vc_redist.x64.exe) |
| Composer for Windows | [Composer installer](https://getcomposer.org/Composer-Setup.exe) |

Choose **Thread Safe**, not NTS, for the Apache module. Use Oracle **Basic**, not Basic Light. The [XAMPP download page](https://www.apachefriends.org/download.html) does not currently provide a PHP 8.5.12 bundle; PHP is upgraded separately here.

Extract the new project ZIP outside `htdocs`, for example to `Downloads\gfc-release\gfc`. That folder must contain `index.php`, `composer.lock`, `tools`, and `tests`. Stop Apache in XAMPP Control Panel, then close the control panel.

Open **PowerShell 5.1 or later**. Edit the four settings marked below, then run this setup block once. Keep this PowerShell window open for the remaining steps. Stop if any command fails; do not continue with later blocks. If the window closes, rerun only the setup block, restore `$GfcBackup` to its saved value, and set `$GfcOciStage = Join-Path $GfcBackup 'oci8-package'` before resuming. Do not repeat completed installation steps.

```powershell
$ErrorActionPreference = 'Stop'

# Edit these settings for your computer.
$GfcXampp = (Resolve-Path -LiteralPath 'C:\xampp').Path.TrimEnd('\')
$GfcSource = Join-Path $env:USERPROFILE 'Downloads\gfc-release\gfc'
$GfcBaseUrl = 'http://127.0.0.1:8084/gfc' # Use the actual Apache port; omit :8084 for port 80.
$GfcComposer = 'C:\ProgramData\ComposerSetup\bin\composer.phar'

$GfcDownloads = Join-Path $env:USERPROFILE 'Downloads'
$GfcApp = Join-Path $GfcXampp 'htdocs\gfc'
$GfcPhpDir = Join-Path $GfcXampp 'php8512'
$GfcPhp = Join-Path $GfcPhpDir 'php.exe'
$GfcOracle = 'C:\oracle\instantclient_19_32'
$GfcApache = Join-Path $GfcXampp 'apache\bin\httpd.exe'
$GfcApacheConfig = Join-Path $GfcXampp 'apache\conf\extra\httpd-xampp.conf'
$GfcBackup = Join-Path $GfcXampp ('backup-before-php85-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
$GfcOciPackage = Join-Path $GfcSource 'tools\php85\packages\oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip'
$GfcOciStage = Join-Path $GfcBackup 'oci8-package'

function Invoke-GfcNative {
    param([string]$FilePath, [string[]]$ArgumentList)
    & $FilePath @ArgumentList
    if ($LASTEXITCODE -ne 0) {
        throw ('Command failed: {0}; exit code: {1}' -f $FilePath, $LASTEXITCODE)
    }
}
function Assert-GfcHash {
    param([string]$Path, [string]$Expected)
    if ((Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash -ne $Expected) {
        throw "SHA256 mismatch: $Path"
    }
}
```

`Invoke-GfcNative` checks the exit code from PHP, Composer, and Apache. This is required on Windows PowerShell 5.1; `$ErrorActionPreference` alone does not stop on a native program's failure.

Verify the downloads and the bundled OCI8 ZIP before extracting them:

```powershell
Assert-GfcHash -Path "$GfcDownloads\php-8.5.12-Win32-vs17-x64.zip" -Expected '660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184'
Assert-GfcHash -Path "$GfcDownloads\instantclient-basic-windows.x64-19.32.0.0.0dbru.zip" -Expected 'e95a66eb4e69e2fb28880cbd252e8eab9807b877c4248c0d50a28f7b75cfe878'
Assert-GfcHash -Path $GfcOciPackage -Expected '046A86199BE3D7F1107ADE26D46B96880B49443E84C3EE0C279FB61C10820F3E'
```

### 2. Back up the old installation

The backup stays outside `htdocs` and includes the old application, uploaded files, dependencies, Apache configuration, and PATH values. Keep the old `php` directory unchanged.

```powershell
if (-not (Test-Path -LiteralPath "$GfcSource\index.php")) { throw 'The extracted release folder is incorrect.' }
if (-not (Test-Path -LiteralPath "$GfcApp\index.php")) { throw 'The old application folder is incorrect.' }
if (Test-Path -LiteralPath $GfcPhpDir) { throw 'php8512 already exists. Review it before continuing.' }
if (Test-Path -LiteralPath $GfcBackup) { throw 'The backup directory already exists.' }

New-Item -ItemType Directory -Path $GfcBackup | Out-Null
Copy-Item -LiteralPath "$GfcXampp\apache\conf" -Destination "$GfcBackup\apache-conf" -Recurse -Force
Copy-Item -LiteralPath "$GfcXampp\xampp_shell.bat" -Destination $GfcBackup
Copy-Item -LiteralPath "$GfcXampp\php\php.ini" -Destination "$GfcBackup\php74.ini"
Copy-Item -LiteralPath $GfcApp -Destination "$GfcBackup\gfc" -Recurse -Force
Set-Content -LiteralPath "$GfcBackup\machine-path-before.txt" -Value ([Environment]::GetEnvironmentVariable('Path', 'Machine')) -Encoding UTF8
Set-Content -LiteralPath "$GfcBackup\user-path-before.txt" -Value ([Environment]::GetEnvironmentVariable('Path', 'User')) -Encoding UTF8
Write-Output "Save this backup path: $GfcBackup"
```

Save the printed backup path somewhere outside this terminal window.

### 3. Install PHP, Oracle Client, and the project

Install the Microsoft Visual C++ x64 runtime. Extract PHP into a new directory and Oracle Basic into its own directory:

```powershell
Expand-Archive -LiteralPath "$GfcDownloads\php-8.5.12-Win32-vs17-x64.zip" -DestinationPath $GfcPhpDir
Copy-Item -LiteralPath "$GfcPhpDir\php.ini-development" -Destination "$GfcPhpDir\php.ini"
New-Item -ItemType Directory -Path 'C:\oracle' -Force | Out-Null
if (Test-Path -LiteralPath $GfcOracle) { throw 'Client 19.32 already exists. Verify that installation before reusing it.' }
Expand-Archive -LiteralPath "$GfcDownloads\instantclient-basic-windows.x64-19.32.0.0.0dbru.zip" -DestinationPath 'C:\oracle'
Expand-Archive -LiteralPath $GfcOciPackage -DestinationPath $GfcOciStage
Assert-GfcHash -Path "$GfcOciStage\php_oci8_19.dll" -Expected 'F60D475675B08C214820A86CC06FC72CD6C3CB64B38D5C5720F90BC2E5ED1D36'
Copy-Item -LiteralPath "$GfcOciStage\php_oci8_19.dll" -Destination "$GfcPhpDir\ext\php_oci8_19.dll"
```

Only the OCI8 DLL goes into PHP's `ext` directory. Keep the package's license and build records in the backup. Oracle's `oci.dll` stays in `C:\oracle\instantclient_19_32`; do not copy Oracle DLLs into `ext` or `System32`.

Install the new project in a clean application directory. These commands preserve the current directory in the backup before copying the release:

```powershell
if ((Resolve-Path -LiteralPath $GfcApp).Path -ne "$GfcXampp\htdocs\gfc" -or
    -not ([IO.Path]::GetFullPath($GfcBackup)).StartsWith($GfcXampp + '\', [StringComparison]::OrdinalIgnoreCase)) {
    throw 'The application or backup path is outside the intended XAMPP directory.'
}
Set-Location -LiteralPath $GfcXampp
Move-Item -LiteralPath $GfcApp -Destination "$GfcBackup\gfc-working-copy"
Copy-Item -LiteralPath $GfcSource -Destination $GfcApp -Recurse -Force
New-Item -ItemType Directory -Path "$GfcApp\temp" -Force | Out-Null
New-Item -ItemType Directory -Path "$GfcXampp\tmp" -Force | Out-Null
```

The new application must be at `htdocs\gfc\index.php`, not `htdocs\gfc\gfc\index.php`. Transfer the required connection values, environment settings, and uploaded files from the backup. Follow [Application settings](INSTALL.md#application-settings); do not overwrite updated DBConn files with old PHP files.

In **Windows Environment Variables → System variables → Path**, add `C:\oracle\instantclient_19_32` before other Oracle Client entries used by this Apache. Do not set `ORACLE_HOME` for Instant Client. Refresh this PowerShell window's PATH after saving the change:

```powershell
$env:Path = [Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [Environment]::GetEnvironmentVariable('Path', 'User')
```

Open XAMPP Control Panel again when instructed below so Apache receives the updated environment. If Apache runs as a Windows service, restart that service after the configuration change. If it still loads the old client, restart Windows and verify again.

### 4. Configure PHP and Apache

Edit the new `php8512\php.ini`. Start from the new template, not the PHP 7.4 INI. Enable each extension once and set the following values. **The paths below use `C:/xampp`; change them if your XAMPP path differs.**

```ini
extension_dir="C:/xampp/php8512/ext"
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=zip
extension=php_oci8_19.dll

default_charset="UTF-8"
short_open_tag=Off
register_argc_argv=Off
memory_limit=512M
max_execution_time=120
max_input_time=120
upload_max_filesize=40M
post_max_size=48M
session.save_path="C:/xampp/tmp"
upload_tmp_dir="C:/xampp/tmp"
```

Keep the approved `date.timezone` and `max_input_vars` values from the old installation. The local reference timezone was `Europe/Berlin`; changing application timezones is a separate decision. Enable other required extensions, such as `ftp`, from the new PHP package. XML, DOM, bcmath, and OPcache are built into this Windows package; do not copy old extension DLLs or an old OPcache loading line.

For HTTPS, point `curl.cainfo` and `openssl.cafile` at an existing, current CA bundle if needed, for example `C:/xampp/apache/bin/curl-ca-bundle.crt`. Keep certificate verification enabled.

Edit `apache\conf\extra\httpd-xampp.conf`. Replace the PHP 7 module directives with:

```apache
LoadFile "C:/xampp/php8512/php8ts.dll"
LoadModule php_module "C:/xampp/php8512/php8apache2_4.dll"
<IfModule php_module>
    PHPIniDir "C:/xampp/php8512"
</IfModule>
```

Update the other PHP paths in that file, including `SetEnv PHPRC`, `PHP_PEAR_SYSCONF_DIR`, and any additional PHP `LoadFile` lines. Change `<IfModule php7_module>` to `<IfModule php_module>`. Keep the existing `SetHandler application/x-httpd-php`. Remove `php_admin_flag safe_mode off` if present. Keep the existing Apache ports.

If Apache reports a cURL/libssh2/nghttp2 dependency error, the tested older XAMPP installation needed these lines **before** `LoadFile .../php8ts.dll`:

```apache
LoadFile "C:/xampp/php8512/libcrypto-3-x64.dll"
LoadFile "C:/xampp/php8512/libssl-3-x64.dll"
LoadFile "C:/xampp/php8512/libssh2.dll"
LoadFile "C:/xampp/php8512/nghttp2.dll"
LoadFile "C:/xampp/php8512/brotlicommon.dll"
LoadFile "C:/xampp/php8512/brotlidec.dll"
```

Use these files from the new PHP directory; do not overwrite DLLs in `apache/bin`.

Check the configuration before starting Apache:

```powershell
Invoke-GfcNative -FilePath $GfcApache -ArgumentList @('-t')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('-v')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('--ini')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('--ri', 'oci8')
```

Expected results: `Syntax OK`, PHP `8.5.12`, INI inside `php8512`, OCI8 `3.4.1`, compile-time Oracle Client `19.32`, and runtime Client `19.32.0.0.0`. Compile-time `23.x` means the wrong DLL was copied; a different runtime client means Windows loaded another Oracle installation. These checks do not connect to Oracle.

### 5. Install dependencies and run the offline checks

Install Composer and select the new `php8512\php.exe` when prompted. If Composer was installed elsewhere, update `$GfcComposer` to its actual `composer.phar` path. Use the root project's lock file:

```powershell
Set-Location -LiteralPath $GfcApp
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @($GfcComposer, 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @($GfcComposer, 'check-platform-reqs', '--no-dev')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('tools/php85/check.php')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('tools/php85/scan-syntax.php')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('-n', 'tests/php85/mx_compat_test.php')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('tests/php85/dependencies_test.php')
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('tools/preview-login.php')
```

Composer may download packages, but the checks above do not contact Oracle or require VPN. Use `install`, not `update`, and keep Composer scripts enabled: they apply the required Jasper compatibility fixes. Do not use `--no-scripts` or `--ignore-platform-reqs`. Do not run Composer inside historical `application/third_party` directories.

### 6. Verify Apache and preview the login page

Start Apache from the reopened XAMPP Control Panel. Check Apache's actual PHP runtime with this temporary localhost-only endpoint. It does not load CodeIgniter, application configuration, or database connections. It returns selected runtime values, not `phpinfo()`.

```powershell
$GfcProbeDir = Join-Path $GfcApp 'temp\runtime-check-local'
$GfcProbePhp = Join-Path $GfcProbeDir 'runtime.php'
$GfcProbeAccess = Join-Path $GfcProbeDir '.htaccess'
if (Test-Path -LiteralPath $GfcProbeDir) { throw 'The probe directory already exists. Review and remove it before retrying.' }
New-Item -ItemType Directory -Path $GfcProbeDir | Out-Null
try {
    Set-Content -LiteralPath $GfcProbeAccess -Value 'Require local' -Encoding ASCII
    @'
<?php
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$extensions = [];
foreach (['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'iconv', 'libxml', 'mbstring', 'oci8', 'openssl', 'session', 'simplexml', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib'] as $name) {
    $extensions[$name] = extension_loaded($name);
}
echo json_encode([
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'thread_safe' => (bool) PHP_ZTS,
    'bits' => PHP_INT_SIZE * 8,
    'ini' => php_ini_loaded_file(),
    'extension_dir' => ini_get('extension_dir'),
    'oci8' => phpversion('oci8'),
    'oracle_client' => function_exists('oci_client_version') ? oci_client_version() : null,
    'extensions' => $extensions,
    'session_dir_writable' => is_writable(ini_get('session.save_path')),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
'@ | Set-Content -LiteralPath $GfcProbePhp -Encoding ASCII
    $GfcResult = Invoke-RestMethod -Uri "$GfcBaseUrl/temp/runtime-check-local/runtime.php" -TimeoutSec 15
    $GfcResult | ConvertTo-Json -Depth 3
    if ($GfcResult.php -ne '8.5.12' -or $GfcResult.sapi -ne 'apache2handler' -or
        $GfcResult.thread_safe -ne $true -or $GfcResult.bits -ne 64 -or
        $GfcResult.oci8 -ne '3.4.1' -or $GfcResult.oracle_client -ne '19.32.0.0.0' -or
        ($GfcResult.ini -replace '\\', '/') -ne ("$GfcPhpDir/php.ini" -replace '\\', '/') -or
        ($GfcResult.extension_dir -replace '\\', '/') -ne ("$GfcPhpDir/ext" -replace '\\', '/') -or
        -not $GfcResult.session_dir_writable) {
        throw 'Apache runtime does not match the required configuration. Review the JSON above.'
    }
    foreach ($GfcExtension in $GfcResult.extensions.PSObject.Properties) {
        if ($GfcExtension.Value -ne $true) { throw "Missing Apache extension: $($GfcExtension.Name)" }
    }
} finally {
    if (Test-Path -LiteralPath $GfcProbePhp) { Remove-Item -LiteralPath $GfcProbePhp }
    if (Test-Path -LiteralPath $GfcProbeAccess) { Remove-Item -LiteralPath $GfcProbeAccess }
}
```

Both probe files are removed even if the request fails. The empty folder remains; remove it manually before repeating the check. The ASCII encoding avoids a UTF-8 BOM before `<?php` in Windows PowerShell 5.1.

Apache must report `apache2handler`, the new PHP INI, and Client 19.32. If CLI and Apache differ, review the module paths, `PHPIniDir`, and Oracle PATH, then restart Apache. The XAMPP Config button may still open the old `php/php.ini`; use the INI path reported by this check.

Generate the static login preview first; temporary output is intentionally not included in the developer ZIP:

```powershell
Set-Location -LiteralPath $GfcApp
Invoke-GfcNative $GfcPhp @('tools/preview-login.php')
```

Then open **`<your base URL>/temp/login-preview/index.html`** to see the login design without Oracle. With the example settings, this is `http://127.0.0.1:8084/gfc/temp/login-preview/index.html`. Keep `mod_rewrite` and `AllowOverride` enabled for the project. The preview does not test login or database access.

Review [Application settings](INSTALL.md#application-settings) before using the real `/index.php/login` route. Open the real application and run Oracle checks only when access to the company network is allowed. Keep using `$GfcPhp` for commands; another `php` on PATH may still be PHP 7.4 or a different XAMPP installation.

### 7. Roll back if the upgrade fails

Stop Apache and close XAMPP Control Panel. If anyone used the new application, reconcile newly uploaded files and any new business data before rollback. Restore the old application **with its old `vendor` and lock file**, together with PHP 7.4.

Use the same PowerShell session. If it was closed, rerun the setup block, then set `$GfcBackup` to the saved backup path before continuing; do not rerun the backup or installation steps.

The commands below preserve the failed release outside `htdocs`, then restore the old files. They keep the new PHP and Oracle directories available for investigation.

```powershell
# If needed: $GfcBackup = 'C:\xampp\backup-before-php85-YYYYMMDD-HHMMSS'
$GfcFailed = Join-Path $GfcXampp ('failed-php85-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
if ((Resolve-Path -LiteralPath $GfcApp).Path -ne "$GfcXampp\htdocs\gfc" -or
    -not ([IO.Path]::GetFullPath($GfcFailed)).StartsWith($GfcXampp + '\', [StringComparison]::OrdinalIgnoreCase)) {
    throw 'The rollback paths are outside the intended XAMPP directory.'
}
foreach ($GfcRequired in @('gfc\index.php', 'apache-conf\extra\httpd-xampp.conf', 'xampp_shell.bat', 'machine-path-before.txt', 'user-path-before.txt')) {
    if (-not (Test-Path -LiteralPath (Join-Path $GfcBackup $GfcRequired))) { throw "Missing backup file: $GfcRequired" }
}
if (Test-Path -LiteralPath $GfcFailed) { throw 'The failed-release directory already exists.' }
New-Item -ItemType Directory -Path $GfcFailed | Out-Null
Copy-Item -LiteralPath $GfcApacheConfig -Destination "$GfcFailed\httpd-xampp.conf"
Copy-Item -LiteralPath "$GfcXampp\xampp_shell.bat" -Destination "$GfcFailed\xampp_shell.bat"
if (Test-Path -LiteralPath "$GfcPhpDir\php.ini") {
    Copy-Item -LiteralPath "$GfcPhpDir\php.ini" -Destination "$GfcFailed\php85.ini"
}
Set-Location -LiteralPath $GfcXampp
Move-Item -LiteralPath $GfcApp -Destination "$GfcFailed\gfc"
Copy-Item -LiteralPath "$GfcBackup\gfc" -Destination $GfcApp -Recurse -Force
Copy-Item -LiteralPath "$GfcBackup\apache-conf\extra\httpd-xampp.conf" -Destination $GfcApacheConfig -Force
Copy-Item -LiteralPath "$GfcBackup\xampp_shell.bat" -Destination "$GfcXampp\xampp_shell.bat" -Force
Write-Output "Failed release preserved at: $GfcFailed"
```

Before restarting Apache, reconcile any new uploaded files from `$GfcFailed\gfc` into the restored `$GfcApp`. Check `uploads`, `assets\da3em\img\uploads`, and your custom upload paths. Keep the original backup intact and review deletions separately. Otherwise database attachment records may refer to files left only in the failed release.

In Windows Environment Variables, restore the **Oracle PATH entries and their original order** using `machine-path-before.txt` and `user-path-before.txt` from the backup. Preserve unrelated PATH changes made since the backup. Leave the old PHP INI in place; do not copy the PHP 8 INI over it.

Refresh this terminal's environment and verify the old runtime before starting Apache:

```powershell
$env:Path = [Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [Environment]::GetEnvironmentVariable('Path', 'User')
Invoke-GfcNative -FilePath $GfcApache -ArgumentList @('-t')
Invoke-GfcNative -FilePath "$GfcXampp\php\php.exe" -ArgumentList @('-v')
Invoke-GfcNative -FilePath "$GfcXampp\php\php.exe" -ArgumentList @('--ri', 'oci8')
```

After `Syntax OK` and the expected old PHP/Oracle versions, reopen XAMPP Control Panel and start Apache. For a Windows service, ensure it receives the restored environment. Database testing remains subject to company-network access.

The historical `tools/php85/install-xampp.ps1` script documents the original local upgrade and requires specific staging files. Use the installation steps above for other developer machines.

<a id="ubuntu-nginx"></a>

## Ubuntu 24.04 with Nginx

Use these steps on **Ubuntu 24.04, amd64**, with an existing Nginx site and a non-root account that has `sudo`. Keep PHP 7.4, the old application and its Nginx configuration until the new release is accepted. Use `php8.5` explicitly for this release.

Run `bash` to open a separate shell, then run the blocks in order in that same shell. The first block stops on errors. If the shell exits, fix the error and restore your variables before continuing; check which steps already completed before repeating them. These are deployment instructions, not commands to run on Windows.

The new application will be at `/srv/gfc/releases/php-8.5.12/gfc`. Keep database passwords out of commands and documentation. For the team's Oracle 11g/12c database, use **Instant Client 19**; confirm that an 11g database is **11.2 or later**, not 11.1.

### 1. Check the server and back up the old installation

Change `GFC_OLD` to the real application path before running this block. Record the backup path and deployment variables in your private deployment notes so you can restore them in a later shell.

```bash
set -euo pipefail
cat /etc/os-release
dpkg --print-architecture
nginx -v
php -v
GFC_PHP_BEFORE=$(readlink -f "$(command -v php)")

GFC_OLD=/var/www/gfc
GFC_RELEASE=/srv/gfc/releases/php-8.5.12
GFC_APP="$GFC_RELEASE/gfc"
GFC_BUILD="$HOME/gfc-php85-build"
GFC_STAMP=$(date +%Y%m%d-%H%M%S)
GFC_BACKUP="/srv/gfc/backups/$GFC_STAMP"
GFC_UPLOAD_DIRS=(uploads assets/da3em/img/uploads)

test -f "$GFC_OLD/index.php"
sudo install -d -m 0700 "$GFC_BACKUP"
sudo tar --acls --xattrs -czpf "$GFC_BACKUP/app-before-php85.tar.gz" -C "$GFC_OLD" .
sudo cp -a /etc/nginx "$GFC_BACKUP/nginx"
sudo cp -a /etc/php "$GFC_BACKUP/php"
printf 'Backup: %s\nPrevious PHP: %s\n' "$GFC_BACKUP" "$GFC_PHP_BEFORE"
```

Confirm that the server reports Ubuntu 24.04 and `amd64`. The backup includes the old application and its dependencies; reverting to PHP 7.4 requires that old application too.

### 2. Install PHP 8.5.12 alongside PHP 7.4

Use the [Ondrej PHP PPA](https://launchpad.net/~ondrej/+archive/ubuntu/php), a third-party repository that must be allowed by your server policy. Do not run a general `apt upgrade` as part of this deployment.

```bash
sudo apt update
sudo apt install software-properties-common ca-certificates curl unzip rsync \
  build-essential autoconf pkg-config libaio1t64 libfcgi-bin netcat-openbsd
sudo add-apt-repository ppa:ondrej/php
sudo apt update
apt-cache madison php8.5-cli php8.5-fpm php8.5-dev
```

The exact version below was verified for Noble on **22 October 2026** in the [package index](https://ppa.launchpadcontent.net/ondrej/php/ubuntu/pool/main/p/php8.5/). If `apt-cache` does not list it, resolve the repository or version with the release owner before continuing.

```bash
GFC_PHP_DEB='8.5.12-1+ubuntu24.04.1+deb.sury.org+1'
GFC_PHP_PACKAGES=(
  php8.5-cli php8.5-common php8.5-fpm php8.5-dev
  php8.5-bcmath php8.5-curl php8.5-gd php8.5-mbstring
  php8.5-xml php8.5-zip php8.5-intl php8.5-soap
)
sudo apt-get install --no-install-recommends "${GFC_PHP_PACKAGES[@]/%/=$GFC_PHP_DEB}"
php8.5 -r 'echo PHP_VERSION, PHP_EOL; exit(PHP_VERSION === "8.5.12" ? 0 : 1);'
php8.5 --ini
```

This selects the installation version without freezing future security updates. Do not install Apache packages on this Nginx server. [OPcache is built into PHP 8.5](https://wiki.php.net/rfc/make_opcache_required): do not add `php8.5-opcache` or copy an old `zend_extension=opcache.so` line.

Installing PHP packages can change the default `php` command automatically. Check the registered alternatives, then restore the previous target if old cron jobs or applications depend on it:

```bash
update-alternatives --list php
php -v
```

Run this next command **only if** `GFC_PHP_BEFORE` appears in that list and you want to preserve the old default. If PHP uses a custom wrapper or path, review that setup instead. Keep using `php8.5` for the commands in this guide.

```bash
sudo update-alternatives --set php "$GFC_PHP_BEFORE"
php -v
```

### 3. Install Oracle Instant Client 19.32 Basic and SDK

Download the **Linux x64 Basic and SDK** packages of the same version. Use the full Basic package to preserve support for legacy database character sets. The [Oracle download page](https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html) provides these files and checksums.

```bash
mkdir -p "$GFC_BUILD"
cd "$GFC_BUILD"
curl -fLO https://download.oracle.com/otn_software/linux/instantclient/1932000/instantclient-basic-linux.x64-19.32.0.0.0dbru.zip
curl -fLO https://download.oracle.com/otn_software/linux/instantclient/1932000/instantclient-sdk-linux.x64-19.32.0.0.0dbru.zip

echo '1749ca1eb5f75b038f2b7ce0abc42eb4575928159259fc4fc851c54a8ca7b002  instantclient-basic-linux.x64-19.32.0.0.0dbru.zip' | sha256sum -c - || exit 1
echo '1698b3be6dde8b7e90501a62122cb9c160c712eb709deb7129d915a504b76158  instantclient-sdk-linux.x64-19.32.0.0.0dbru.zip' | sha256sum -c - || exit 1

sudo install -d -m 0755 /opt/oracle
sudo unzip -q -n instantclient-basic-linux.x64-19.32.0.0.0dbru.zip -d /opt/oracle
sudo unzip -q -n instantclient-sdk-linux.x64-19.32.0.0.0dbru.zip -d /opt/oracle
printf '%s\n' /opt/oracle/instantclient_19_32 | sudo tee /etc/ld.so.conf.d/gfc-oracle-instantclient.conf
sudo ldconfig
ldd /opt/oracle/instantclient_19_32/libclntsh.so
```

If the client directory already exists, confirm its version and completeness first. Do not mix releases or change a client used by other applications without checking their requirements.

**Only if `ldd` reports `libaio.so.1 => not found`:** Ubuntu 24.04 supplies `libaio.so.1t64`. This compatibility link is documented by an [Oracle project](https://oracle.github.io/rwloadsim/INSTALL.html). It does not replace an existing file or link:

```bash
if [ -e /usr/lib/x86_64-linux-gnu/libaio.so.1t64 ] &&
   [ ! -e /usr/lib/x86_64-linux-gnu/libaio.so.1 ] &&
   [ ! -L /usr/lib/x86_64-linux-gnu/libaio.so.1 ]; then
  sudo ln -s /usr/lib/x86_64-linux-gnu/libaio.so.1t64 /usr/lib/x86_64-linux-gnu/libaio.so.1
fi
sudo ldconfig
ldd /opt/oracle/instantclient_19_32/libclntsh.so
```

Resolve every `not found` entry before continuing. Do not install Ubuntu 22.04's old libaio package on 24.04. See the [Ubuntu libaio1t64 file list](https://packages.ubuntu.com/noble/amd64/libaio1t64/filelist).

### 4. Build OCI8 3.4.1 for PHP 8.5 and Client 19

Use the versioned build tools so OCI8 is built for PHP 8.5 rather than the system's default PHP. [OCI8 3.4.1 supports PHP 8.5](https://pecl.php.net/package/oci8); the [PHP installation guide](https://www.php.net/manual/en/oci8.installation.php) documents building it with Instant Client.

```bash
if ldd /opt/oracle/instantclient_19_32/libclntsh.so | grep 'not found'; then
  echo 'STOP: resolve missing Oracle Client libraries first' >&2
  exit 1
fi
cd "$GFC_BUILD"
curl -fLO https://pecl.php.net/get/oci8-3.4.1.tgz
tar -xzf oci8-3.4.1.tgz
cd oci8-3.4.1
/usr/bin/phpize8.5
./configure --with-php-config=/usr/bin/php-config8.5 \
  --with-oci8=shared,instantclient,/opt/oracle/instantclient_19_32
make -j"$(nproc)"
sudo make install
printf '%s\n' 'extension=oci8.so' | sudo tee /etc/php/8.5/mods-available/oci8.ini
sudo phpenmod -v 8.5 -s cli oci8
sudo phpenmod -v 8.5 -s fpm oci8
php8.5 --ri oci8
ldd "$(php-config8.5 --extension-dir)/oci8.so"
```

Expected: OCI8 **3.4.1**, compile-time Oracle Client **19.x**, runtime client **19.32**, and no missing libraries. These checks do not connect to Oracle. Do not copy the old PHP 7.4 extension into PHP 8.5.

### 5. Extract the application and install Composer dependencies

Upload the release ZIP to `~/gfc.zip`, outside the published site. The example assumes that the ZIP contains a `gfc` folder. If it contains `index.php` directly, use `GFC_SOURCE="$GFC_UNPACK"` in the block below instead.

```bash
GFC_UNPACK=$(mktemp -d "$HOME/gfc-php85-unpack.XXXXXX")
unzip -q "$HOME/gfc.zip" -d "$GFC_UNPACK"
GFC_SOURCE="$GFC_UNPACK/gfc"
test -f "$GFC_SOURCE/index.php"
test -f "$GFC_SOURCE/composer.lock"

test ! -e "$GFC_RELEASE"
sudo install -d -m 0755 /srv/gfc /srv/gfc/releases "$GFC_RELEASE"
sudo install -d -o "$USER" -g www-data -m 0750 "$GFC_APP"
sudo rsync -a --chown="$USER":www-data \
  --exclude='/.git/' --exclude='/temp/' --exclude='/uploads/' --exclude='/vendor/' \
  --exclude='/assets/da3em/img/uploads/' \
  "$GFC_SOURCE/" "$GFC_APP/"
cd "$GFC_APP"
```

The release directory must be new. If it already exists, inspect the previous attempt before continuing. The leading `/` in `/vendor/` is intentional: exclude only the root dependency directory and retain `application/third_party/phpoffice/vendor/autoload.php`, which older Excel callers still need.

Install Composer 2 as your normal account, then install the locked dependencies with PHP 8.5. Validate the installer using the [official Composer signature](https://getcomposer.org/download/):

```bash
cd "$GFC_BUILD"
curl -fsSLo composer-setup.php https://getcomposer.org/installer
GFC_COMPOSER_SHA=$(curl -fsSL https://composer.github.io/installer.sig)
printf '%s  composer-setup.php\n' "$GFC_COMPOSER_SHA" | sha384sum -c - || exit 1
php8.5 composer-setup.php --2 --install-dir="$GFC_BUILD" --filename=composer.phar

cd "$GFC_APP"
php8.5 "$GFC_BUILD/composer.phar" install --no-dev --prefer-dist --optimize-autoloader
php8.5 "$GFC_BUILD/composer.phar" check-platform-reqs --no-dev
```

Run Composer from the project root, without `sudo`. Use `install`, not `update`, and do not add `--no-scripts` or `--ignore-platform-reqs`. The install script applies the required Jasper compatibility fixes.

### 6. Restore settings, uploads and permissions

Follow [Application settings](INSTALL.md#application-settings). Transfer environment values from the old installation while retaining the new code fixes. Linux filenames are case-sensitive.

Copy the real server uploads. Pause writes and uploads on the old site before the final synchronization in your deployment window. Add any extra upload paths used by your installation to `GFC_UPLOAD_DIRS` before running the copy and rollback steps.

```bash
for GFC_DIR in "${GFC_UPLOAD_DIRS[@]}"; do
  sudo install -d "$GFC_APP/$GFC_DIR"
  if [ -d "$GFC_OLD/$GFC_DIR" ]; then
    sudo rsync -a "$GFC_OLD/$GFC_DIR/" "$GFC_APP/$GFC_DIR/"
  fi
done

GFC_WRITABLE=("${GFC_UPLOAD_DIRS[@]}" temp application/cache application/logs barcode/qrcode/cache)
for GFC_DIR in "${GFC_WRITABLE[@]}"; do
  sudo mkdir -p "$GFC_APP/$GFC_DIR"
done
sudo chown -R "$USER":www-data "$GFC_APP"
sudo find "$GFC_APP" -type d -exec chmod 0750 {} +
sudo find "$GFC_APP" -type f -exec chmod 0640 {} +
for GFC_DIR in "${GFC_WRITABLE[@]}"; do
  sudo chown -R www-data:www-data "$GFC_APP/$GFC_DIR"
done
sudo install -d -o www-data -g www-data -m 0700 /var/lib/php/gfc-sessions /var/lib/php/gfc-tmp
```

Do not use `chmod -R 777`. Give the web worker write access only to required data directories. Old sessions are not copied; users must sign in again.

### 7. Configure the project's PHP-FPM pool

Copy the [FPM template](tools/php85/deploy/gfc-fpm.conf). It creates the project's own socket and leaves PHP 7.4 configuration in place:

```bash
sudo cp "$GFC_APP/tools/php85/deploy/gfc-fpm.conf" /etc/php/8.5/fpm/pool.d/gfc.conf
sudo nano /etc/php/8.5/fpm/pool.d/gfc.conf
```

Before saving:

- Set `date.timezone` to the old server's value. The template's `Europe/Berlin` value is a local reference.
- Preserve any custom `max_input_vars` setting with `php_admin_value[max_input_vars]` for large forms.
- Start with `pm.max_children=5` and `memory_limit=512M`, then size the pool for available RAM and report workloads.
- The examples use `www-data` for Nginx/FPM. If your server uses another account, update the worker, socket and directory ownership together.
- Set `env[TNS_ADMIN]` only when using TNS files in a separate directory. Do not set `ORACLE_HOME` for Instant Client or change the database character set during this migration.
- Keep `CI_ENV=production` in the pool and Nginx configuration to avoid showing internal errors to visitors.

```bash
sudo php-fpm8.5 -t
sudo systemctl enable --now php8.5-fpm
sudo systemctl restart php8.5-fpm
sudo systemctl --no-pager --full status php8.5-fpm
ls -l /run/php/php8.5-fpm-gfc.sock
```

Future PHP updates may require rebuilding OCI8. Check the extension before reopening the application after an update.

### 8. Check CLI and FPM without connecting to Oracle

```bash
cd "$GFC_APP"
sudo -u www-data php8.5 tools/php85/check.php
sudo -u www-data php8.5 tools/php85/scan-syntax.php
sudo -u www-data php8.5 -n tests/php85/mx_compat_test.php
sudo -u www-data php8.5 tests/php85/dependencies_test.php
```

Also check **FPM itself**. This temporary script is outside the web directory and is called through the local socket. It does not connect to Oracle or publish a `phpinfo()` page:

```bash
sudo tee /srv/gfc/runtime-check.php >/dev/null <<'PHP'
<?php
header('Content-Type: application/json');
echo json_encode([
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'ini' => php_ini_loaded_file(),
    'oci8' => phpversion('oci8'),
    'oracle_client' => function_exists('oci_client_version') ? oci_client_version() : null,
    'gd' => extension_loaded('gd'),
    'zip' => extension_loaded('zip'),
    'mbstring' => extension_loaded('mbstring'),
    'session_writable' => is_writable(session_save_path()),
], JSON_PRETTY_PRINT);
PHP
sudo chmod 0644 /srv/gfc/runtime-check.php
sudo env SCRIPT_FILENAME=/srv/gfc/runtime-check.php \
  SCRIPT_NAME=/runtime-check.php REQUEST_METHOD=GET \
  cgi-fcgi -bind -connect /run/php/php8.5-fpm-gfc.sock
sudo rm -- /srv/gfc/runtime-check.php
```

Expected: `php=8.5.12`, `sapi=fpm-fcgi`, `oci8=3.4.1`, `oracle_client=19.32...`, and `true` for the extension and session checks. If CLI and FPM differ, check `/etc/php/8.5/cli`, `/etc/php/8.5/fpm` and the enabled `oci8.ini`, then restart FPM.

### 9. Update the existing Nginx site and switch releases

The [Nginx template](tools/php85/deploy/nginx-gfc.conf) supports `/gfc/login`, `/gfc/index.php/login` and direct entry points such as `tutorial/tutorial_show.php`. Nginx does not read `.htaccess`; the template blocks private directories and PHP execution in the known upload directories. Adjust its paths if you changed `GFC_RELEASE`, and extend the upload protection if your installation uses additional upload paths.

```bash
sudo cp "$GFC_APP/tools/php85/deploy/nginx-gfc.conf" /etc/nginx/snippets/gfc-php85.conf
sudo nginx -T
ls -l /etc/nginx/sites-enabled/
```

Find the actual site configuration containing the existing hostname and HTTPS certificate. Set `GFC_SITE` below to that file; `/etc/nginx/sites-available/gfc` is only an example. Record the path with your deployment notes.

```bash
GFC_SITE=/etc/nginx/sites-available/gfc
sudo cp -a "$GFC_SITE" "$GFC_BACKUP/site-before-php85.conf"
sudo nano "$GFC_SITE"
```

Inside the existing **HTTPS `server {}` block**, replace the old `/gfc` locations with:

```nginx
include /etc/nginx/snippets/gfc-php85.conf;
```

Keep the existing `listen 443 ssl`, hostname, certificates and HTTP-to-HTTPS redirect. Remove conflicting old `/gfc` locations. This template changes only GFC to the new release directory and `/run/php/php8.5-fpm-gfc.sock`; leave other sites on their existing PHP versions.

After the settings, final upload synchronization and offline checks pass, switch during the agreed deployment window:

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl --no-pager --full status nginx
```

Once access to the Oracle network is authorized, test the company's actual URL at `/gfc/index.php/login` and `/gfc/login`. Check CSS/JS, sign-in, permissions, Excel import/export, uploads and reports. A static login preview does not verify database access. Use the database checks under [Application settings](INSTALL.md#application-settings) only when network access is available.

### 10. Check logs or roll back

```bash
sudo journalctl -u php8.5-fpm -n 80 --no-pager
sudo tail -n 80 /var/log/nginx/error.log
sudo ls -lt "$GFC_APP/application/logs"
```

To roll back, first pause writes and uploads in both versions. If this is a new shell, restore the saved deployment variables, including `GFC_UPLOAD_DIRS`, and enable `set -euo pipefail`. Keep the new release and all backups.

Save the new application's state and preview the uploads that will be copied back:

```bash
test -f "$GFC_APP/index.php"
test -f "$GFC_OLD/index.php"
GFC_ROLLBACK="$GFC_BACKUP/rollback-$(date +%Y%m%d-%H%M%S)"
sudo install -d -m 0700 "$GFC_ROLLBACK"
sudo tar --acls --xattrs -czpf "$GFC_ROLLBACK/new-app-before-rollback.tar.gz" -C "$GFC_APP" .
for GFC_DIR in "${GFC_UPLOAD_DIRS[@]}"; do
  if [ -d "$GFC_APP/$GFC_DIR" ]; then
    sudo mkdir -p "$GFC_OLD/$GFC_DIR"
    sudo rsync -ani "$GFC_APP/$GFC_DIR/" "$GFC_OLD/$GFC_DIR/"
  fi
done
```

Review that file list before running the actual copy. Replaced old files are retained in `overwritten`. These commands do not delete old files: review any intentional deletions made after deployment separately.

```bash
for GFC_DIR in "${GFC_UPLOAD_DIRS[@]}"; do
  if [ -d "$GFC_APP/$GFC_DIR" ]; then
    sudo rsync -a --backup --backup-dir="$GFC_ROLLBACK/overwritten/$GFC_DIR" \
      "$GFC_APP/$GFC_DIR/" "$GFC_OLD/$GFC_DIR/"
  fi
done
```

Check upload ownership for the old FPM account and confirm its service/socket is available. Then restore the saved site configuration:

```bash
sudo cp -a "$GFC_BACKUP/site-before-php85.conf" "$GFC_SITE"
sudo nginx -t
sudo systemctl reload nginx
```

Restore the **old application path and old PHP socket together**. Do not run the new application and dependencies on PHP 7.4. Leave PHP 8.5 running if another application uses it.

These Ubuntu instructions and templates were reviewed against the project and official documentation; they have not been executed on an Ubuntu server as part of preparing this guide. Production acceptance still requires testing the actual server and authorized Oracle connections.

## Application settings

Apply this section before opening the real application. Transfer the environment values from the old installation into the updated files. Keep the updated PHP code, CI core, MX, and compatibility fixes.

The prepared developer ZIP replaces private values with `REQUIRED_...` placeholders. Follow the [complete package configuration checklist](tools/php85/developer-package-setup.md) for the exact files and fields, including Jasper and optional integrations. Use the approved settings from your environment; do not attempt sign-in or Oracle checks with placeholders. The package does not deploy SQL migrations or change database permissions.

| File or service | What to preserve or configure |
|---|---|
| `application/config/config.php` | The site's base URL/path, session settings, existing encryption key, and approved environment settings. The current application uses `/gfc`. |
| `application/config/database.php` | CodeIgniter database settings where the framework connection is used. |
| `application/libraries/DBConn.php` | The main connection's username, password, and `instance` value. Retain the updated connection code and `AL32UTF8`. |
| `application/core/GISConn.php` and `application/core/BillsConn.php` | GIS and billing connection values used by those modules. |
| Jasper, email, and other services | The actual server URLs, credentials, certificates, and environment settings. |
| Uploaded files | Existing files in `uploads`, `assets/da3em/img/uploads`, and any additional storage paths used by your installation. |

**Changing `database.php` alone is not enough** when the application connects through `DBConn.php`. Copy the required values, not entire old PHP files. This release does not automatically load these values from `.env`; creating that file alone will not change the connection. Keep passwords out of shared documentation and command history.

For an Easy Connect string such as `host:1521/service`, or a complete connection descriptor, `tnsnames.ora` is usually unnecessary. For a TNS alias, ask the database administrator for the existing `tnsnames.ora`, `sqlnet.ora`, and `TNS_ADMIN` directory. That directory must be readable by **Apache or PHP-FPM**, not only by your terminal. Preserve existing wallet and encryption settings where used.

### Database checks when network access is available

Replace `ORACLE_HOST` with the actual database address. Adjust port `1521` if your listener uses a different port. These commands do not enable VPN.

**Windows:** use the same PowerShell session and variables from the installation steps:

```powershell
Set-Location -LiteralPath $GfcApp
if (-not (Test-NetConnection -ComputerName 'ORACLE_HOST' -Port 1521 -InformationLevel Quiet)) {
    throw 'Oracle TCP port is unreachable. Check the company network, VPN, firewall, and listener.'
}
Invoke-GfcNative -FilePath $GfcPhp -ArgumentList @('tools/php85/check-oracle.php')
```

**Ubuntu:** use the same Bash session and variables from the installation steps:

```bash
nc -vz -w 5 ORACLE_HOST 1521
cd "$GFC_APP"
sudo -u www-data php8.5 tools/php85/check-oracle.php
```

`check-oracle.php` uses the default connection values in `DBConn.php` and runs the read-only query `SELECT 1 FROM DUAL`. A successful result confirms that connection only. Then test real sign-in, permissions, the required modules, Excel import/export, reports, uploads, and external services. Check GIS and billing connections separately where used.

`ORA-12170` is a connection timeout. Check the network route, VPN, firewall, listener, and database availability. Increasing PHP's execution timeout does not restore an unreachable Oracle port.

## Troubleshooting

| Problem | First check |
|---|---|
| `php -v` still reports 7.4 | Use the full `php8512/php.exe` path on Windows or `php8.5` on Ubuntu. Check Apache/FPM separately. |
| `Unable to load dynamic library` or `oci.dll not found` | Windows: PHP/OCI8 must be 8.5 TS/x64, with Oracle Client 19.32 visible to Apache. Linux: check `ldd` and `ldconfig`. |
| OCI8 compile-time client is `23.x` | The wrong Windows DLL is installed. Copy the included Client 19 build into the new PHP `ext` directory. |
| OCI8 runtime client is not `19.32` | Another Oracle Client was loaded. Review PATH on Windows or the linker configuration on Linux, then restart the web process. |
| `oci_connect` is undefined | OCI8 is not enabled in the PHP runtime handling the request. CLI success alone does not verify Apache/FPM. |
| Composer platform check fails | Enable the missing extension in the correct PHP installation. Keep the platform checks enabled. |
| Apache fails after the PHP switch | Check its error log, module paths, Visual C++ runtime, and the DLL preload notes in Windows step 4. |
| Nginx returns `502` | Check FPM status, the socket path, and socket permissions. |
| Nginx returns `404` or `Primary script unknown` | Check the release path, `root`, `SCRIPT_FILENAME`, and `PATH_INFO`. Avoid an accidental `gfc/gfc` folder. |
| Nginx returns `413` | Check `client_max_body_size`, `post_max_size`, and `upload_max_filesize`. The template allows a 40 MB file with room for the full request. |
| Sessions, uploads, or Excel operations fail | Check writable session/temp/upload paths, required extensions, and application template files. |
| `ORA-12154` | Check the TNS alias, `TNS_ADMIN`, and whether the web process can read its files. |
| `ORA-12170` | Check company-network access, listener port, and firewall rules. |

## Verification and references

The Windows Client 19.32 DLL passed isolated CLI and Apache checks on PHP 8.5.12 without connecting to Oracle. Those build checks did not replace the DLL in the running local XAMPP installation. Follow the installation steps to deploy it on each machine.

The Ubuntu commands and templates were reviewed, but have not been executed on the target Ubuntu server. Database and business acceptance remain separate from syntax checks, extension loading, and the static login preview.

- [OCI8 build report](tools/php85/oci8-client19-build.md)
- [Optional Windows OCI8 rebuild guide](tools/php85/windows-oci8-client19.md)
- [PHP 8.5.12 release](https://www.php.net/releases/8_5_12.php)
- [PHP with Apache on Windows](https://www.php.net/manual/en/install.windows.apache2.php)
- [OCI8 requirements](https://www.php.net/manual/en/oci8.requirements.php) and [installation](https://www.php.net/manual/en/oci8.installation.php)
- [Composer install](https://getcomposer.org/doc/03-cli.md#install-i)
- [PHP-FPM configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Nginx FastCGI](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_split_path_info) and [try_files](https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files)
- [Earlier project upgrade log](tools/php85/README.md) and [CI merge record](tools/php85/ci3113-upgrade.md), retained as historical records in Arabic
