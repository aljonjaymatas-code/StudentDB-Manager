<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
	$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}
if (!$id) {
	die('Invalid student ID.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$stmt = sqlsrv_query($conn, "DELETE FROM dbo.Students WHERE StudentID = ?", [$id]);
	if ($stmt === false) {
		$error = 'Delete failed.';
	} else {
		$rows = sqlsrv_rows_affected($stmt);
		sqlsrv_free_stmt($stmt);
		if ($rows === false || $rows === 0) {
			$error = 'Student not found or already deleted.';
		} else {
			header('Location: index.php?msg=' . urlencode('Student deleted successfully.'));
			exit;
		}
	}
}

$stmt = sqlsrv_query($conn, "SELECT StudentID, FirstName, MiddleName, LastName, course_name FROM dbo.Students WHERE StudentID = ?", [$id]);
if ($stmt === false) {
	die('Failed to load student.');
}
$student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);
if (!$student) {
	die('Student not found.');
}

$studentName = trim(($student['FirstName'] ?? '') . ' ' . ($student['MiddleName'] ?? '') . ' ' . ($student['LastName'] ?? ''));
$title = 'Delete Student';
$active = 'students';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Delete Student</h1>

<div class="card form-narrow">
	<div class="card-body">
		<?php if ($error): ?>
			<div class="alert alert-danger"><?= e($error) ?></div>
		<?php endif; ?>
		<p>Are you sure you want to delete this student?</p>
		<p><strong>#<?= e($student['StudentID']) ?> &mdash; <?= e($studentName) ?></strong></p>
		<p><?= e($student['course_name']) ?></p>
		<form method="post" action="delete.php?id=<?= e($id) ?>" onsubmit="return confirm('Confirm delete?');">
			<input type="hidden" name="id" value="<?= e($id) ?>">
			<button class="btn btn-danger" type="submit">Yes, Delete</button>
			<a class="btn btn-secondary" href="index.php">Cancel</a>
		</form>
	</div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
