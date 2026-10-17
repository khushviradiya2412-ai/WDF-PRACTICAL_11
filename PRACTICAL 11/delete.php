<?php
// =====================================================
// delete.php — Soft delete via status column (Advanced)
// GET  -> shows confirm prompt page with student name.
// POST -> performs UPDATE status='inactive' (prepared).
// =====================================================
session_start();
require_once __DIR__ . '/db.php';

// SECURITY: id always cast via intval, loaded via prepared SELECT.
$id = (int)($_REQUEST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid student id.';
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, full_name, email, student_id, course, status FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['flash_error'] = 'Student not found.';
    header('Location: index.php');
    exit;
}

// SECURITY: destructive action allowed via POST only (GET only renders confirm page).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: soft delete via prepared UPDATE — row kept, status flipped.
    $stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_success'] = 'Student "' . $student['full_name'] . '" deleted successfully (soft delete).';
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirm Delete — Practical 11</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<main class="container my-4" style="max-width:640px">
  <h1>Confirm Delete</h1>
  <div class="alert alert-warning" role="alert">
    Are you sure you want to delete
    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
    (<?php echo htmlspecialchars($student['student_id']); ?>,
    <?php echo htmlspecialchars($student['email']); ?>,
    <?php echo htmlspecialchars($student['course']); ?>)?
    <br>This performs a <strong>soft delete</strong>: status becomes <code>inactive</code>; the record is kept.
  </div>
  <?php if ($student['status'] === 'inactive'): ?>
    <div class="alert alert-info" role="alert">Note: this student is already inactive.</div>
  <?php endif; ?>
  <!-- POST-only delete: confirm button submits POST; cancel is a plain link -->
  <form method="post" action="delete.php">
    <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">
    <button class="btn btn-danger" type="submit">Yes, Delete</button>
    <a class="btn btn-secondary" href="index.php">Cancel</a>
  </form>
</main>
</body>
</html>
