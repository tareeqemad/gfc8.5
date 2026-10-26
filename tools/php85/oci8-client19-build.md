# OCI8 build report: Oracle Client 19

**The build and offline acceptance checks completed on October 22, 2026.** Developers can use the [ready ZIP package](packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip) and copy `php_oci8_19.dll` into PHP's `ext` directory by following the [installation guide](../../INSTALL.md). C++ build tools and the Oracle SDK are not required to install this package.

## Build configuration

| Component | Verified value |
|---|---|
| Extension source | Official OCI8 3.4.1, with no source changes |
| PHP tested | 8.5.12 / Thread Safe / VS17 / x64 |
| Build tools | Visual Studio Build Tools 2022, version 17.14.42 |
| Compiler | MSVC 19.44.35229.0, toolset 14.44.35207 |
| Windows SDK | 10.0.26100.0 |
| Oracle SDK at build time | 19.32 |
| Oracle Client at run time | 19.32.0.0.0, Basic x64 |
| Output | `php_oci8_19.dll`, Release, x64; imports `php8ts.dll` and `OCI.dll` |

The required Microsoft build tools were installed on the build machine. The build and checks used PHP and Oracle files under `temp/php85/oci8-client19-build`. This step did not replace the DLL in the running XAMPP installation or change its Apache configuration or permanent Oracle PATH settings.

## Acceptance results

- `php --ri oci8` succeeded and reported OCI8 3.4.1, compile-time Client **19.32**, and run-time Client **19.32.0.0.0**.
- The CLI acceptance script passed using the DLL copied into the distribution staging directory. It confirmed PHP 8.5.12, Thread Safe mode, 64-bit architecture, and the availability of the required OCI8 functions.
- The DLL loaded successfully through Apache 2.4.54 Win64 and `apache2handler` in **five localhost requests**. The check used a temporary Apache copy, an available port, and a separate `opcache.cache_id`; the test process was stopped afterward. The running Apache instance on port 8084 was neither changed nor restarted.
- `dumpbin` confirmed x64 architecture, imports from `php8ts.dll`, `OCI.dll`, and the Microsoft C runtime, and the ASLR, NX, and Control Flow Guard flags.
- All nine files inside the ZIP were compared by hash with the accepted distribution contents and matched.
- The application page and VPN were not started, and no Oracle database connection was created. These checks establish that the extension builds and loads. Login, queries, Arabic text, LOB/cursors, and reports still require testing against the company's database when network access is available.

## Package and checksums

ZIP size: **73275 bytes**. The package contains the DLL, an English `INSTALL.md`, `LICENSE`, `CREDITS`, `BUILD-INFO.json`, CLI and Apache acceptance results, `accept-offline.php`, and `SHA256SUMS.txt`. Oracle Basic is a separate download; use the Oracle link in the [installation guide](../../INSTALL.md).

The package was repacked to translate `INSTALL.md` into English and refresh its checksum. The DLL, build metadata, acceptance records, licenses, and offline check script are byte-for-byte unchanged.

```text
oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip
SHA256 046A86199BE3D7F1107ADE26D46B96880B49443E84C3EE0C279FB61C10820F3E

php_oci8_19.dll
SHA256 F60D475675B08C214820A86CC06FC72CD6C3CB64B38D5C5720F90BC2E5ED1D36
```

Detailed build and check records are stored locally under `temp/php85/oci8-client19-build`: `build.log`, `build-task.bat`, `dll-headers.txt`, `dll-dependents.txt`, and `apache-acceptance.json`. The `temp` directory does not need to be distributed; the package includes acceptance results and checksums.

## Rebuilding and references

The procedure is in the [Windows build guide](windows-oci8-client19.md). The build used the development pack matching the official PHP binary, [PHP SDK 2.8.4](https://github.com/php/php-sdk-binary-tools/releases/tag/php-sdk-2.8.4), and the [OCI8 3.4.1 source](https://pecl.php.net/package/oci8/3.4.1). PHP documents the method in [Building PECL extensions with phpize on Windows](https://wiki.php.net/internals/windows/stepbystepbuild_sdk_2#building_pecl_extensions_with_phpize).

The unattended build used `phpsdk-starter.bat -c vs17 -a x64 -t <build-task.bat>`. The task runs `phpize`, then `configure` with `--with-oci8-19=shared,<Client19>/sdk`, and finally `nmake`, stopping if any step fails. Checksums of all input packages are recorded in `BUILD-INFO.json` inside the ZIP.

Client 19 compatibility with Oracle Database **11.2 or later** is documented in the [OCI8 requirements](https://www.php.net/manual/en/oci8.requirements.php). Confirm that any database described as 11g is version 11.2, not 11.1. This package was built separately from the official precompiled DLL inspected earlier, which used Client 23.8.
