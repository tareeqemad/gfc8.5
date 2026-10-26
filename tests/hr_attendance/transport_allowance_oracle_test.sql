-- MANUAL TEST ONLY: NOT EXECUTED BY CODEX.
-- Oracle 11g, GFC_PAK, isolated disposable TEST database only; never production.
-- Requires the corrected 02_transport_allowance_procedures.sql body fragment,
-- existing constants 553/1 and 553/2, and a valid USER_PKG audit-user context.
-- Use a fresh session. Set the two explicit opt-in constants below only after
-- checking the database name. No application/PHP endpoint is used (it commits).
-- This block creates fixtures ONLY through the corrected INSERT procedure.
-- It never deletes existing data, runs DDL, creates triggers, or commits.
-- All fixture rows are rolled back on success and failure. Sequence gaps are
-- expected: Oracle NEXTVAL increments are not transactional.
-- The year-0001 fixture window is intentional, not a real policy tariff.

SET SERVEROUTPUT ON

DECLARE
  C_RUN_ON_ISOLATED_TEST_DB CONSTANT BOOLEAN := FALSE;
  C_EXPECTED_TEST_DB_NAME CONSTANT VARCHAR2(128) := 'SET_TEST_DATABASE_NAME';
  C_START CONSTANT DATE := DATE '0001-01-01';
  C_END CONSTANT DATE := DATE '0001-03-31';
  V_NOTE VARCHAR2(100) := 'TA553_ORACLE_TEST_' || RAWTOHEX(SYS_GUID());
  V_MSG VARCHAR2(500);
  V_ORIGINAL_ID NUMBER;
  V_ADJACENT_ID NUMBER;
  V_SPLIT_ID NUMBER;
  V_UNKNOWN_REGION NUMBER;
  V_COUNT NUMBER;
  V_CHECKS PLS_INTEGER := 0;

  PROCEDURE ASSERT_TRUE(P_CONDITION BOOLEAN, P_LABEL VARCHAR2) IS
  BEGIN
    IF P_CONDITION IS NULL OR NOT P_CONDITION THEN
      RAISE_APPLICATION_ERROR(-20901, 'FAIL: ' || P_LABEL);
    END IF;
    V_CHECKS := V_CHECKS + 1;
  END;

  PROCEDURE ASSERT_POSITIVE_ID(P_MSG VARCHAR2, P_LABEL VARCHAR2) IS
  BEGIN
    ASSERT_TRUE(P_MSG IS NOT NULL AND REGEXP_LIKE(P_MSG, '^[1-9][0-9]*$'),
                P_LABEL || '; MSG_OUT=' || NVL(P_MSG, '<NULL>'));
  END;

  PROCEDURE ASSERT_REJECTED(P_MSG VARCHAR2, P_LABEL VARCHAR2) IS
  BEGIN
    ASSERT_TRUE(P_MSG IS NOT NULL AND NOT REGEXP_LIKE(P_MSG, '^[0-9]+$'),
                P_LABEL || ' must return an error; MSG_OUT=' || NVL(P_MSG, '<NULL>'));
  END;

  FUNCTION FIXTURE_COUNT RETURN NUMBER IS
    V_ROWS NUMBER;
  BEGIN
    SELECT COUNT(*) INTO V_ROWS FROM GFC.TRANSPORT_ALLOWANCE_TB WHERE NOTE = V_NOTE;
    RETURN V_ROWS;
  END;

  PROCEDURE ASSERT_ROW(P_ID NUMBER, P_AMOUNT NUMBER,
                       P_START DATE, P_END DATE, P_LABEL VARCHAR2) IS
    V_ROWS NUMBER;
  BEGIN
    SELECT COUNT(*) INTO V_ROWS FROM GFC.TRANSPORT_ALLOWANCE_TB
     WHERE SER = P_ID AND FROM_REGION = 1 AND TO_REGION = 2
       AND DAILY_AMOUNT = P_AMOUNT AND START_DATE = P_START
       AND END_DATE = P_END AND NOTE = V_NOTE;
    ASSERT_TRUE(V_ROWS = 1, P_LABEL);
  END;

  PROCEDURE REJECT_INSERT(P_LABEL VARCHAR2, P_FROM NUMBER, P_TO NUMBER,
                          P_AMOUNT NUMBER, P_START VARCHAR2, P_END VARCHAR2) IS
    V_BEFORE NUMBER;
    V_RESULT VARCHAR2(500);
  BEGIN
    V_BEFORE := FIXTURE_COUNT;
    HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_INSERT(
      P_FROM, P_TO, P_AMOUNT, P_START, P_END, V_NOTE, V_RESULT);
    ASSERT_REJECTED(V_RESULT, P_LABEL);
    ASSERT_TRUE(FIXTURE_COUNT = V_BEFORE, P_LABEL || ' inserted no fixture row');
  END;

  PROCEDURE REJECT_INITIAL_SPLIT(P_LABEL VARCHAR2, P_AMOUNT NUMBER,
                                 P_EFFECTIVE VARCHAR2) IS
    V_RESULT VARCHAR2(500);
  BEGIN
    HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_AMOUNT(
      V_ORIGINAL_ID, P_AMOUNT, P_EFFECTIVE, V_RESULT);
    ASSERT_REJECTED(V_RESULT, P_LABEL);
    ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-31',
               P_LABEL || ' preserves original January 31 end');
    ASSERT_ROW(V_ADJACENT_ID, 24, DATE '0001-02-01', DATE '0001-02-28',
               P_LABEL || ' preserves adjacent February row');
    ASSERT_TRUE(FIXTURE_COUNT = 2, P_LABEL || ' leaves exactly two fixture rows');
  END;

