# ترقية GFC إلى PHP 8.5.12

تم تنفيذ الترقية المحلية بتاريخ 2026-10-22.

مراجعة قراءة المستخدم والصلاحيات عبر جميع الـmodules بتاريخ 2026-10-23 موثقة في [تقرير سياق المستخدم واختباراته](user-context-audit.md).

نتائج الاختبار الفعلي عبر VPN للنظام المالي وJasper والصرف والحضور، والإصلاحات والملاحظة المتبقية، في [تقرير فحص الشاشات](live-screen-check-2026-10-23.md).

دمج مجلد production بتاريخ 26 أكتوبر وتجهيز حزمة المبرمجين موثّق بالإنجليزية في [سجل الدمج](production-merge-2026-10-26.md)، مع [دليل إعداد القيم الخاصة](developer-package-setup.md) لنسخة ZIP.

المقارنة اللاحقة بتاريخ 24 أكتوبر، بعد تحسين اتصال أول controller، موثقة في [تقرير القياس والاختبار الفعلي](live-screen-check-2026-10-24.md): انخفضت الاتصالات من 3 إلى 2، لكن تحسن السرعة غير عام. نجحت الاستعلامات وتقريرا Jasper، وبقي بطء أرشيف السندات ومشكلة سجل طلبات النوع 4.

لخطوات تسليم ZIP للمبرمجين ومسؤول Ubuntu 24.04/Nginx، راجع [دليل النشر في جذر المشروع](../../INSTALL.md). هذا الملف سجل الترقية المحلية، وليس وصفة نسخ إعداداتها إلى كل بيئة.

**مراجعة توافق Oracle عند إعداد دليل النشر:** أكد الفريق أن قاعدة الهدف 11g/12c. أظهر فحص DLL المحلي والمصدر الرسمي المطابق له أنه مبني بـOracle Client 23.8، بينما Client التشغيل المحلي 21.19. لذلك لا يُعتمد هذا الزوج للنشر على القاعدة القديمة لمجرد نجاح تحميله محليًا. **بُني البديل باستخدام Client 19.32 وأصبحت [حزمة OCI8 الجاهزة](packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip) مرفقة للمبرمجين**؛ يفكون `php_oci8_19.dll` وفق دليل النشر ولا يحتاجون أدوات C++ أو Oracle SDK. راجع [تقرير البناء والقبول](oci8-client19-build.md) و[وصفة إعادة البناء](windows-oci8-client19.md). إعدادات XAMPP العامل وDLL القديم وClient 21.19 بقيت كما هي.

## بيئة التشغيل

- PHP الجديد: `C:\xampp744\php8512\php.exe`، ‏x64 Thread Safe / VS17.
- Apache الخاص بالمشروع: `C:\xampp744\apache\bin\httpd.exe`، والمنفذ `8084`.
- عنوان المشروع: http://127.0.0.1:8084/gfc/
- OCI8: الإصدار `3.4.1`؛ Oracle Instant Client الموجود: `21.19`.
- تم التحقق عبر HTTP من `PHP 8.5.12 / apache2handler` ومن تحميل curl وoci8 وgd وzip وmbstring وopenssl وfileinfo وdom.
- تم الاحتفاظ بالمنطقة الزمنية السابقة `Europe/Berlin` وحدود الرفع والذاكرة لتجنب تغيير سلوك التطبيق.
- XAMPP Shell يستخدم `php8512`. مسار PHP العام في Windows بقي كما هو، وقد يشير إلى تثبيت XAMPP آخر. استخدم المسار الكامل أدناه أو XAMPP Shell.

## حزمة OCI8 المبنية باستخدام Client 19.32

نجح البناء بتاريخ 2026-10-22 باستخدام Visual Studio 2022 Build Tools **17.14.42** وcompiler **19.44.35229** وWindows SDK **10.0.26100**. اجتاز DLL الجديد فحص CLI وApache مؤقتًا ومعزولًا عن إعدادات XAMPP العامل: **PHP 8.5.12 / TS / x64، OCI8 3.4.1، Oracle compile-time 19.32 وruntime 19.32.0.0.0**، دون اتصال بقاعدة البيانات أو تشغيل VPN.

اسم الملف `php_oci8_19.dll`، وبصمة SHA256 الخاصة به:

```text
F60D475675B08C214820A86CC06FC72CD6C3CB64B38D5C5720F90BC2E5ED1D36
```

