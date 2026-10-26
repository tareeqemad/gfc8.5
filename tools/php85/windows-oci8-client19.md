# OCI8 for PHP 8.5.12 and Oracle Client 19: ready package and build guide

For normal installation, use the **[ready ZIP package](packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip)** and copy `php_oci8_19.dll` into PHP's `ext` directory by following the [installation guide](../../INSTALL.md). Developers do not need Visual Studio, the Oracle SDK, or a C++ build. The numbered steps below are for the release maintainer if the package needs to be rebuilt.

A separate build was needed because the official precompiled DLL inspected on October 22, 2026 is named `php_oci8_19.dll` but was built with Client 23.8. The team's database is Oracle 11g/12c, so this package was built with Client 19.32. Any database described as 11g must still be confirmed as 11.2 or later.

**Status on October 22, 2026: the build completed and CLI and temporary Apache checks passed** on PHP 8.5.12 TS x64, with OCI8 3.4.1, Oracle compile-time Client **19.32**, and run-time Client **19.32.0.0.0**. No Oracle connection was created and no VPN was started. The build used Visual Studio 2022 Build Tools **17.14.42**, compiler **19.44.35229**, and Windows SDK **10.0.26100**. See the [build and acceptance report](oci8-client19-build.md).

SHA256 of `php_oci8_19.dll` inside the package:

```text
F60D475675B08C214820A86CC06FC72CD6C3CB64B38D5C5720F90BC2E5ED1D36
```

SHA256 of the ZIP package itself:

```text
046A86199BE3D7F1107ADE26D46B96880B49443E84C3EE0C279FB61C10820F3E
```

The running XAMPP installation's DLL and Client 21.19 were not replaced during the build. The acceptance checks establish that the package loads in the isolated environment. Testing against the real Oracle database remains pending until network access is available.

## 1. Build tools and downloads

Use a Windows x64 machine and new build directories. Keep the build outside the running XAMPP PHP directory.

