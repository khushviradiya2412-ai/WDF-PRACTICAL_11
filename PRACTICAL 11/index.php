<?php
// =====================================================
// index.php — List students: search + filter + pagination
// Shows only status='active' by default; filter active/inactive/all.
// =====================================================
session_start();
require_once __DIR__ . '/db.php';

// SECURITY: whitelist for course + status filters (never trust raw GET).
$ALLOWED_COURSES = [
    'Computer Engineering',
    'Information Technology',
    'Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
];
$ALLOWED_STATUS_FILTER = ['active', 'inactive', 'all'];

// SECURITY: read + sanitise GET filters (trim; validate against whitelists).
$q = trim($_GET['q'] ?? '');
$course = trim($_GET['course'] ?? '');
if ($course !== '' && !in_array($course, $ALLOWED_COURSES, true)) {
    $course = '';
}
$status_filter = $_GET['status'] ?? 'active';
if (!in_array($status_filter, $ALLOWED_STATUS_FILTER, true)) {
    $status_filter = 'active';
}

// SECURITY: pagination inputs cast to int (no SQL injection via page).
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

// SECURITY: build WHERE dynamically but bind every user value via prepared statement.
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

// --- Total count (for pagination) ---
$count_sql = "SELECT COUNT(*) AS total FROM students $where_sql";
$stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// --- Page data (prepared LIMIT/OFFSET bound as ints) ---
$data_sql = "SELECT id, full_name, email, phone, student_id, course, year, gender, status, created_at
             FROM students $where_sql ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($data_sql);
$types2 = $types . 'ii';
$params2 = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Flash messages set by add/edit/delete (success/failure).
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Helper: keep current filters in pagination / export links.
function qs(array $over = []): string {
    $base = ['q' => $_GET['q'] ?? '', 'course' => $_GET['course'] ?? '', 'status' => $_GET['status'] ?? 'active'];
    $merged = array_merge($base, $over);
    return http_build_query($merged);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StudentHub — Students (Practical 11)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand" href="index.php">StudentHub P11</a>
      <div class="navbar-nav">
        <a class="nav-link active" href="index.php">Students</a>
        <a class="nav-link" href="add.php">Add Student</a>
      </div>
    </div>
  </nav>
</header>

<main class="container my-4">
  <h1 class="mb-1">Student Module</h1>
  <p class="text-muted">Add / View / Edit / Soft-delete, search by name, email, course; filter by course and status.</p>

  <!-- Flash success / failure messages -->
  <?php if ($flash_success): ?>
    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($flash_error); ?></div>
  <?php endif; ?>

  <!-- Search + filter form (GET) -->
  <form class="row g-2 mb-3" method="get" action="index.php" role="search">
    <div class="col-md-4">
      <label class="form-label" for="q">Search (name / email / course)</label>
      <input class="form-control" type="search" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="e.g. Aarav, @example.com, Computer">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="course">Course</label>
      <select class="form-select" id="course" name="course">
        <option value="">All courses</option>
        <?php foreach ($ALLOWED_COURSES as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($course === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label" for="status">Status</label>
      <select class="form-select" id="status" name="status">
        <?php foreach ($ALLOWED_STATUS_FILTER as $s): ?>
          <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($status_filter === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($s)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button class="btn btn-primary" type="submit">Search</button>
      <a class="btn btn-secondary" href="index.php">Reset</a>
    </div>
  </form>

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-success" href="add.php">+ Add Student</a>
    <!-- Intermediate: CSV export of current filter -->
    <a class="btn btn-outline-primary" href="export_csv.php?<?php echo htmlspecialchars(qs()); ?>">Export CSV</a>
    <span class="ms-auto text-muted align-self-center">Total: <?php echo (int)$total; ?> record(s)</span>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">Student ID</th>
          <th scope="col">Name</th>
          <th scope="col">Email</th>
          <th scope="col">Phone</th>
          <th scope="col">Course</th>
          <th scope="col">Year</th>
          <th scope="col">Gender</th>
          <th scope="col">Status</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="text-center">No students found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['student_id']); ?></td>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['email']); ?></td>
            <td><?php echo htmlspecialchars($r['phone']); ?></td>
            <td><?php echo htmlspecialchars($r['course']); ?></td>
            <td><?php echo (int)$r['year']; ?></td>
            <td><?php echo htmlspecialchars($r['gender']); ?></td>
            <td><span class="badge <?php echo $r['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-primary" href="edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
              <!-- KEY Q: delete is confirmed — JS confirm() + server-side confirm page -->
              <a class="btn btn-sm btn-danger" href="delete.php?id=<?php echo (int)$r['id']; ?>" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($r['full_name'], ENT_QUOTES); ?> (<?php echo htmlspecialchars($r['student_id'], ENT_QUOTES); ?>)? This will soft-delete the record.');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination: 5 per page -->
  <nav aria-label="Student pages">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
          <a class="page-link" href="index.php?<?php echo htmlspecialchars(qs(['page' => $i])); ?>"><?php echo (int)$i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</main>

<footer class="bg-dark text-white text-center py-3">
  <small>StudentHub ITUE203 — Practical 11 Student Module</small>
</footer>
</body>
</html>