أدلة الفحص المحلية محفوظة في `temp/php85/oci8-client19-build/build.log` و`apache-acceptance.json` و`dll-dependents.txt`، وملخصها في [تقرير البناء](oci8-client19-build.md). هذا قبول تحميل محلي للحزمة الجديدة؛ يبقى اختبار Oracle الفعلي والعربية وLOB/cursors والتقارير والاستيراد والتصدير بعد إتاحة شبكة الشركة. الفحوص المتصلة المسجلة أدناه أُجريت سابقًا بالبيئة القديمة، وليست اختبارًا لهذه الحزمة.

## تغييرات المشروع

- تحديث CodeIgniter من 3.1.11 إلى 3.1.13 مع دمج إصلاحات PHP 8.5.12 السابقة في الجلسات والخصائص الديناميكية وGD وfileinfo وFTP وXML-RPC، والمحافظة على MX. تفاصيل الدمج والنسخة الاحتياطية والاختبارات في [تقرير ترقية CI](ci3113-upgrade.md).
- في `DBConn` أصبحت معاملات نوع الربط الفارغة وإزاحة/حد قراءة المؤشر تمرّر `0` صراحة بدل `null`، حفاظًا على التحويل السابق وتجنب تحذيرات PHP 8.5. اختبار الربط المالي يتحقق من معاملات OCI8 الرقمية غير القابلة لـ`null`، دون اتصال بقاعدة البيانات.
- في `MY_Controller` عولج غياب بيانات الطلب الاختيارية ورسائل الخطأ الفارغة دون تغيير الصلاحيات أو نتائج النجاح والفشل. اختبارات معزولة تغطي تهيئة الزائر والمستخدم وبيانات الطلب ورمز الجلسة.
- في تصدير CSV ضمن `Payment_req` حُدد معامل escape صراحةً بالقيمة السابقة، لمنع تحذيرات PHP 8.5 داخل الملف المصدّر. اختبار مساري التصدير الفعليين ببيانات مصطنعة يغطي العربية والاقتباسات والأصفار الأولى والتصفية والإجمالي.
- تحويل وسوم PHP القصيرة إلى `<?php`، وإصلاح التعبيرات الشرطية المتداخلة التي يمنع PHP 8 تجميعها، مع الحفاظ على التقييم السابق.
- إزالة القيم الافتراضية التي كانت مُهملة قبل المعاملات المطلوبة؛ تمت مقارنة بيانات Reflection قبل التعديل وبعده لكل توقيع متأثر.
- تحديث PhpSpreadsheet إلى `5.10.0` وتعديل استدعاءات الخلايا المتأثرة بتغيير واجهتها.
- أصبح `composer.json` في جذر المشروع نقطة تثبيت الاعتماديات الوحيدة. مسار التحميل القديم يحوّل إلى `vendor/autoload.php` في الجذر.
- تثبيت Jasper REST Client عند `2.0.0` مع إصلاحات توافق قابلة لإعادة التطبيق عبر Composer post-install/post-update. لا تستخدم `--no-scripts` عند النشر.
- بقيت نسخ PhpSpreadsheet التاريخية في `application/third_party/PhpSpreadsheet` وداخل `application/third_party/phpoffice/vendor` محفوظة؛ ليست مسار التشغيل، باستثناء ملف التحميل الذي يحوّل إلى الجذر. لا تثبت Composer من مجلداتها.

## التحقق

اجتاز 3234 ملف PHP نشط فحص التجميع الأخير بعد تحديث CI إلى 3.1.13 باستخدام PHP 8.5.12 و`E_ALL` و`short_open_tag=Off` دون أخطاء أو تحذيرات تجميع. يشمل العدد الاختبارات وأدوات الفحص؛ لا يعني فحص التجميع اختبار جميع المسارات أثناء التشغيل.

في مراجعة Controllers اللاحقة اجتاز 485 ملفًا فحص التجميع: 482 ملفًا داخل مجلدات controllers، بما فيها المجلدات المتداخلة، وملفات MY/MX/CI_Controller الثلاثة. اجتاز 468 تعريف Controller و5 تعريفات Model موجودة داخل مجلدات controllers فحص الوراثة والتواقيع دون إنشاء كائنات أو تشغيل التطبيق. الملفات التسعة الباقية لا تعرّف أصنافًا، لذا تغطيتها فحص التجميع فقط. نجح اختبار MY_Controller المعزول و19 فحصًا لتصدير CSV؛ لا يغطي ذلك جميع إجراءات العمل المتصلة بـOracle.

