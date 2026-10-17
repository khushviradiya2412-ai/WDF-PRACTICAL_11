<?php
// =====================================================
// StudentHub Practical 11 — central MySQLi connection
// XAMPP defaults: host=localhost, user=root, password='', db=studenthub_p11
// =====================================================

// SECURITY: keep credentials out of each page; single include point.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'studenthub_p11';

// SECURITY: enable strict error reporting for MySQLi (exceptions, no silent fail).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    // SECURITY: utf8mb4 supports all Unicode + prevents encoding-based bypasses.
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // FRIENDLY ERROR: never leak raw DB details to students/users.
    die('Database connection failed. Please start MySQL in XAMPP and import database.sql.');
}