BEGIN
  SAVEPOINT TA553_TEST_OUTER_SP;

  ASSERT_TRUE(C_RUN_ON_ISOLATED_TEST_DB,
              'Disabled by default: explicitly opt in on an isolated test database');
  ASSERT_TRUE(C_EXPECTED_TEST_DB_NAME <> 'SET_TEST_DATABASE_NAME' AND
              UPPER(SYS_CONTEXT('USERENV', 'DB_NAME')) = UPPER(C_EXPECTED_TEST_DB_NAME),
              'Test database name does not match the explicit opt-in name');
  ASSERT_TRUE(UPPER(SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA')) = 'GFC_PAK',
              'Run using the GFC_PAK test schema and its established user context');

  -- Serialize the preflight and fixtures. A conflict exits instead of waiting.
  LOCK TABLE GFC.TRANSPORT_ALLOWANCE_TB IN EXCLUSIVE MODE NOWAIT;
  SELECT COUNT(*) INTO V_COUNT FROM GFC.TRANSPORT_ALLOWANCE_TB
   WHERE ((FROM_REGION = 1 AND TO_REGION = 2)
       OR (FROM_REGION = 2 AND TO_REGION = 1))
     AND START_DATE <= C_END
     AND NVL(END_DATE, DATE '9999-12-31') >= C_START;
  ASSERT_TRUE(V_COUNT = 0,
              'Reserved pair 1/2 window 0001-01-01 through 0001-03-31 is not empty; stopped without changes');

  SELECT COUNT(*) INTO V_COUNT FROM GFC.CONSTANT_DETAILS_TB
   WHERE TB_NO = 553 AND CON_NO = 1;
  ASSERT_TRUE(V_COUNT = 1, 'Exactly one definition of region 553/1 is required');
  SELECT COUNT(*) INTO V_COUNT FROM GFC.CONSTANT_DETAILS_TB
   WHERE TB_NO = 553 AND CON_NO = 2;
  ASSERT_TRUE(V_COUNT = 1, 'Exactly one definition of region 553/2 is required');
  SELECT NVL(MAX(CON_NO), 0) + 1 INTO V_UNKNOWN_REGION
    FROM GFC.CONSTANT_DETAILS_TB WHERE TB_NO = 553;
  ASSERT_TRUE(FIXTURE_COUNT = 0, 'Generated fixture marker must be unused');

  -- 1. Valid closed interval and its exact stored values.
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_INSERT(
    1, 2, 20, '0001-01-01', '0001-01-31', V_NOTE, V_MSG);
  ASSERT_POSITIVE_ID(V_MSG, 'Valid insert');
  V_ORIGINAL_ID := TO_NUMBER(V_MSG);
  ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-31',
             'Original January fixture stored exactly');

  -- 2. Reverse direction and shared endpoints must conflict.
  REJECT_INSERT('Reversed overlapping route', 2, 1, 21, '0001-01-10', '0001-01-20');
  REJECT_INSERT('Inclusive endpoint overlap', 1, 2, 21, '0001-01-31', '0001-02-10');

  -- 3. Validation failures on the reserved empty March interval. An existing
  -- overlap must not mask broken validation and cause a false positive test.
  REJECT_INSERT('Impossible date', 1, 2, 20, '0001-03-01', '0001-03-32');
  REJECT_INSERT('Noncanonical date format', 1, 2, 20, '0001-3-01', '0001-03-31');
  REJECT_INSERT('Missing required start', 1, 2, 20, NULL, '0001-03-31');
  REJECT_INSERT('End before start', 1, 2, 20, '0001-03-20', '0001-03-19');
  REJECT_INSERT('NULL amount', 1, 2, NULL, '0001-03-01', '0001-03-31');
  REJECT_INSERT('Zero amount', 1, 2, 0, '0001-03-01', '0001-03-31');
  REJECT_INSERT('Negative amount', 1, 2, -1, '0001-03-01', '0001-03-31');
  REJECT_INSERT('More than two decimal places', 1, 2, 16.751, '0001-03-01', '0001-03-31');
  REJECT_INSERT('Exceeds NUMBER(10,2) storage', 1, 2, 100000000, '0001-03-01', '0001-03-31');
  REJECT_INSERT('Region absent from constant 553', V_UNKNOWN_REGION, 2, 20,
                '0001-03-01', '0001-03-31');
  REJECT_INSERT('Fractional region', 1.5, 2, 20, '0001-03-01', '0001-03-31');
  ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-31',
             'Rejected inserts preserved original row');

  -- 4. The day immediately after END_DATE is legal adjacency.
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_INSERT(
    1, 2, 24, '0001-02-01', '0001-02-28', V_NOTE, V_MSG);
  ASSERT_POSITIVE_ID(V_MSG, 'Adjacent insert');
  V_ADJACENT_ID := TO_NUMBER(V_MSG);
  ASSERT_ROW(V_ADJACENT_ID, 24, DATE '0001-02-01', DATE '0001-02-28',
             'Adjacent February fixture stored exactly');
  ASSERT_TRUE(FIXTURE_COUNT = 2, 'Exactly two initial fixture rows');

  -- Regression: an oversized replacement must not shorten the old interval.
  -- Corrected validation now rejects it before DML. This does not inject or
  -- prove an INSERT failure after UPDATE; that remains the separate plan below.
  REJECT_INITIAL_SPLIT('Oversized replacement amount', 100000000, '0001-01-16');
  REJECT_INITIAL_SPLIT('NULL replacement amount', NULL, '0001-01-16');
  REJECT_INITIAL_SPLIT('NULL replacement effective date', 16.75, NULL);

  -- 5. Split a finite interval. The replacement inherits the original END_DATE.
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_AMOUNT(
    V_ORIGINAL_ID, 16.75, '0001-01-16', V_MSG);
  ASSERT_POSITIVE_ID(V_MSG, 'Split at January 16');
  V_SPLIT_ID := TO_NUMBER(V_MSG);
  ASSERT_TRUE(V_SPLIT_ID <> V_ORIGINAL_ID AND V_SPLIT_ID <> V_ADJACENT_ID,
              'Split created a distinct record');
  ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-15',
             'Old price ends January 15');
  ASSERT_ROW(V_SPLIT_ID, 16.75, DATE '0001-01-16', DATE '0001-01-31',
             'New price preserves January 31 end and decimal amount');
  ASSERT_ROW(V_ADJACENT_ID, 24, DATE '0001-02-01', DATE '0001-02-28',
             'Split did not alter February');
  ASSERT_TRUE(FIXTURE_COUNT = 3, 'Exactly three rows after split');

  -- 6. Simulate a stale client's old ID, not actual two-session concurrency.
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_AMOUNT(
    V_ORIGINAL_ID, 18, '0001-01-20', V_MSG);
  ASSERT_REJECTED(V_MSG, 'Stale split against shortened original interval');
  ASSERT_TRUE(FIXTURE_COUNT = 3, 'Stale split created no replacement');
  ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-15',
             'Stale split left original unchanged');
  ASSERT_ROW(V_SPLIT_ID, 16.75, DATE '0001-01-16', DATE '0001-01-31',
             'Stale split left replacement unchanged');

  -- 7. An edit that moves a start onto another interval's endpoint is rejected.
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_UPDATE(
    V_SPLIT_ID, 1, 2, 16.75, '0001-01-15', '0001-01-31', V_NOTE, V_MSG);
  ASSERT_REJECTED(V_MSG, 'UPDATE overlapping preceding interval');
  ASSERT_TRUE(FIXTURE_COUNT = 3, 'Rejected update changed no row count');
  ASSERT_ROW(V_ORIGINAL_ID, 20, DATE '0001-01-01', DATE '0001-01-15',
             'Rejected update preserved original');
  ASSERT_ROW(V_SPLIT_ID, 16.75, DATE '0001-01-16', DATE '0001-01-31',
             'Rejected update preserved replacement');

  -- 8. Delete only the known new February fixture; repeated delete returns 0.
  ASSERT_ROW(V_ADJACENT_ID, 24, DATE '0001-02-01', DATE '0001-02-28',
             'Delete target is this test run fixture');
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_DELETE(V_ADJACENT_ID, V_MSG);
  ASSERT_TRUE(V_MSG = '1', 'Existing fixture delete reports 1');
  SELECT COUNT(*) INTO V_COUNT FROM GFC.TRANSPORT_ALLOWANCE_TB WHERE SER = V_ADJACENT_ID;
  ASSERT_TRUE(V_COUNT = 0 AND FIXTURE_COUNT = 2, 'Exactly the selected fixture was deleted');
  HR_ATTENDANCE_PKG.TRANSPORT_ALLOWANCE_TB_DELETE(V_ADJACENT_ID, V_MSG);
  ASSERT_TRUE(V_MSG = '0', 'Already deleted fixture reports 0');
  ASSERT_TRUE(FIXTURE_COUNT = 2, 'Repeated delete leaves the other fixtures');

  -- Success cleanup. Do not use a full ROLLBACK that could discard caller work.
  ROLLBACK TO TA553_TEST_OUTER_SP;
  ASSERT_TRUE(FIXTURE_COUNT = 0, 'All this run fixture rows rolled back');
  DBMS_OUTPUT.PUT_LINE('PASS: ' || V_CHECKS || ' assertions; fixture rows rolled back.');
  DBMS_OUTPUT.PUT_LINE('Sequence gaps expected. Concurrency and injected INSERT failure were NOT tested.');
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK TO TA553_TEST_OUTER_SP;
    DBMS_OUTPUT.PUT_LINE('FAILED / STOPPED: ' || SQLERRM || '; fixture transaction rolled back.');
    RAISE;