نجحت اختبارات الإطار والتطبيق والمكتبات، و1091 فحص ربط مالي و475 فحص تقارير، واختبارات JavaScript للتجميع والعرض وترتيب التحميل. تستخدم الاختبارات بيانات مصطنعة وبدائل Oracle، دون تعديل قاعدة بيانات.

في مراجعة views/helpers/libraries اللاحقة شمل الفحص الساكن 1915 ملف views داخل modules و18 view مشتركًا و7 helpers للمشروع و8 ملفات application/libraries، مع مراجعة دوال المكتبات والـhelpers الأساسية وملفي GISConn وBillsConn المستخدمين فعليًا. عولجت النتائج الفارغة والقيم الاختيارية في 7 views، وتمرير null لدوال النص والأرقام في general_helper وgenerate_list_helper. عولجت كذلك نهاية نص JavaScript في Minify وتحميل drivers، وتحويلات GD وملفات انتهاء CAPTCHA، وكائنات XMLParser وcallbacks وتنسيق التاريخ في XML-RPC. استُبدل ocinewcursor بالاسم القياسي oci_new_cursor في مكتبات الاتصال الثلاث، مع إبقاء قيم الربط وتحويل المفاتيح السابق صراحةً.

نجح 167 فحصًا محليًا إضافيًا: 28 للـviews، و55 للـhelpers، و18 لـCAPTCHA، و29 للمكتبات والتشفير، و21 لمكتبات الاتصال باستخدام بدائل OCI، و16 لتحليل XML-RPC وأخطائه وتواريخه. تعمل الاختبارات مع E_ALL، واختبارات الاتصال لا تنشئ كائن اتصال فعليًا. أُعيد أيضًا اختبار الربط المالي ونجحت فحوصه الـ1091 بعد تعديل مكتبات الاتصال. لم يُشغّل VPN أو يُستدعَ Oracle في هذه المراجعة. تبقى بيانات الشاشات الحقيقية وخدمات الشبكة خارج تغطية الاختبارات المحلية.

بعد ترقية CI 3.1.13 أُعيدت الاختبارات أعلاه بنجاح، مع 75 فحصًا جديدًا يستخدم CI وMX الفعليين وتطبيق modules مصطنعًا، و45 فحصًا لتعريفات الملفات والمتصفحات وقبول الرفع والدخول. اختُبر CI_SessionWrapper الجديد من خلال session_start وحفظ بيانات عربية وأرقام بأصفار أولى واستعادتها وتحديث وقت الجلسة وحذفها، مع رفض معرف جلسة غير معروف. بقيت ملفات MX وindex.php وملفات اللغة العربية مطابقة للنسخة السابقة بالـSHA-256. أُجريت الترقية والاختبارات دون VPN أو اتصال Oracle.

من جذر المشروع، في PowerShell:

```powershell
$php85 = 'C:\xampp744\php8512\php.exe'
& $php85 tools/php85/check.php
& $php85 tools/php85/scan-syntax.php
& $php85 tests/php85/framework_test.php
& $php85 -n tests/php85/mx_compat_test.php
& $php85 -n tests/php85/ci_upgrade_config_test.php
& $php85 tests/php85/app_test.php
& $php85 -n tests/php85/controller_test.php
& $php85 -n tests/php85/controller_connection_test.php
& $php85 tests/php85/controller_csv_test.php
& $php85 -n tools/php85/check-controller-declarations.php
& $php85 tests/php85/dependencies_test.php
& $php85 -n tests/php85/views_compat_test.php
& $php85 -n tests/php85/helpers_compat_test.php
& $php85 -n -d extension=C:/xampp744/php8512/ext/php_gd.dll tests/php85/captcha_test.php
& $php85 -n -d extension=C:/xampp744/php8512/ext/php_openssl.dll tests/php85/libraries_compat_test.php
& $php85 -n tests/php85/connection_libraries_test.php
& $php85 -n tests/php85/xmlrpc_compat_test.php
& $php85 -n tests/payment_req/financial_bindings_test.php
& $php85 -n -d extension=C:/xampp744/php8512/ext/php_mbstring.dll -d extension=C:/xampp744/php8512/ext/php_zip.dll tests/payment_req/report_contract_test.php --render --output=C:/xampp744/htdocs/gfc/temp/php85/report-fixtures
node tests/payment_req/month_summary_test.js C:/xampp744/php8512/php.exe
node tests/payment_req/report_options_preferences_test.js
node tests/payment_req/report_renderers_test.js --output=C:/xampp744/htdocs/gfc/temp/php85/report-fixtures
node tests/payment_req/report_script_order_test.js --output=C:/xampp744/htdocs/gfc/temp/php85/report-fixtures
& $php85 C:\ProgramData\ComposerSetup\bin\composer.phar install
& $php85 C:\ProgramData\ComposerSetup\bin\composer.phar check-platform-reqs
```

