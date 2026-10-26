"""Offline interface/transaction regression checks; NOT an Oracle compiler.

The six table declarations are transcribed from the user's original procedures.
The current spec also exposes the two functions and four validation helpers.
Checks target the split-then-commit failure and concurrent overlap regressions.
Real PL/SQL behavior still requires transport_allowance_oracle_test.sql.
"""
from pathlib import Path
import re

from transport_allowance_seed_test import without_comments

ROOT = Path(__file__).resolve().parents[2]
SQL_PATH = ROOT / "database/hr_attendance/02_transport_allowance_procedures.sql"
SPEC_PATH = ROOT / "database/hr_attendance/02_transport_allowance_spec.sql"
PREFIX = "TRANSPORT_ALLOWANCE_TB_"
TABLE = "TRANSPORT_ALLOWANCE_TB"
ROW_INPUTS = [
    f"FROM_REGION_IN IN {TABLE}.FROM_REGION%TYPE",
    f"TO_REGION_IN IN {TABLE}.TO_REGION%TYPE",
    f"DAILY_AMOUNT_IN IN {TABLE}.DAILY_AMOUNT%TYPE",
    "START_DATE_IN IN VARCHAR2", "END_DATE_IN IN VARCHAR2",
    f"NOTE_IN IN {TABLE}.NOTE%TYPE", "MSG_OUT OUT VARCHAR2",
]
EXPECTED = {
    "INSERT": ROW_INPUTS,
    "UPDATE": [f"SER_IN IN {TABLE}.SER%TYPE"] + ROW_INPUTS,
    "AMOUNT": [f"SER_IN IN {TABLE}.SER%TYPE",
               f"NEW_AMOUNT_IN IN {TABLE}.DAILY_AMOUNT%TYPE",
               "EFFECTIVE_DATE_IN IN VARCHAR2", "MSG_OUT OUT VARCHAR2"],
    "DELETE": [f"ID IN {TABLE}.SER%TYPE", "MSG_OUT OUT VARCHAR2"],
    "GET": [f"ID IN {TABLE}.SER%TYPE", "REF_CUR_OUT OUT SYS_REFCURSOR", "MSG_OUT OUT VARCHAR2"],
    "LIST": ["INSQL IN VARCHAR2", "OFFSET NUMBER", "ROW NUMBER",
             "REF_CUR_OUT OUT SYS_REFCURSOR", "MSG_OUT OUT VARCHAR2"],
}


def normalize(text):
    return " ".join(text.upper().split())


def validate_spec(body, spec):
    def declarations(text, ending):
        pattern = (r"\b(FUNCTION|PROCEDURE)\s+(\w+)\s*\((.*?)\)\s*"
                   r"(RETURN\s+\w+\s*)?" + ending)
        matches = re.findall(pattern, without_comments(text), re.I | re.S)
        result = {}
        for kind, name, params, returns in matches:
            name = name.upper()
            assert name not in result, "Duplicate declaration: " + name
            assert len(name.encode("ascii")) <= 30, "Oracle 11g identifier too long: " + name
            result[name] = (kind.upper(), re.sub(r"\s+", "", params).upper(), normalize(returns))
        return result

    actual_body = declarations(body, r"(?:AS|IS)\b")
    actual_spec = declarations(spec, ";")
    helpers = {
        "TRANSPORT_FORMAT_ERROR", "TRANSPORT_PARSE_DATE", "TRANSPORT_VALIDATE_ID",
        "TRANSPORT_VALIDATE_AMOUNT", "TRANSPORT_VALIDATE_RATE", "TRANSPORT_CHECK_RATE_OVERLAP",
    }
    expected_names = {PREFIX + suffix for suffix in EXPECTED} | helpers
    assert actual_spec.keys() == expected_names, "Header must declare all six table procedures and six helpers"
    assert actual_body == actual_spec, "Header/body signature or return-type mismatch"
    assert sum(kind == "FUNCTION" for kind, _, _ in actual_spec.values()) == 2
    assert not re.search(r"\bTA553_(?:ERROR_TEXT|PARSE_DATE|CHECK_ID|CHECK_AMOUNT|CHECK_ROW|CHECK_OVERLAP)\b",
                         without_comments(body + spec), re.I), "Old helper declaration or call remains"


