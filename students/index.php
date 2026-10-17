<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$message = $_GET['msg'] ?? '';
$error = '';
$students = [];
$sql = "SELECT StudentID, FirstName, MiddleName, LastName, Gender, Age, Section, Score, EnrollmentDate, IsActive, Email, course_name FROM dbo.Students ORDER BY StudentID DESC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
	$error = 'Failed to load students.';
} else {
	while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
		$students[] = $row;
	}
	sqlsrv_free_stmt($stmt);
}

$title = 'Student List';
$active = 'students';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Student List</h1>
<p><a href="../index.php">&larr; Back to Home</a></p>

<?php if ($message): ?>
	<div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
	<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<p><a class="btn btn-primary" href="add.php">+ Add New Student</a></p>

<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
	<thead class="table-light">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Gender</th>
			<th>Age</th>
			<th>Section</th>
			<th>Score</th>
			<th>Enrollment Date</th>
			<th>Status</th>
			<th>Email</th>
			<th>Course</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		<?php if (empty($students)): ?>
			<tr><td colspan="11">No students found.</td></tr>
		<?php else: ?>
			<?php foreach ($students as $student): ?>
				<tr>
					<td><?= e($student['StudentID']) ?></td>
					<td><?= e(trim(($student['FirstName'] ?? '') . ' ' . ($student['MiddleName'] ?? '') . ' ' . ($student['LastName'] ?? ''))) ?></td>
					<td><?= e($student['Gender']) ?></td>
					<td><?= e($student['Age']) ?></td>
					<td><?= e($student['Section']) ?></td>
					<td><?= e($student['Score']) ?></td>
					<td><?= e($student['EnrollmentDate'] instanceof DateTime ? $student['EnrollmentDate']->format('Y-m-d') : $student['EnrollmentDate']) ?></td>
					<td><?= $student['IsActive'] === null ? '-' : ($student['IsActive'] ? 'Active' : 'Inactive') ?></td>
					<td><?= e($student['Email']) ?></td>
					<td><?= e($student['course_name']) ?></td>
					<td class="actions text-nowrap">
						<a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= e($student['StudentID']) ?>">Edit</a>
						<a class="btn btn-sm btn-outline-danger" href="delete.php?id=<?= e($student['StudentID']) ?>">Delete</a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
