"""Read-only policy check for the decision 19/2026 transport-rate seed.

Run with Python 3; no third-party dependencies or Oracle connection are used.
The expected matrix is transcribed independently from the decision's 17-row
annex. Areas 4, 5, 6 and 7 expand a grouped annex cell; this does not imply a
fare between those four areas themselves.
"""

from __future__ import annotations

import argparse
from decimal import Decimal
from pathlib import Path
import re


ROOT = Path(__file__).resolve().parents[2]
DEFAULT_SQL = ROOT / "database/hr_attendance/03_transport_allowance_seed_19_2026.sql"
EFFECTIVE_DATE = "2026-04-13"
REGIONS = {
    1: "الشمال", 2: "غزة", 3: "الزهراء", 4: "البريج", 5: "النصيرات",
    6: "المغازي", 7: "الزوايدة", 8: "دير البلح", 9: "خان يونس", 10: "رفح",
}

# One tuple for each original annex row: (origins, destinations, daily ILS).
# Amounts cover the entire day's round trip, not one direction.
ANNEX_ROWS = (
    ((2,), (1,), 20),
    ((2,), (3,), 16),
    ((2,), (4, 5, 6, 7), 28),
    ((2,), (8,), 30),
    ((2,), (9,), 40),
    ((2,), (10,), 40),
    ((3,), (1,), 36),
    ((3,), (4, 5, 6, 7), 16),
    ((3,), (8,), 24),
    ((3,), (9,), 30),
    ((3,), (10,), 30),
    ((4, 5, 6, 7), (8,), 16),
    ((4, 5, 6, 7), (9,), 30),
    ((4, 5, 6, 7), (10,), 30),
    ((8,), (9,), 20),
    ((8,), (10,), 25),
    ((9,), (10,), 10),
)

# These are intentionally not priced by the decision's annex.
UNPRICED = {
    (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
    (4, 5), (4, 6), (4, 7), (5, 6), (5, 7), (6, 7),
}


def expected_rates() -> dict[tuple[int, int], Decimal]:
    expected = {(region, region): Decimal(5) for region in REGIONS}
    for origins, destinations, amount in ANNEX_ROWS:
        for origin in origins:
            for destination in destinations:
                pair = tuple(sorted((origin, destination)))
                assert pair not in expected, f"Duplicate in policy transcription: {pair}"
                expected[pair] = Decimal(amount)
    assert len(expected) == 42
    assert sum(a != b for a, b in expected) == 32
    all_external = {(a, b) for a in REGIONS for b in REGIONS if a < b}
    assert all_external - expected.keys() == UNPRICED
    return expected


def without_comments(sql: str) -> str:
    """Strip SQL comments while preserving quoted values and line positions."""
    result: list[str] = []
    index = 0
    quoted = False
    while index < len(sql):
        char = sql[index]
        if char == "'":
            result.append(char)
            if quoted and index + 1 < len(sql) and sql[index + 1] == "'":
                result.append("'")
                index += 2
                continue
            quoted = not quoted
            index += 1
        elif not quoted and sql.startswith("--", index):
            end = sql.find("\n", index)
            index = len(sql) if end == -1 else end
        elif not quoted and sql.startswith("/*", index):
            end = sql.find("*/", index + 2)
            if end == -1:
                raise AssertionError("Unterminated SQL block comment")
            result.append("\n" * sql[index:end + 2].count("\n"))
            index = end + 2
        else:
            result.append(char)
            index += 1
    return "".join(result)


def read_calls(sql: str) -> list[tuple[int, int, Decimal]]:
    code = without_comments(sql)
    calls = re.findall(r"(?im)^\s*ENSURE_RATE\s*\(([^;]*?)\)\s*;", code)
    result = []
    for call in calls:
        match = re.fullmatch(r"\s*(\d+)\s*,\s*(\d+)\s*,\s*([+-]?\d+(?:\.\d+)?)\s*", call)
        if not match:
            raise AssertionError(f"Expected a literal (from, to, daily_amount) seed call: {call!r}")
        result.append((int(match[1]), int(match[2]), Decimal(match[3])))
    return result


def validate_rates(calls: list[tuple[int, int, Decimal]]) -> None:
    expected = expected_rates()
    assert len(calls) == 42, f"Expected 42 seed calls, found {len(calls)}"
    actual = {}
    for origin, destination, amount in calls:
        assert origin in REGIONS and destination in REGIONS, f"Unknown region: {origin}, {destination}"
        assert origin <= destination, f"Store the symmetric route only in canonical order: {origin}, {destination}"
        pair = (origin, destination)
        assert pair not in actual, f"Duplicate seed route: {pair}"
        actual[pair] = amount
    assert not (actual.keys() & UNPRICED), f"Invented prices for absent routes: {actual.keys() & UNPRICED}"
    assert actual.keys() == expected.keys(), (
        f"Routes differ: missing {expected.keys() - actual.keys()}, extra {actual.keys() - expected.keys()}"
    )
    for pair, expected_amount in expected.items():
        assert actual[pair] == expected_amount, (
            f"Wrong daily round-trip rate for {REGIONS[pair[0]]} / {REGIONS[pair[1]]}: "
            f"expected {expected_amount}, found {actual[pair]}"
        )


def validate_seed(sql: str) -> None:
    validate_rates(read_calls(sql))
    code = without_comments(sql)
    dates = re.findall(
        r"(?i)\b(?:C_START_DATE|C_EFFECTIVE_DATE)\s+CONSTANT\s+DATE\s*:=\s*DATE\s*'([^']+)'",
        code,
    )
    assert dates == [EFFECTIVE_DATE], f"Expected exactly one effective-date constant {EFFECTIVE_DATE}; found {dates}"
    # The release deliberately leaves committing to the operator after review.
    without_strings = re.sub(r"'(?:''|[^'])*'", "''", code)
    assert not re.search(r"(?i)\bCOMMIT\b", without_strings), "Seed must not execute COMMIT automatically"


def parser_self_test() -> None:
    rates = [(a, b, value) for (a, b), value in expected_rates().items()]
    validate_rates(rates)
    mutations = [
        rates[:-1],
        rates[:-1] + [(1, 4, Decimal(20))],
        [(a, b, value * 2) if (a, b) == (1, 2) else (a, b, value) for a, b, value in rates],
        [(2, 1, value) if (a, b) == (1, 2) else (a, b, value) for a, b, value in rates],
        [rates[0]] + rates[1:-1] + [rates[0]],
    ]
    for mutation in mutations:
        try:
            validate_rates(mutation)
        except AssertionError:
            pass
        else:
            raise AssertionError("Policy validator accepted a deliberately invalid matrix")
    assert read_calls("-- ENSURE_RATE(1, 4, 20);\n/* ENSURE_RATE(1, 5, 20); */\nENSURE_RATE(1, 1, 5);") == [(1, 1, Decimal(5))]


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("sql", nargs="?", type=Path, default=DEFAULT_SQL)
    args = parser.parse_args()
    parser_self_test()
    validate_seed(args.sql.read_text(encoding="utf-8-sig"))
    print("PASS: decision 19/2026 — 32 external round-trip rates, 10 internal rates at ILS 5, "
          "13 unpriced routes omitted, effective 2026-04-13, no automatic COMMIT.")


if __name__ == "__main__":
    main()