def validate(sql):
    sql = without_comments(sql)
    # Strings may contain words such as COMMIT; ignore their contents here.
    executable = re.sub(r"'(?:''|[^'])*'", "''", sql).upper()
    assert not re.search(r"\bCOMMIT\b|\bPRAGMA\s+AUTONOMOUS_TRANSACTION\b", executable)
    assert not re.search(r"\bCREATE\s+(?:OR\s+REPLACE\s+)?PACKAGE", executable)
    bodies = {}
    for suffix, signature in EXPECTED.items():
        name = PREFIX + suffix
        match = re.search(r"\bPROCEDURE\s+" + name +
                          r"\s*\((.*?)\)\s*AS(.*?)\bEND\s+" + name + r"\s*;",
                          sql, re.I | re.S)
        assert match, f"Missing or changed public procedure: {name}"
        assert [normalize(p) for p in match[1].split(",")] == signature, name + " signature drift"
        bodies[suffix] = match[2].upper()
    assert len(re.findall(r"\bPROCEDURE\s+TRANSPORT_ALLOWANCE_TB_", sql, re.I)) == 6

    for suffix in ("INSERT", "UPDATE", "AMOUNT", "DELETE"):
        body = bodies[suffix]
        savepoint = "TA553_" + suffix + "_SP"
        assert re.search(r"BEGIN\s+SAVEPOINT\s+" + savepoint + r";", body), suffix
        lock = body.index("LOCK TABLE TRANSPORT_ALLOWANCE_TB IN EXCLUSIVE MODE NOWAIT;")
        first_write = re.search(r"\b(?:INSERT INTO|UPDATE|DELETE FROM) TRANSPORT_ALLOWANCE_TB\b", body)
        assert first_write and lock < first_write.start(), suffix + " must lock before writing"
        if suffix != "DELETE":
            assert lock < body.index("TRANSPORT_CHECK_RATE_OVERLAP("), suffix + " unchecked concurrent overlap"
        handlers = body[body.index("EXCEPTION"):]
        for handler in re.split(r"\bWHEN\s+(?:OTHERS|NO_DATA_FOUND)\s+THEN", handlers)[1:]:
            assert "ROLLBACK TO " + savepoint + ";" in handler, suffix + " can partially commit on failure"
        for returning in re.finditer(r"\bRETURN;", body):
            assert re.search(r"ROLLBACK TO " + savepoint + r";\s*$", body[:returning.start()]), suffix

    amount = bodies["AMOUNT"]
    assert amount.index("LOCK TABLE") < amount.index("SELECT * INTO REC") < amount.index("UPDATE TRANSPORT_ALLOWANCE_TB")
    assert "WHERE SER = SER_IN FOR UPDATE;" in amount
    assert "END_DATE = V_EFFECTIVE - 1" in amount
    assert "V_EFFECTIVE, REC.END_DATE, REC.NOTE" in amount  # preserve a finite original end
    assert "V_EFFECTIVE <= REC.START_DATE" in amount
    assert "V_EFFECTIVE > NVL(REC.END_DATE" in amount
    overlap = re.search(r"PROCEDURE TRANSPORT_CHECK_RATE_OVERLAP\b(.*?)END TRANSPORT_CHECK_RATE_OVERLAP;", sql, re.I | re.S)[1].upper()
    assert "FROM_REGION = P_TO AND TO_REGION = P_FROM" in overlap
    assert "START_DATE <= NVL(P_END" in overlap and ">= P_START" in overlap
    assert "SER <> P_EXCLUDE_SER" in overlap
    assert "OPEN REF_CUR_OUT FOR XSQL USING ROW, OFFSET;" in bodies["LIST"]
    for suffix in ("GET", "LIST"):
        for field in ("START_DATE_STR", "END_DATE_STR", "FROM_REGION_NAME", "TO_REGION_NAME"):
            assert field in bodies[suffix], suffix + " cursor contract drift"
    assert "GFC.CONSTANT_DETAILS_TB" in executable and "TB_NO = 553" in executable
    assert "P_AMOUNT > 99999999.99" in executable and "P_AMOUNT <> ROUND(P_AMOUNT, 2)" in executable
    assert "FXYYYY-MM-DD" in sql
    assert "LENGTHB(V_TEXT) > 480" in executable


def main():
    sql = SQL_PATH.read_text(encoding="utf-8-sig")
    spec = SPEC_PATH.read_text(encoding="utf-8-sig")
    validate_spec(sql, spec)
    validate(sql)
    # Verify the checker detects the dangerous regressions it is intended for.
    mutants = [
        sql.replace("ROLLBACK TO TA553_AMOUNT_SP;", "NULL;"),
        sql.replace("LOCK TABLE TRANSPORT_ALLOWANCE_TB IN EXCLUSIVE MODE NOWAIT;", "NULL;"),
        sql.replace("USING ROW, OFFSET;", "USING OFFSET, ROW;"),
        sql.replace("V_EFFECTIVE, REC.END_DATE, REC.NOTE", "V_EFFECTIVE, NULL, REC.NOTE"),
        sql.replace("FROM_REGION = P_TO AND TO_REGION = P_FROM", "FROM_REGION = P_FROM AND TO_REGION = P_TO"),
    ]
    for mutated in mutants:
        try:
            validate(mutated)
        except (AssertionError, ValueError):
            continue
        raise AssertionError("Regression checker accepted an unsafe mutation")
    print("PASS: 12 matching header/body declarations including two functions; six original table signatures; "
          "Oracle 11g identifier lengths; savepoint/lock/rollback contracts; "
          "reverse overlap, finite-end split, cursor and bind contracts; five regression mutations rejected.")
    print("Static checks only: Oracle compilation and runtime tests remain required.")


if __name__ == "__main__":
    main()
