"""Offline packaging regression tests; synthetic files only, no services."""
import importlib.util
import json
from pathlib import Path
import sys
import shutil
import unittest
import uuid
import zipfile

sys.dont_write_bytecode = True
SPEC = importlib.util.spec_from_file_location(
    "developer_builder", Path(__file__).resolve().parents[2] / "tools/php85/build-developer-package.py")
builder = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(builder)


class PackageTests(unittest.TestCase):
    def setUp(self):
        self.test_root = (Path(__file__).resolve().parents[2] / "temp/package-builder-tests").resolve()
        self.test_root.mkdir(parents=True, exist_ok=True)
        self.base = (self.test_root / str(uuid.uuid4())).resolve()
        self.assertTrue(builder.is_under(self.base, self.test_root))
        self.base.mkdir()
        self.root = self.base / "source"
        self.root.mkdir()

    def tearDown(self):
        self.assertTrue(builder.is_under(self.base.resolve(), self.test_root))
        self.assertNotEqual(self.base.resolve(), self.test_root)
        shutil.rmtree(self.base)

    def put(self, path, data=b"fixture"):
        target = self.root / path
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(data)

    def test_inventory_blocks_private_data_and_keeps_bridge_and_dated_routes(self):
        wanted = ["index.php", builder.BRIDGE, "application/modules/payment_20220630/views/page.php", "assets-n/css/modules/payment_req/page.css", "vendor/autoload.php", "tools/car_request.webm", "tutorial/videos/07_task_explain.mp4"]
        private = ["uploads/person.xlsx", "assets/da3em/img/uploads/person.jpg", "application/cache/session", "temp/password.txt", "HANDOFF.md", "database/one-off.sql", "application/config/.env.local", "application/config/database.php.bak1", "application/modules/budg_pb/budg_pb/controllers/old.php", "application/third_party/phpoffice/vendor/private.txt", "application/modules.zip", ".git/config", "tools/chrome_38.exe", "tools/firefox_33.exe"]
        for name in wanted + private:
            self.put(name)
        actual, skipped = builder.inventory(self.root)
        self.assertEqual(sorted(wanted), actual)
        self.assertTrue(skipped)

    def test_sanitizer_changes_credential_value_only_and_never_source(self):
        path = "application/libraries/DBConn.php"
        source = b"<?php\r\nprivate $pass = 'fixture-password';\r\n$db_pass = 'x'.substr(md5($current_user->db_pwd),3);\r\n"
        self.put(path, source)
        sanitizer = builder.Sanitizer(self.root)
        sanitizer.literal(path, r"private\s+\$pass\s*=\s*", "MAIN_PASSWORD", 1)
        self.assertEqual(source, (self.root / path).read_bytes())
        self.assertEqual(source.replace(b"fixture-password", b"REQUIRED_MAIN_PASSWORD"), sanitizer.changed[path])
        builder.scan_payload(path, sanitizer.changed[path], sanitizer.secrets)
        with self.assertRaises(builder.PackageError):
            builder.scan_payload(path, source, sanitizer.secrets)
        self.put(path, sanitizer.changed[path])
        again = builder.Sanitizer(self.root)
        again.literal(path, r"private\s+\$pass\s*=\s*", "MAIN_PASSWORD", 1)
        self.assertEqual(sanitizer.changed[path], again.changed[path])

    def test_ambiguous_or_missing_selector_fails_closed(self):
        self.put("application/x.php", b"<?php $pass='one'; $pass='two';")
        with self.assertRaises(builder.PackageError):
            builder.Sanitizer(self.root).variable("application/x.php", "pass", "PASSWORD")

    def test_unknown_sensitive_assignments_fail_without_disclosing_value(self):
        for data in [b"<?php $password='fixture-password';", b"<?php $config['smtp_pass']='fixture-password';", b"<?php $config['client_secret']='fixture-password';", b"<?php oci_new_connect($user,'fixture-password',$instance);", b"<?php // 'smtp_pass' => 'fixture-password',"]:
            with self.assertRaises(builder.PackageError) as exc:
                builder.scan_payload("application/new.php", data, set())
            self.assertNotIn("fixture-password", str(exc.exception))

    def test_javascript_credential_guard_and_reviewed_library_constants(self):
        source = b"var jid = 'fixture@example.invalid';\nvar password = 'fixture$password';\nclient.connect();\n"
        path = "assets/js/mainFunction.js"
        self.put(path, source)
        sanitizer = builder.Sanitizer(self.root)
        for variable in ["jid", "password"]:
            sanitizer.literal(path, r"\bvar\s+" + variable + r"\s*=\s*", "CAR_TRACKING_XMPP_" + variable.upper(), 1, variable == "password")
        self.assertEqual(source, (self.root / path).read_bytes())
        self.assertIn(b"client.connect();", sanitizer.changed[path])
        builder.scan_payload(path, sanitizer.changed[path], sanitizer.secrets)
        with self.assertRaises(builder.PackageError):
            builder.scan_payload("assets/js/new-company.js", b"var password = 'fixture$password';", set())
        vendor = "assets/da3em/vendor/bootstrap/js/bootstrap.js"
        builder.scan_payload(vendor, b"const DATA_API_KEY$7 = '.data-api';", set())
        with self.assertRaises(builder.PackageError):
            builder.scan_payload(vendor, b"var password = 'fixture$password';", set())

    def test_zip_is_repeatable_and_extraction_hashes_match(self):
        self.put("index.php", b"<?php echo 'fixture';\n")
        self.put(builder.BRIDGE, b"<?php // bridge\n")
        files, skipped = builder.inventory(self.root)
        sanitizer = builder.Sanitizer(self.root)
        sanitizer.source_hashes = {p: builder.digest((self.root / p).read_bytes()) for p in files}
        first = builder.build(self.root, self.base / "build-one", "release", files, skipped, sanitizer)
        second = builder.build(self.root, self.base / "build-two", "release", files, skipped, sanitizer)
        self.assertEqual(first["sha256"], second["sha256"])
        self.assertEqual("Require all denied\n", (self.base / "build-one/.htaccess").read_text(encoding="ascii"))
        with zipfile.ZipFile(first["archive"]) as z:
            self.assertIn("gfc/" + builder.BRIDGE, z.namelist())
            self.assertIn("gfc/uploads/", z.namelist())
            self.assertEqual(b"Options -Indexes\n", z.read("gfc/uploads/.htaccess"))
            self.assertNotIn("excluded-path-audit.private.json", z.namelist())
            manifest = json.loads(z.read("gfc/PACKAGE-MANIFEST.json"))
            self.assertEqual(3, len(manifest["files"]))
            guard = next(entry for entry in manifest["files"] if entry["path"] == "uploads/.htaccess")
            self.assertEqual(builder.digest(b"Options -Indexes\n"), guard["sha256"])
            self.assertTrue(guard["generated"])
        extracted = Path(first["verified_extraction"])
        self.assertEqual((self.root / "index.php").read_bytes(), (extracted / "index.php").read_bytes())
        self.assertTrue((extracted / "application/cache").is_dir())

    def test_build_rejects_source_changes_after_audit(self):
        self.put("index.php", b"old")
        sanitizer = builder.Sanitizer(self.root)
        sanitizer.source_hashes = {"index.php": builder.digest(b"old")}
        self.put("index.php", b"changed")
        with self.assertRaises(builder.PackageError):
            builder.build(self.root, self.base / "failed-build", "release", ["index.php"], [], sanitizer)
        self.assertFalse((self.base / "failed-build/release.zip").exists())

    def test_existing_output_and_path_like_archive_name_are_rejected(self):
        sanitizer = builder.Sanitizer(self.root)
        with self.assertRaises(builder.PackageError):
            builder.build(self.root, self.root, "release", [], [], sanitizer)
        with self.assertRaises(builder.PackageError):
            builder.build(self.root, self.base / "output", "../release", [], [], sanitizer)


if __name__ == "__main__":
    unittest.main(verbosity=2)
