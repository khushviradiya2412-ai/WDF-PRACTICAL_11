<?php
// =====================================================
// edit.php — Edit a student (loads by id, validates, UPDATE)
// =====================================================
session_start();
require_once __DIR__ . '/db.php';

// SECURITY: whitelists.
$ALLOWED_COURSES = [
    'Computer Engineering',
    'Information Technology',
    'Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
];
$ALLOWED_GENDERS = ['Male', 'Female', 'Other'];

// SECURITY: id from GET cast via intval (prevents injection).
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid student id.';
    header('Location: index.php');
    exit;
}

// SECURITY: load existing row via prepared statement.
$stmt = $conn->prepare('SELECT id, full_name, email, phone, student_id, course, year, gender, status FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['flash_error'] = 'Student not found.';
    header('Location: index.php');
    exit;
}

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: trim + server-side validation (same rules as add.php).
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year       = trim($_POST['year'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');

    if (!preg_match('/^[A-Za-z ]{3,100}$/', $full_name)) {
        $errors[] = 'Full name must be 3–100 letters/spaces only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone must be exactly 10 digits.';
    }
    if (!preg_match('/^STU[0-9]{3}$/', $student_id)) {
        $errors[] = 'Student ID must match pattern like STU001 (STU + 3 digits).';
    }
    if (!in_array($course, $ALLOWED_COURSES, true)) {
        $errors[] = 'Please select a valid course.';
    }
    if (!preg_match('/^[0-9]{4}$/', $year) || (int)$year < 2000 || (int)$year > 2099) {
        $errors[] = 'Year must be between 2000 and 2099.';
    }
    if (!in_array($gender, $ALLOWED_GENDERS, true)) {
        $errors[] = 'Please select a valid gender.';
    }

    // SECURITY: duplicate check excluding current row (prepared).
    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM students WHERE (email = ? OR student_id = ?) AND id <> ? LIMIT 1');
        $stmt->bind_param('ssi', $email, $student_id, $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Duplicate email or student ID already exists.';
        }
        $stmt->close();
    }

    // SECURITY: UPDATE via prepared statement.
    if (!$errors) {
        $year_int = (int)$year;
        $stmt = $conn->prepare(
            'UPDATE students SET full_name = ?, email = ?, phone = ?, student_id = ?, course = ?, year = ?, gender = ? WHERE id = ?'
        );
        $stmt->bind_param('sssssisi', $full_name, $email, $phone, $student_id, $course, $year_int, $gender, $id);
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_success'] = 'Student updated successfully.';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to update student. Please try again.';
            $stmt->close();
        }
        // Keep posted values visible on error.
        $student = array_merge($student, [
            'full_name' => $full_name, 'email' => $email, 'phone' => $phone,
            'student_id' => $student_id, 'course' => $course, 'year' => $year, 'gender' => $gender,
        ]);
    } else {
        $message = implode(' ', $errors);
        $student = array_merge($student, [
            'full_name' => $full_name, 'email' => $email, 'phone' => $phone,
            'student_id' => $student_id, 'course' => $course, 'year' => $year, 'gender' => $gender,
        ]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Student — Practical 11</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<main class="container my-4" style="max-width:640px">
  <h1>Edit Student #<?php echo (int)$student['id']; ?></h1>
  <?php if ($message): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <form method="post" action="edit.php?id=<?php echo (int)$student['id']; ?>" novalidate>
    <div class="mb-3">
      <label class="form-label" for="full_name">Full Name</label>
      <input class="form-control" id="full_name" name="full_name" required pattern="[A-Za-z ]{3,100}" value="<?php echo htmlspecialchars($student['full_name']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" type="email" name="email" required value="<?php echo htmlspecialchars($student['email']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="phone">Phone</label>
      <input class="form-control" id="phone" name="phone" required pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($student['phone']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="student_id">Student ID</label>
      <input class="form-control" id="student_id" name="student_id" required pattern="STU[0-9]{3}" value="<?php echo htmlspecialchars($student['student_id']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="course">Course</label>
      <select class="form-select" id="course" name="course" required>
        <?php foreach ($ALLOWED_COURSES as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($student['course'] === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label" for="year">Year</label>
      <input class="form-control" id="year" type="number" name="year" min="2000" max="2099" required value="<?php echo (int)$student['year']; ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="gender">Gender</label>
      <select class="form-select" id="gender" name="gender" required>
        <?php foreach ($ALLOWED_GENDERS as $g): ?>
          <option value="<?php echo htmlspecialchars($g); ?>" <?php echo ($student['gender'] === $g) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a class="btn btn-secondary" href="index.php">Cancel</a>
  </form>
</main>
</body>
</html>