END;
/

/*
MANUAL FOLLOW-UP PLAN -- NOT PERFORMED BY THE BLOCK ABOVE

A. Real two-session contention (isolated Oracle test database only):
   1. Both sessions establish a fresh savepoint and valid audit-user context.
      Choose a verified empty test-only route/window and record fixture IDs.
   2. Session A takes the tariff-table exclusive lock and keeps it uncommitted.
   3. Session B calls the corrected INSERT for the empty route/window. Expect
      the NOWAIT retry error and no row creation; do not silently retry loops.
   4. Session A rolls back to its savepoint, releasing its test lock.
   5. Session B repeats INSERT. Expect one success and then roll back its fixture.
   6. For an actual stale split race, use a separately prepared committed fixture
      in the disposable clone. Session A splits it while Session B still holds
      the original displayed period; B must receive retry while A is uncommitted.
      After A's split is committed in this isolated setup, B retries the old ID
      with a date outside its now-shortened interval: rejection, no overlap.
      Dispose of/revert this dedicated clone fixture using its recorded IDs.

B. Failure of the new-row INSERT after the old-row UPDATE during AMOUNT:
   1. Use a controlled fault-injection harness in a disposable Oracle clone.
      Arrange a deterministic failure at the new INSERT after UPDATE executes.
      This file supplies no trigger, DDL, or fault-injection code.
   2. Record the original row and related rows before calling AMOUNT.
   3. Expect an error MSG_OUT; compare original END_DATE, amount and every other
      field with the before snapshot, and verify no replacement row exists.
   4. Verify unrelated earlier caller changes are preserved by the procedure's
      savepoint rollback, then roll back the caller's whole test fixture.
   5. Invalid dates, amounts, stale IDs, and overlap rejections fail BEFORE this
      critical UPDATE/INSERT boundary and do NOT prove this failure path.
*/
