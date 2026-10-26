#!/usr/bin/env python3
"""Build a sanitized developer ZIP without modifying the running application.

Python 3 standard library only. --audit writes nothing. --build creates a new
output directory, staged copy, ZIP, extraction verification and audit files.
Run the release acceptance tests on the extracted copy separately.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import sys
import zipfile

ROOT_FILES = {".htaccess", "index.php", "barcode.php", "composer.json", "composer.lock", "README.md", "INSTALL.md"}
ROOT_DIRS = {"application", "assets", "assets-n", "barcode", "system", "tests", "tools", "tutorial", "vendor"}
PRIVATE_DIRS = {".git", ".idea", ".claude", ".memory", ".codex", ".agents", ".aws", "node_modules", "__pycache__"}
EXCLUDED_TREES = {
    "application/logs", "application/cache", "assets/da3em/img/uploads",
    "barcode/qrcode/cache", "application/third_party/PhpSpreadsheet",
    "application/modules/budg_pb/budg_pb", "application/modules/budg_pb/controllers/budg_pb",
}
EXCLUDED_FILES = {
    "application/core/DBConn_deleted.php", "application/modules.zip",
    "application/modules/budg_pb/models/priv-db", "application/modules/budg_pb/models/priv-pkg",
    "tutorial/visits.txt", "tools/php85/install-xampp.ps1",
    "tools/chrome_38.exe", "tools/firefox_33.exe",
    "assets/js/Newfullcalendar/demos/gcal.html", "assets/css/Newfullcalendar/demos/gcal.html",
}
OCI_PACKAGE = "tools/php85/packages/oci8-3.4.1-php-8.5-ts-vs17-x64-client-19.32.zip"
BRIDGE = "application/third_party/phpoffice/vendor/autoload.php"
TEXT_SUFFIXES = {".php", ".js", ".json", ".html", ".htm", ".md", ".txt", ".sql", ".css", ".map", ".ini", ".conf", ".ps1", ".py", ".yml", ".yaml", ".xml", ".svg"}
FIXED_ZIP_TIME = (2026, 10, 26, 0, 0, 0)
EMPTY_DIRS = ("temp", "uploads", "application/cache", "application/logs", "barcode/qrcode/cache", "assets/da3em/img/uploads")
GENERATED_FILES = {"uploads/.htaccess": b"Options -Indexes\n"}
LITERAL = r'''(?:'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*")'''
PLACEHOLDER = "REQUIRED_"
JS_CREDENTIAL_ASSIGNMENT = re.compile(
    r'''(?<![\w$])(?:var\s+|let\s+|const\s+)?(?P<name>[A-Za-z_$][\w$]*(?:password|passwd|pwd|api_?key|secret|token)[\w$]*|password|passwd|pwd|api_?key|secret|token)\s*=\s*(?P<q>['"])(?P<v>(?:\\.|(?!(?P=q)).)*)(?P=q)''', re.I)


class PackageError(Exception):
    pass


def digest(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def is_under(path: Path, parent: Path) -> bool:
    try:
        path.relative_to(parent)
        return True
    except ValueError:
        return False


def excluded(path: str, is_dir: bool = False) -> str | None:
    parts = path.split("/")
    if parts[0] not in ROOT_DIRS and path not in ROOT_FILES:
        return "outside explicit root allowlist"
    if any(p in PRIVATE_DIRS for p in parts):
        return "private development metadata"
    if any(p.lower() in {"uploads", "sessions"} for p in parts):
        return "runtime uploaded/session data"
    if any(path == p or path.startswith(p + "/") for p in EXCLUDED_TREES):
        return "runtime data or reviewed historical copy"
    if path.startswith("application/third_party/phpoffice/vendor/") and path != BRIDGE:
        # Do not prune the directory itself: the autoload bridge must survive.
        if is_dir and BRIDGE.startswith(path + "/"):
            return None
        return "inactive historical dependencies; root Composer is canonical"
    if path in EXCLUDED_FILES:
        return "reviewed obsolete/private file"
    name = parts[-1]
    if name == ".env" or name.startswith(".env.") or name == ".htpasswd":
        return "private environment/HTTP credentials"
    if re.search(r"(?i)(?:\.(?:bak|bkp|old|orig|save|tmp)(?:\W|\d|$)|\.PRE_|^Thumbs\.db$|^\.DS_Store$|~$|\.tmp-)", name):
        return "backup or temporary file"
    if re.search(r"(?i)\.(?:log|dat|pem|pfx|p12|key|ora)$", name):
        return "runtime log/cache or private deployment material"
    if name.lower().endswith(".zip") and path != OCI_PACKAGE:
        return "nested archive not on release allowlist"
    if path.startswith("barcode/qrcode/") and re.search(r"(?i)errors\.txt$", name):
        return "generated barcode error log"
    return None


def inventory(root: Path) -> tuple[list[str], list[dict]]:
    included, skipped = [], []
    for current, dirs, files in os.walk(root, topdown=True, followlinks=False):
        parent = Path(current)
        for name in sorted(dirs[:]):
            p = parent / name
            relative = p.relative_to(root).as_posix()
            attrs = p.lstat()
            reparse = p.is_symlink() or bool(getattr(attrs, "st_file_attributes", 0) & 0x400)
            reason = "symlink/reparse point" if reparse else excluded(relative, True)
            if reason:
                dirs.remove(name)
                skipped.append({"path": relative + "/", "reason": reason})
        for name in sorted(files):
            p = parent / name
            relative = p.relative_to(root).as_posix()
            attrs = p.lstat()
            reparse = p.is_symlink() or bool(getattr(attrs, "st_file_attributes", 0) & 0x400)
            reason = "symlink/reparse point" if reparse else excluded(relative)
            if not reason and not stat.S_ISREG(attrs.st_mode):
                reason = "not a regular file"
            if reason:
                skipped.append({"path": relative, "reason": reason})
            else:
                included.append(relative)
    return sorted(included), sorted(skipped, key=lambda x: x["path"])


class Sanitizer:
    def __init__(self, root: Path):
        self.root = root
        self.changed: dict[str, bytes] = {}
        self.notes: list[dict] = []
        self.secrets: set[bytes] = set()
        self.identifiers: set[bytes] = set()
        self.source_hashes: dict[str, str] = {}

    def text(self, path: str) -> str:
        raw = (self.root / path).read_bytes()
        current_hash = digest(raw)
        if path in self.source_hashes and self.source_hashes[path] != current_hash:
            raise PackageError("Source changed during sanitization: " + path)
        self.source_hashes[path] = current_hash
        return self.changed.get(path, raw).decode("utf-8", "surrogateescape")

    def replace(self, path: str, pattern: str, label: str, count: int, secret: bool = True,
                flags: int = re.MULTILINE, group: str = "value") -> None:
        text = self.text(path)
        matches = list(re.finditer(pattern, text, flags))
        if len(matches) != count:
            raise PackageError(f"Review sanitizer {label}: expected {count} matches, found {len(matches)} in {path}")
        for m in reversed(matches):
            old = m.group(group)
            if old.startswith(PLACEHOLDER):
                if old != PLACEHOLDER + label:
                    raise PackageError(f"Unexpected release placeholder for {label}: {path}")
                continue
            if secret and old:
                self.secrets.add(old.encode("utf-8", "surrogateescape"))
            elif old:
                self.identifiers.add(old.encode("utf-8", "surrogateescape"))
            text = text[:m.start(group)] + PLACEHOLDER + label + text[m.end(group):]
        self.changed[path] = text.encode("utf-8", "surrogateescape")
        self.notes.append({"path": path, "setting": label, "occurrences": count})

    def literal(self, path: str, prefix: str, label: str, count: int, secret: bool = True) -> None:
        self.replace(path, prefix + r'''(?P<quote>['"])(?P<value>(?:\\.|(?!(?P=quote)).)*)(?P=quote)''', label, count, secret)

    def variable(self, path: str, variable: str, label: str, count: int = 1, secret: bool = True) -> None:
        self.literal(path, r"\$" + re.escape(variable) + r"\s*=\s*", label, count, secret)

    def key(self, path: str, key: str, label: str, count: int = 1, secret: bool = True) -> None:
        self.literal(path, r"\[['\"]" + re.escape(key) + r"['\"]\]\s*=\s*", label, count, secret)

    def query(self, path: str, key: str, label: str, count: int) -> None:
        self.replace(path, r"[?&]" + re.escape(key) + r"=(?P<value>[^&\s'\"<>]+)", label, count, key not in {"user", "username"})

    def run(self) -> None:
        for path, label in [
            ("application/libraries/DBConn.php", "MAIN_ORACLE"),
            ("application/core/GISConn.php", "GIS_ORACLE"),
            ("application/core/BillsConn.php", "BILLING_ORACLE"),
        ]:
            self.literal(path, r"public\s+\$user\s*=\s*", label + "_USER", 1, False)
            self.literal(path, r"private\s+\$pass\s*=\s*", label + "_PASSWORD", 1)
            self.literal(path, r"private\s+\$instance\s*=\s*", label + "_INSTANCE", 2 if label == "BILLING_ORACLE" else 1, False)
        self.key("application/config/config.php", "encryption_key", "EXISTING_ENCRYPTION_KEY")
        for variable in ["jid", "password"]:
            self.literal("assets/js/mainFunction.js", r"\bvar\s+" + variable + r"\s*=\s*",
                         "CAR_TRACKING_XMPP_" + variable.upper(), 1, variable == "password")
        for key in ["smtp_user", "smtp_pass"]:
            self.literal("application/controllers/Login.php", r"['\"]" + key + r"['\"]\s*=>\s*",
                         "COMMENTED_" + key.upper(), 1, key == "smtp_pass")
        for key in ["hostname", "username", "password", "database"]:
            self.key("application/config/database.php", key, "CI_DATABASE_" + key.upper(), secret=key == "password")
        for key in ["username", "password", "instance"]:
            self.variable("application/controllers/Reports.php", key, "CRYSTAL_" + key.upper(), secret=key == "password")

        # Match actual constructor argument positions, including the old commented call.
        jasper = "application/controllers/Jsperreport.php"
        call = r"new\s+Jaspersoft\\Client\\Client\s*\(\s*[^,\r\n]+,\s*"
        self.literal(jasper, call, "JASPER_USER", 2, False)
        self.literal(jasper, call + LITERAL + r"\s*,\s*", "JASPER_PASSWORD", 2)
        for key in ["username", "password"]:
            self.key("application/modules/archive/controllers/Archive.php", key, "ARCHIVE_FTP_" + key.upper(), 2, key == "password")
        self.replace("application/modules/treasury/controllers/Dxncharging.php",
                     r"Authorization:\s*Bearer\s+(?P<value>[^'\"\s]+)", "DXN_BEARER_TOKEN", 1)
        self.variable("application/modules/payment/controllers/Carmovements.php", "apiKey", "CAR_SERVICE_API_KEY")
        for variable in ["user", "password_app"]:
            self.variable("application/modules/treasury/controllers/Workfield.php", variable,
                          "WORKFIELD_SMS_" + variable.upper(), 2, variable != "user")
        for path, keys, count, label in [
            ("application/modules/technical/controllers/Requests.php", ["username", "password"], 2, "TECHNICAL_SMS"),
            ("application/modules/interviews/controllers/Interviews_result.php", ["user", "password"], 1, "INTERVIEWS_SMS"),
            ("application/modules/payment/views/carmovements_jsview.php", ["user", "password"], 2, "CAR_SMS"),
        ]:
            for key in keys:
                self.query(path, key, label + "_" + key.upper(), count)
        self.query("application/modules/settings/views/users_index.php", "token", "IDENTITY_SERVICE_TOKEN", 1)
        for path, count in [
            ("application/views/map_show.php", 2),
            ("application/modules/biunit/views/info/template.php", 1),
            ("application/modules/payment/views/car_movment_show.php", 1),
            ("application/modules/payment/views/tracking_index.php", 1),
            ("application/modules/payment/views/tracking_live_index.php", 1),
            ("application/modules/payment/views/tracking_ost_index.php", 1),
            ("application/modules/payment/views/tracking_ost_page.php", 1),
            ("application/modules/payment_20220630/views/car_movment_show.php", 1),
            ("application/modules/payment_20220630/views/tracking_index.php", 1),
            ("application/modules/payment_20220630/views/tracking_ost_index.php", 1),
        ]:
            self.query(path, "key", "GOOGLE_MAPS_API_KEY", count)
        for module in ["payment", "payment_20220630"]:
            self.query(f"application/modules/{module}/views/carmovements_jsview.php", "access_token", "MAPBOX_ACCESS_TOKEN", 1)
        direct = "application/modules/hr_attendance/controllers/Exit_edit.php"
        for connection in [1, 2]:
            for index, label in [(0, "USER"), (1, "PASSWORD"), (2, "INSTANCE")]:
                prefix = r"\$conn_" + str(connection) + r"\s*=\s*oci_new_connect\(\s*" + (LITERAL + r"\s*,\s*") * index
                self.literal(direct, prefix, f"ATTENDANCE_DIRECT_{connection}_" + label, 1, label == "PASSWORD")
        # Some legacy passwords equal public schema/user identifiers used by
        # procedure names and authorization guards. Their credential positions
        # above are replaced; do not rewrite legitimate business identifiers.
        # Semantic credential scans still reject hardcoded password assignments.
        self.secrets.difference_update(self.identifiers)


def scan_payload(path: str, data: bytes, secrets: set[bytes]) -> None:
    # Longer original values are checked anywhere, including comments/binaries.
    # Short values must be whole quoted literals, not substrings of identifiers.
    for value in secrets:
        found = (len(value) >= 12 and value in data) or (
            6 <= len(value) < 12 and (b"'" + value + b"'" in data or b'"' + value + b'"' in data))
        if found:
            raise PackageError(f"An original credential literal remains in {path}; review source/stage privately")
    if Path(path).suffix.lower() not in TEXT_SUFFIXES:
        return
    text = data.decode("utf-8", "surrogateescape")
    signatures = [r"AIza[0-9A-Za-z_-]{30,}", r"pk\.eyJ[0-9A-Za-z_.-]{20,}",
                  r"(?:Bearer|Basic)\s+(?!REQUIRED_)[A-Za-z0-9_./+=-]{20,}",
                  r"[?&](?:password|api_key|access_token|apikey|token)=(?!REQUIRED_)[A-Za-z0-9_.%=-]{6,}"]
    for pattern in signatures:
        if re.search(pattern, text):
            raise PackageError(f"Unreviewed credential-shaped literal in {path}; no release created")
    if path.endswith(".js"):
        for m in JS_CREDENTIAL_ASSIGNMENT.finditer(text):
            value, name = m.group("v"), m.group("name")
            if not value or value.startswith(PLACEHOLDER):
                continue
            # Reviewed library constants, limited to exact paths, names AND values.
            # Do not ignore whole vendor files: another literal still fails closed.
            bootstrap_path = path.startswith(("assets/da3em/vendor/bootstrap/js/", "assets-n/plugins/bootstrap/js/"))
            if bootstrap_path and re.fullmatch(r"DATA_API_KEY(?:\$\d+)?", name) and value == ".data-api":
                continue
            if path in {"assets/js/moment.js", "assets-n/js/moment.js"} and (name, value) in {
                ("ordinalizeTokens", "DDD w W M D d"), ("paddedTokens", "M D H h m s w W")
            }:
                continue
            if path == "assets-n/plugins/moment/moment.js" and name == "token" and value in {"S", "SSSS"}:
                continue
            raise PackageError(f"Unreviewed JavaScript credential literal in {path}:{text.count(chr(10), 0, m.start()) + 1} ({name})")
    if path.startswith("application/") and "/third_party/" not in path:
        assignments = re.compile(r'''\$(?:\w*(?:pass(?:word)?|api_?key|secret|token)\w*)\s*=\s*(?P<q>['"])(?P<v>(?:\\.|(?!(?P=q)).)*)(?P=q)''', re.I)
        for m in assignments.finditer(text):
            value = m.group("v")
            if not value or value.startswith(PLACEHOLDER):
                continue
            # Existing authenticated-user password derivation is code, not a stored credential.
            if path in {"application/libraries/DBConn.php", "application/controllers/Reports.php"} and len(value) == 1 and re.match(r"\s*\.\s*substr\(md5\(\$current_user->db_pwd\),\s*3\)", text[m.end():]):
                continue
            raise PackageError(f"Unreviewed sensitive variable literal in {path}:{text.count(chr(10), 0, m.start()) + 1}")
        sensitive_keys = r"(?:password|passwd|smtp_pass|(?:client_)?secret|api_?key|access_?token|token|encryption_key)"
        array_values = re.compile(
            r'''(?:\[['"]''' + sensitive_keys + r'''['"]\]\s*=|['"]''' + sensitive_keys +
            r'''['"]\s*=>)\s*(?P<q>['"])(?P<v>(?:\\.|(?!(?P=q)).)*)(?P=q)''', re.I)
        for m in array_values.finditer(text):
            if m.group("v") and not m.group("v").startswith(PLACEHOLDER):
                raise PackageError(f"Unreviewed sensitive configuration literal in {path}:{text.count(chr(10), 0, m.start()) + 1}")
        direct_password = re.compile(r'''oci_(?:new_|p)?connect\s*\(\s*[^,\r\n]+,\s*(?P<q>['"])(?P<v>(?:\\.|(?!(?P=q)).)*)(?P=q)''', re.I)
        for m in direct_password.finditer(text):
            if m.group("v") and not m.group("v").startswith(PLACEHOLDER):
                raise PackageError(f"Unreviewed direct OCI password literal in {path}:{text.count(chr(10), 0, m.start()) + 1}")


def json_bytes(value: object) -> bytes:
    return (json.dumps(value, indent=2, ensure_ascii=True, sort_keys=True) + "\n").encode("utf-8")


def prepare(root: Path) -> tuple[list[str], list[dict], Sanitizer]:
    files, skipped = inventory(root)
    required = ROOT_FILES | {BRIDGE, OCI_PACKAGE, "tools/php85/developer-package-setup.md", "vendor/autoload.php",
                             "application/third_party/php85/patch_jasper.php", "tests/php85/controller_connection_test.php"}
    missing = sorted(required - set(files))
    if missing:
        raise PackageError("Required package files missing/excluded: " + ", ".join(missing))
    sanitizer = Sanitizer(root)
    sanitizer.run()
    if set(sanitizer.changed) - set(files):
        raise PackageError("A sanitizer target is absent from the package inventory")
    for path in files:
        raw = (root / path).read_bytes()
        current_hash = digest(raw)
        if path in sanitizer.source_hashes and sanitizer.source_hashes[path] != current_hash:
            raise PackageError("Source changed during audit: " + path)
        sanitizer.source_hashes[path] = current_hash
        scan_payload(path, sanitizer.changed.get(path, raw), sanitizer.secrets)
    return files, skipped, sanitizer


def build(root: Path, output: Path, name: str, files: list[str], skipped: list[dict], sanitizer: Sanitizer) -> dict:
    if not re.fullmatch(r"[A-Za-z0-9_.-]+", name):
        raise PackageError("Use a simple ASCII archive name without paths")
    if output.exists():
        raise PackageError("Output directory already exists; choose a fresh directory")
    if output == root or (is_under(output, root) and not is_under(output, root / "temp")):
        raise PackageError("Inside the workspace, output must be below temp; never overlay application sources")
    output.mkdir(parents=True)
    # Build outputs can be beneath htdocs/temp. Protect the ZIP, stage and private
    # exclusion audit before writing any of them. Nginx deployment blocks /temp.
    (output / ".htaccess").write_text("Require all denied\n", encoding="ascii")
    stage = output / "stage" / "gfc"
    manifest = []
    for path in files:
        original = (root / path).read_bytes()
        if digest(original) != sanitizer.source_hashes.get(path):
            raise PackageError("Source changed since audit: " + path)
        data = sanitizer.changed.get(path, original)
        # Detect source changes after the initial scan before writing a release.
        scan_payload(path, data, sanitizer.secrets)
        dest = stage / path
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(data)
        manifest.append({"path": path, "bytes": len(data), "sha256": digest(data), "sanitized": path in sanitizer.changed})
    for path in EMPTY_DIRS:
        (stage / path).mkdir(parents=True, exist_ok=True)
    for path, data in sorted(GENERATED_FILES.items()):
        if path in files:
            raise PackageError("Generated file overlaps source inventory: " + path)
        scan_payload(path, data, sanitizer.secrets)
        (stage / path).write_bytes(data)
        manifest.append({"path": path, "bytes": len(data), "sha256": digest(data), "sanitized": False, "generated": True})
    manifest.sort(key=lambda entry: entry["path"])
    (stage / "PACKAGE-MANIFEST.json").write_bytes(json_bytes({"format": 1, "files": manifest, "runtime_directories": EMPTY_DIRS, "sanitized_settings": sanitizer.notes}))
    # Excluded paths can contain private filenames. Keep this audit OUTSIDE the ZIP.
    (output / "excluded-path-audit.private.json").write_bytes(json_bytes(skipped))
    final_inventory, _ = inventory(root)
    if final_inventory != files:
        raise PackageError("Source inventory changed during staging; repeat with a fresh output directory")
    for path in files:
        if digest((root / path).read_bytes()) != sanitizer.source_hashes[path]:
            raise PackageError("Source changed during staging: " + path)
    archive = output / (name + ".zip")
    all_files = sorted(p for p in stage.rglob("*") if p.is_file())
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for directory in sorted(EMPTY_DIRS):
            entry = zipfile.ZipInfo("gfc/" + directory + "/", FIXED_ZIP_TIME)
            entry.create_system = 3
            entry.external_attr = (0o40755 << 16) | 0x10
            z.writestr(entry, b"")
        for p in all_files:
            entry = zipfile.ZipInfo("gfc/" + p.relative_to(stage).as_posix(), FIXED_ZIP_TIME)
            entry.create_system = 3
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            z.writestr(entry, p.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    verify = output / "verified-extraction"
    with zipfile.ZipFile(archive) as z:
        expected = {"gfc/" + p.relative_to(stage).as_posix() for p in all_files}
        expected.update("gfc/" + d + "/" for d in EMPTY_DIRS)
        if len(z.namelist()) != len(set(z.namelist())) or set(z.namelist()) != expected:
            raise PackageError("ZIP entry inventory mismatch")
        for item in z.infolist():
            if not item.filename.startswith("gfc/") or ".." in Path(item.filename).parts or "\\" in item.filename:
                raise PackageError("Unsafe ZIP entry")
        if z.testzip() is not None:
            raise PackageError("ZIP CRC verification failed")
        z.extractall(verify)
    for p in all_files:
        relative = p.relative_to(stage)
        if digest(p.read_bytes()) != digest((verify / "gfc" / relative).read_bytes()):
            raise PackageError("Extracted hash mismatch: " + relative.as_posix())
    for path in EMPTY_DIRS:
        if not (verify / "gfc" / path).is_dir():
            raise PackageError("Runtime directory missing from extracted ZIP: " + path)
    sha = digest(archive.read_bytes())
    (output / (archive.name + ".sha256")).write_text(sha + "  " + archive.name + "\n", encoding="ascii")
    result = {"archive": str(archive), "sha256": sha, "files": len(all_files), "sanitized_files": len(sanitizer.changed),
              "verified_extraction": str(verify / "gfc"), "acceptance_tests": "NOT RUN by this builder; run the release checklist on verified-extraction/gfc"}
    (output / "build-result.json").write_bytes(json_bytes(result))
    return result


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", type=Path, default=Path(__file__).resolve().parents[2])
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--audit", action="store_true", help="Validate inventory/sanitization in memory; write nothing")
    mode.add_argument("--build", action="store_true", help="Create a new stage, ZIP and verified extraction")
    parser.add_argument("--output", type=Path)
    parser.add_argument("--name", default="gfc-php8512-production-20261026")
    args = parser.parse_args()
    root = args.root.resolve(strict=True)
    try:
        files, skipped, sanitizer = prepare(root)
        if args.audit:
            print(json.dumps({"audit": "PASS", "included_files": len(files), "excluded_entries": len(skipped),
                              "sanitized_files": len(sanitizer.changed), "settings": sanitizer.notes}, indent=2))
        else:
            if args.output is None:
                raise PackageError("--build requires --output pointing to a new directory")
            print(json.dumps(build(root, args.output.resolve(), args.name, files, skipped, sanitizer), indent=2))
        return 0
    except (PackageError, OSError, UnicodeError) as exc:
        print("PACKAGE STOPPED: " + str(exc), file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
