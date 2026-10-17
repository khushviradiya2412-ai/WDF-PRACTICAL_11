<?php
// =====================================================
// add.php — Add a student with server-side validation
// =====================================================
session_start();
require_once __DIR__ . '/db.php';

// SECURITY: whitelists (course + gender) — never trust raw POST.
$ALLOWED_COURSES = [
    'Computer Engineering',
    'Information Technology',
    'Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
];
$ALLOWED_GENDERS = ['Male', 'Female', 'Other'];

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'student_id' => '', 'course' => '', 'year' => '', 'gender' => ''];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: trim all inputs; validate on server even if HTML5 validates.
    $old['full_name']  = trim($_POST['full_name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['phone']      = trim($_POST['phone'] ?? '');
    $old['student_id'] = trim($_POST['student_id'] ?? '');
    $old['course']     = trim($_POST['course'] ?? '');
    $old['year']       = trim($_POST['year'] ?? '');
    $old['gender']     = trim($_POST['gender'] ?? '');

    if (!preg_match('/^[A-Za-z ]{3,100}$/', $old['full_name'])) {
        $errors[] = 'Full name must be 3–100 letters/spaces only.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!preg_match('/^[0-9]{10}$/', $old['phone'])) {
        $errors[] = 'Phone must be exactly 10 digits.';
    }
    if (!preg_match('/^STU[0-9]{3}$/', $old['student_id'])) {
        $errors[] = 'Student ID must match pattern like STU001 (STU + 3 digits).';
    }
    if (!in_array($old['course'], $ALLOWED_COURSES, true)) {
        $errors[] = 'Please select a valid course.';
    }
    if (!preg_match('/^[0-9]{4}$/', $old['year']) || (int)$old['year'] < 2000 || (int)$old['year'] > 2099) {
        $errors[] = 'Year must be between 2000 and 2099.';
    }
    if (!in_array($old['gender'], $ALLOWED_GENDERS, true)) {
        $errors[] = 'Please select a valid gender.';
    }

    // SECURITY: duplicate check via prepared statement (email OR student_id).
    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM students WHERE email = ? OR student_id = ? LIMIT 1');
        $stmt->bind_param('ss', $old['email'], $old['student_id']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Duplicate email or student ID already exists.';
        }
        $stmt->close();
    }

    // SECURITY: INSERT via prepared statement (all user input bound).
    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO students (full_name, email, phone, student_id, course, year, gender, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'active\')'
        );
        $year_int = (int)$old['year'];
        $stmt->bind_param(
            'sssssis',
            $old['full_name'], $old['email'], $old['phone'],
            $old['student_id'], $old['course'], $year_int, $old['gender']
        );
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_success'] = 'Student added successfully.';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to add student. Please try again.';
            $stmt->close();
        }
    }

    if ($errors) {
        $message = implode(' ', $errors);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Student — Practical 11</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<main class="container my-4" style="max-width:640px">
  <h1>Add Student</h1>
  <?php if ($message): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <form method="post" action="add.php" novalidate>
    <div class="mb-3">
      <label class="form-label" for="full_name">Full Name</label>
      <input class="form-control" id="full_name" name="full_name" required pattern="[A-Za-z ]{3,100}" value="<?php echo htmlspecialchars($old['full_name']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" type="email" name="email" required value="<?php echo htmlspecialchars($old['email']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="phone">Phone (10 digits)</label>
      <input class="form-control" id="phone" name="phone" required pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($old['phone']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="student_id">Student ID (e.g. STU021)</label>
      <input class="form-control" id="student_id" name="student_id" required pattern="STU[0-9]{3}" value="<?php echo htmlspecialchars($old['student_id']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="course">Course</label>
      <select class="form-select" id="course" name="course" required>
        <option value="">-- Select --</option>
        <?php foreach ($ALLOWED_COURSES as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($old['course'] === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label" for="year">Year (2000–2099)</label>
      <input class="form-control" id="year" type="number" name="year" min="2000" max="2099" required value="<?php echo htmlspecialchars($old['year']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="gender">Gender</label>
      <select class="form-select" id="gender" name="gender" required>
        <option value="">-- Select --</option>
        <?php foreach ($ALLOWED_GENDERS as $g): ?>
          <option value="<?php echo htmlspecialchars($g); ?>" <?php echo ($old['gender'] === $g) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-success" type="submit">Save</button>
    <a class="btn btn-secondary" href="index.php">Back to list</a>
  </form>
</main>
</body>
</html>