## ما يحتاج تحققًا متصلًا

تم تحديد سبب `ORA-12170` وحلّه: اتصال Windows المحفوظ باسم `GEDCO VPN` كان غير متصل، ولم يكن منفذ Oracle الداخلي `10.184.3.204:1521` قابلًا للوصول. بعد تشغيل الاتصال ببياناته المحفوظة أصبح المنفذ متاحًا وفتحت صفحة الدخول بنجاح على PHP 8.5.12 دون أخطاء جديدة في سجل التطبيق. لم تُغيّر إعدادات Oracle أو بيانات الاتصال.

نجح اتصال الحساب الافتراضي واستعلام `SELECT 1 FROM DUAL` خلال 0.4 ثانية. جرى أيضًا التحقق من نموذج الدخول الفعلي باستخدام User-Agent لمتصفح Chrome، وحقول المستخدم وكلمة المرور ورمز الحماية وكعكة `ci_session`؛ أعادت ملفات CSS وJavaScript المحلية الستة HTTP 200. كان GEDCO VPN متصلًا عند ذلك الفحص، ثم أوقفه المستخدم؛ أُجريت المراجعات اللاحقة محليًا دون إعادة تشغيله. متصفح PowerShell الافتراضي يعرض تحذير المتصفح غير المدعوم بسبب سياسة التطبيق، ولذلك لا يكفي فحص عنوان الصفحة وحده.

الوصول إلى الخادم يتطلب GEDCO VPN أو شبكة الشركة. عند استئناف الاختبارات المتصلة يمكن إعادة فحص Oracle باستعلام `SELECT 1 FROM DUAL` عبر `& $php85 tools/php85/check-oracle.php`؛ لا يعدّل الفحص بيانات، ولا يدخل ضمن الفحوص المحلية أعلاه. استُخدمت لاحقًا جلسة المستخدم الفعلية لفحص شاشات واستعلامات محددة، ونجح تقريران من خادم Jasper الحقيقي؛ راجع تقريري 23 و24 أكتوبر أعلاه. لم يشمل ذلك جميع الأدوار أو العمليات التي تعدّل البيانات. اختُبرت XML-RPC ببيانات محلية دون إرسال أي طلب خارجي.

## ملفات XAMPP والرجوع

- النسخة القديمة محفوظة في `C:\xampp744\php`، وإصدارها `7.4.30`.
- نسخة الإعدادات السابقة: `C:\xampp744\php85-backup-20261022-080643`، وتحتوي `httpd-xampp.conf` و`xampp_shell.bat`.
- تغيّر ملف `apache/conf/extra/httpd-xampp.conf` لتحميل PHP 8 وإضافات DLL المطابقة لـcURL؛ لم تُستبدل DLL القديمة في Apache.
- `install-xampp.ps1` يوثق إجراء التثبيت من الأرشيف الرسمي المتحقق منه في `temp/php85`. يرفض الكتابة فوق تثبيت `php8512` موجود.

النسخة الاحتياطية المذكورة تخص إعدادات التشغيل فقط. للرجوع الكامل يجب استعادة نسخة التطبيق واعتمادياته السابقة أيضًا؛ الاعتماديات الحالية تتطلب PHP 8.5.12، ولذلك لا يكفي تحويل Apache إلى PHP 7.4 وحده. أوقف Apache قبل استعادة الإعدادات ثم افحص `httpd.exe -t` قبل تشغيله.

## المصادر

- [إعلان PHP 8.5.12](https://www.php.net/releases/8_5_12.php)
- [تنزيلات PHP الرسمية لـWindows](https://www.php.net/downloads.php?os=windows&version=8.5)
- [دليل الانتقال إلى PHP 8.5](https://www.php.net/manual/en/migration85.php)
- [استبدال xml_set_object بتمرير callable للـhandlers](https://www.php.net/manual/en/function.xml-set-object.php)
- [إهمال xml_parser_free في PHP 8.5](https://www.php.net/manual/en/function.xml-parser-free.php)
- [OCI8 3.4.1 لـWindows](https://pecl.php.net/package/oci8/3.4.1/windows)

SHA-256 لأرشيف PHP x64 TS المستخدم:
`660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184`