1. Install [Visual Studio 2022 Build Tools](https://aka.ms/vs/17/release/vs_BuildTools.exe) with C++ build tools, MSVC v143 x64/x86, and the Windows SDK. Use an updated 2022 release; the inspected PHP development pack identifies compiler `1944`. See [Microsoft's installation instructions](https://learn.microsoft.com/en-us/cpp/build/vscpp-step-0-installation?view=msvc-170).
2. Install Git from its [official download page](https://git-scm.com/downloads/win) if it is not already available.
3. Download these packages into your Downloads directory:

| Package | Download |
|---|---|
| PHP runtime 8.5.12 TS VS17 x64 | [php-8.5.12-Win32-vs17-x64.zip](https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip) |
| Development pack **matching PHP version, TS, VS17, and x64** | [php-devel-pack-8.5.12-Win32-vs17-x64.zip](https://downloads.php.net/~windows/releases/archives/php-devel-pack-8.5.12-Win32-vs17-x64.zip) |
| OCI8 3.4.1 source | [oci8-3.4.1.tgz](https://pecl.php.net/get/oci8-3.4.1.tgz) |
| Oracle Basic 19.32 x64 | [Basic ZIP](https://download.oracle.com/otn_software/nt/instantclient/1932000/instantclient-basic-windows.x64-19.32.0.0.0dbru.zip) |
| Oracle SDK 19.32 x64 | [SDK ZIP](https://download.oracle.com/otn_software/nt/instantclient/1932000/instantclient-sdk-windows.x64-19.32.0.0.0dbru.zip) |

Verified package checksums:

```text
PHP runtime:
660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184
PHP development pack:
82977ddb4cc7173a3e34822e4ceb72d2d942b8d7ed01ce302d1c59b494425aef
Oracle Basic 19.32:
e95a66eb4e69e2fb28880cbd252e8eab9807b877c4248c0d50a28f7b75cfe878
Oracle SDK 19.32:
d869d08ddddbfb2fabc6f4632ddb156ce2f18dd771006182a8682950feddfe12
```

In PowerShell, calculate the checksums with `Get-FileHash -Algorithm SHA256` and compare them before extracting the archives. For example:

```powershell
Get-FileHash "$env:USERPROFILE\Downloads\php-devel-pack-8.5.12-Win32-vs17-x64.zip" -Algorithm SHA256
Get-FileHash "$env:USERPROFILE\Downloads\instantclient-sdk-windows.x64-19.32.0.0.0dbru.zip" -Algorithm SHA256
```

## 2. Prepare the directories

Run the following in PowerShell. `C:\php-sdk` must not exist before cloning, and the runtime directory must be new. If files from an earlier build are present, inspect them before proceeding:

```powershell
$ErrorActionPreference = 'Stop'
if (Test-Path -LiteralPath 'C:\php-sdk') { throw 'Inspect the existing SDK directory before building' }
if (Test-Path -LiteralPath 'C:\php8512-build') { throw 'Inspect the existing runtime directory before building' }
git -c core.autocrlf=false clone --branch php-sdk-2.8.4 --depth 1 https://github.com/php/php-sdk-binary-tools.git C:\php-sdk
if ($LASTEXITCODE -ne 0) { throw 'PHP SDK clone failed' }
$GfcBuildDownloads = "$env:USERPROFILE\Downloads"
Expand-Archive -LiteralPath "$GfcBuildDownloads\php-8.5.12-Win32-vs17-x64.zip" -DestinationPath 'C:\php8512-build'
Expand-Archive -LiteralPath "$GfcBuildDownloads\php-devel-pack-8.5.12-Win32-vs17-x64.zip" -DestinationPath 'C:\php-sdk'
tar -xzf "$GfcBuildDownloads\oci8-3.4.1.tgz" -C C:\php-sdk
if ($LASTEXITCODE -ne 0) { throw 'OCI8 source extraction failed' }
New-Item -ItemType Directory -Path 'C:\oracle' -Force
Expand-Archive -LiteralPath "$GfcBuildDownloads\instantclient-basic-windows.x64-19.32.0.0.0dbru.zip" -DestinationPath 'C:\oracle'
Expand-Archive -LiteralPath "$GfcBuildDownloads\instantclient-sdk-windows.x64-19.32.0.0.0dbru.zip" -DestinationPath 'C:\php-sdk\oracle-sdk-19_32'
if (Test-Path -LiteralPath 'C:\oracle\instantclient_19_32\sdk') { throw 'Inspect the existing Oracle SDK before copying' }
Copy-Item -LiteralPath 'C:\php-sdk\oracle-sdk-19_32\instantclient_19_32\sdk' -Destination 'C:\oracle\instantclient_19_32\sdk' -Recurse
```

If Basic 19.32 was already installed using the main installation guide, skip its extraction, verify its version, and add only the matching SDK. Extract the SDK ZIP into a staging directory, then copy its `sdk` subdirectory: both the Basic and SDK archives contain shared `META-INF` files, so extracting both directly into the same destination with PowerShell causes file conflicts. Keep the original archives and their license files with the build materials. These files must be present:

```text
C:\php8512-build\php.exe
C:\php8512-build\php8ts.dll
C:\php-sdk\phpsdk-vs17-x64.bat
C:\php-sdk\php-8.5.12-devel-vs17-x64\phpize.bat
C:\php-sdk\php-8.5.12-devel-vs17-x64\lib\php8ts.lib
C:\php-sdk\oci8-3.4.1\config.w32
C:\oracle\instantclient_19_32\oci.dll
C:\oracle\instantclient_19_32\sdk\include\oci.h
C:\oracle\instantclient_19_32\sdk\include\ociver.h
C:\oracle\instantclient_19_32\sdk\lib\msvc\oci.lib
```

## 3. Build inside the PHP SDK shell

Open **CMD** and run:

```bat
C:\php-sdk\phpsdk-vs17-x64.bat
```

Run the remaining commands inside the shell prepared by the SDK. The following block uses **CMD syntax**:

```bat
set "PATH=C:\oracle\instantclient_19_32;C:\php8512-build;C:\php-sdk\php-8.5.12-devel-vs17-x64;%PATH%"
cd /d C:\php-sdk\oci8-3.4.1

where cl
where nmake
where phpize
where oci.dll
C:\php8512-build\php.exe -n -v

call C:\php-sdk\php-8.5.12-devel-vs17-x64\phpize.bat
call configure.bat --help
call configure.bat --with-prefix=C:\php8512-build --with-php-build=C:\oracle\instantclient_19_32\sdk --with-oci8-19=shared,C:\oracle\instantclient_19_32\sdk
nmake
```

Stop if any command fails. The output must show that `oci.lib` and the headers were selected from the **19.32 SDK** and that OCI8 is enabled as `shared`. A successful configure command alone is insufficient if OCI8 was not enabled.

The Windows option is `--with-oci8-19=shared,...\sdk`, as defined in [OCI8 3.4.1's config.w32](https://github.com/php/pecl-database-oci8/blob/v3.4.1/config.w32). The Linux option `--with-oci8=shared,instantclient,...` does not apply here. The development pack supplies `TS` and `php8ts.lib`; do not add options for building PHP itself, such as `--enable-cli` or `--enable-zts`, to this extension build.

This procedure uses the specified PHP development pack and Oracle SDK; it does not require an automatic download of Oracle 23 dependencies. References: [Building PECL extensions with phpize on Windows](https://wiki.php.net/internals/windows/stepbystepbuild_sdk_2#building_pecl_extensions_with_phpize) and the [official PHP SDK](https://github.com/php/php-sdk-binary-tools/releases/tag/php-sdk-2.8.4).

## 4. Verify the DLL before distribution

Expected output: `C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll`. If the build log reports a different directory, use the newly generated file identified by that log. Do not select an older DLL found on PATH.

In the same SDK shell, run:

```bat
dumpbin /headers C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll
dumpbin /dependents C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll

C:\php8512-build\php.exe -n -d extension=C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll --ri oci8
C:\php8512-build\php.exe -n -d extension=C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll -r "echo PHP_VERSION,' ',(PHP_ZTS ? 'TS' : 'NTS'),' ',PHP_INT_SIZE*8,'-bit OCI8=',phpversion('oci8'),' Client=',oci_client_version(),PHP_EOL;"
```

All acceptance conditions must be met:

- PHP reports `8.5.12 TS 64-bit` and OCI8 `3.4.1`, without startup warnings.
- The DLL is x64 and imports `php8ts.dll`.
- **Oracle Compile-time Instant Client Version = 19.32**.
- **Oracle Run-time Client Library Version = 19.32.0.0.0**.
- No database connections are made during these checks. Keep the full OCI8 tests that require a database outside the offline acceptance procedure.

If the compile-time version is `23.8`, the file is not a Client 19 build and must not be distributed as one. If the run-time version is `21` or `23`, check the DLL loaded by the process and the PATH order. If the build or verification fails, leave that build unapproved; loading a different DLL successfully does not establish that it meets these requirements.

## 5. Ready package and future releases

The current package is ready: [oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip](packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip). See the [build report](oci8-client19-build.md) for details. The ZIP contains nine files at its root: the DLL, `LICENSE`, `CREDITS`, `INSTALL.md`, `BUILD-INFO.json`, `ACCEPTANCE-cli.json`, `ACCEPTANCE-apache.json`, `accept-offline.php`, and `SHA256SUMS.txt`.

Developers should verify the ZIP checksum, extract it into a temporary directory, compare the DLL checksum with the value above, and copy only `php_oci8_19.dll` into `C:\xampp\php8512\ext`. Install **Oracle Basic 19.32** and complete the [installation guide](../../INSTALL.md). Keep the remaining package files for documentation and licensing. No SDK or C++ build tools are needed for installation.

For a future release, repeat the acceptance checks and package the resulting DLL with the source license and credits, its SHA256 checksum, and a record of PHP/TS/VS17/x64, the Oracle SDK version, compiler version, configure command, and acceptance results. Calculate the new DLL checksum in PowerShell:

```powershell
Get-FileHash 'C:\php-sdk\oci8-3.4.1\x64\Release_TS\php_oci8_19.dll' -Algorithm SHA256
```

Loading the extension does not replace later tests against the real Oracle 11.2/12c database, including Arabic text, LOB/cursors, and reports, once network access is available. This procedure does not start a VPN.
