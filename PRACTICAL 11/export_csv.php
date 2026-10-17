<?php
// =====================================================
// export_csv.php — Intermediate: export current filter to CSV
// No HTML output; headers + fputcsv only.
// =====================================================
require_once __DIR__ . '/db.php';

// SECURITY: same whitelists + sanitising as index.php so export matches list.
$ALLOWED_COURSES = [
    'Computer Engineering',
    'Information Technology',
    'Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
];
$ALLOWED_STATUS_FILTER = ['active', 'inactive', 'all'];

$q = trim($_GET['q'] ?? '');
$course = trim($_GET['course'] ?? '');
if ($course !== '' && !in_array($course, $ALLOWED_COURSES, true)) {
    $course = '';
}
$status_filter = $_GET['status'] ?? 'active';
if (!in_array($status_filter, $ALLOWED_STATUS_FILTER, true)) {
    $status_filter = 'active';
}

// SECURITY: every user value bound via prepared statement.
$where = [];
$types = '';
$params = [];

if ($status_filter !== 'all') {
    $where[] = 'status = ?';
    $types .= 's';
    $params[] = $status_filter;
}
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR course LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($course !== '') {
    $where[] = 'course = ?';
    $types .= 's';
    $params[] = $course;
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT student_id, full_name, email, phone, course, year, gender, status, created_at
        FROM students $where_sql ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// CSV download headers (must come before ANY output).
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students.csv"');

$out = fopen('php://output', 'w');
// SECURITY: fputcsv handles quoting/escaping of commas/quotes in data.
fputcsv($out, ['Student ID', 'Full Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Status', 'Created At']);
while ($row = $result->fetch_assoc()) {
    fputcsv($out, $row);
}
fclose($out);
$stmt->close();
exit;
