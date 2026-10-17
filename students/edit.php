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

$fieldNames = ['FirstName', 'MiddleName', 'LastName', 'Gender', 'Age', 'Section', 'Score', 'EnrollmentDate', 'IsActive', 'Email', 'course_name'];
$studentStmt = sqlsrv_query($conn, "SELECT StudentID, FirstName, MiddleName, LastName, Gender, Age, Section, Score, EnrollmentDate, IsActive, Email, course_name FROM dbo.Students WHERE StudentID = ?", [$id]);
if ($studentStmt === false) {
	die('Failed to load student.');
}
$student = sqlsrv_fetch_array($studentStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($studentStmt);
if (!$student) {
	die('Student not found.');
}

$fields = [];
foreach ($fieldNames as $name) {
	$value = $student[$name];
	$fields[$name] = $value instanceof DateTime ? $value->format('Y-m-d') : (string)($value ?? '');
}

$lengthStmt = sqlsrv_query($conn, "SELECT COL_LENGTH('dbo.Students', 'course_name') AS CourseNameMaxLength");
if ($lengthStmt === false) {
	die('Failed to inspect student table.');
}
$lengthRow = sqlsrv_fetch_array($lengthStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($lengthStmt);
$courseNameMaxLength = (int)($lengthRow['CourseNameMaxLength'] ?? 250);

$courses = [];
$courseStmt = sqlsrv_query($conn, "SELECT course_name FROM dbo.Course WHERE course_name IS NOT NULL ORDER BY course_name");
if ($courseStmt === false) {
	die('Failed to load courses.');
}
while ($courseRow = sqlsrv_fetch_array($courseStmt, SQLSRV_FETCH_ASSOC)) {
	$courses[] = $courseRow['course_name'];
}
sqlsrv_free_stmt($courseStmt);
if ($fields['course_name'] !== '' && !in_array($fields['course_name'], $courses, true)) {
	$courses[] = $fields['course_name'];
}

$genderOptions = ['Female', 'Male', 'Other'];
$genderStmt = sqlsrv_query($conn, "SELECT DISTINCT Gender FROM dbo.Students WHERE Gender IS NOT NULL ORDER BY Gender");
if ($genderStmt === false) {
	die('Failed to load gender options.');
}
while ($genderRow = sqlsrv_fetch_array($genderStmt, SQLSRV_FETCH_ASSOC)) {
	if (!in_array($genderRow['Gender'], $genderOptions, true)) {
		$genderOptions[] = $genderRow['Gender'];
	}
}
sqlsrv_free_stmt($genderStmt);
$genderOptions = array_values(array_unique($genderOptions));
sort($genderOptions);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$fields = [];
	foreach ($fieldNames as $name) {
		$fields[$name] = trim($_POST[$name] ?? '');
	}

	if (strlen($fields['FirstName']) > 50 || strlen($fields['MiddleName']) > 50 || strlen($fields['LastName']) > 50) {
		$error = 'Name fields must not exceed 50 characters.';
	} elseif (strlen($fields['Gender']) > 10 || strlen($fields['Section']) > 1) {
		$error = 'Gender must not exceed 10 characters and section must be one character.';
	} elseif ($fields['Gender'] !== '' && !in_array($fields['Gender'], $genderOptions, true)) {
		$error = 'Choose a gender from the list.';
	} elseif ($fields['Age'] !== '' && filter_var($fields['Age'], FILTER_VALIDATE_INT) === false) {
		$error = 'Age must be an integer.';
	} elseif ($fields['Score'] !== '' && (!is_numeric($fields['Score']) || abs((float)$fields['Score']) > 999.99)) {
		$error = 'Score must be a number from -999.99 to 999.99.';
	} elseif ($fields['EnrollmentDate'] !== '' && !DateTime::createFromFormat('Y-m-d', $fields['EnrollmentDate'])) {
		$error = 'Enrollment date must be a valid date.';
	} elseif (!in_array($fields['IsActive'], ['', '0', '1'], true)) {
		$error = 'Choose a valid status.';
	} elseif ($fields['course_name'] !== '' && !in_array($fields['course_name'], $courses, true)) {
		$error = 'Choose a course from the list.';
	} elseif (strlen($fields['Email']) > 100 || strlen($fields['course_name']) > $courseNameMaxLength) {
		$error = 'Email must not exceed 100 characters and course name must not exceed ' . $courseNameMaxLength . ' characters.';
	} else {
		$sql = "UPDATE dbo.Students SET FirstName = ?, MiddleName = ?, LastName = ?, Gender = ?, Age = ?, Section = ?, Score = ?, EnrollmentDate = ?, IsActive = ?, Email = ?, course_name = ? WHERE StudentID = ?";
		$params = [
			$fields['FirstName'] !== '' ? $fields['FirstName'] : null,
			$fields['MiddleName'] !== '' ? $fields['MiddleName'] : null,
			$fields['LastName'] !== '' ? $fields['LastName'] : null,
			$fields['Gender'] !== '' ? $fields['Gender'] : null,
			$fields['Age'] !== '' ? (int)$fields['Age'] : null,
			$fields['Section'] !== '' ? $fields['Section'] : null,
			$fields['Score'] !== '' ? (float)$fields['Score'] : null,
			$fields['EnrollmentDate'] !== '' ? $fields['EnrollmentDate'] : null,
			$fields['IsActive'] !== '' ? (int)$fields['IsActive'] : null,
			$fields['Email'] !== '' ? $fields['Email'] : null,
			$fields['course_name'] !== '' ? $fields['course_name'] : null,
			$id,
		];
		$stmt = sqlsrv_query($conn, $sql, $params);
		if ($stmt === false) {
			$error = 'Update failed.';
		} else {
			sqlsrv_free_stmt($stmt);
			header('Location: index.php?msg=' . urlencode('Student updated successfully.'));
			exit;
		}
	}
}

$title = 'Edit Student #' . $id;
$active = 'students';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Edit Student #<?= e($id) ?></h1>
<p><a href="index.php">&larr; Back to list</a></p>

<?php if ($error): ?>
	<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="edit.php?id=<?= e($id) ?>" class="form-narrow">
	<input type="hidden" name="id" value="<?= e($id) ?>">
	<div class="mb-3">
		<label class="form-label" for="StudentID">Student ID:</label>
		<input class="form-control" type="number" id="StudentID" value="<?= e($id) ?>" disabled>
	</div>
	<?php foreach (['FirstName' => 'First name', 'MiddleName' => 'Middle name', 'LastName' => 'Last name', 'Section' => 'Section', 'Email' => 'Email'] as $name => $label): ?>
		<?php $maxLength = in_array($name, ['FirstName', 'MiddleName', 'LastName'], true) ? 50 : ($name === 'Gender' ? 10 : ($name === 'Section' ? 1 : ($name === 'course_name' ? $courseNameMaxLength : 100))); ?>
		<div class="mb-3">
			<label class="form-label" for="<?= e($name) ?>"><?= e($label) ?>:</label>
			<input class="form-control" type="text" id="<?= e($name) ?>" name="<?= e($name) ?>" maxlength="<?= $maxLength ?>" value="<?= e($fields[$name]) ?>">
		</div>
	<?php endforeach; ?>
	<div class="mb-3">
		<label class="form-label" for="Gender">Gender:</label>
		<select class="form-select" id="Gender" name="Gender">
			<option value="" <?= $fields['Gender'] === '' ? 'selected' : '' ?>>Not set</option>
			<?php foreach ($genderOptions as $gender): ?>
				<option value="<?= e($gender) ?>" <?= $fields['Gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label class="form-label" for="course_name">Course:</label>
		<select class="form-select" id="course_name" name="course_name">
			<option value="" <?= $fields['course_name'] === '' ? 'selected' : '' ?>>Not set</option>
			<?php foreach ($courses as $course): ?>
				<option value="<?= e($course) ?>" <?= $fields['course_name'] === $course ? 'selected' : '' ?>><?= e($course) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label class="form-label" for="Age">Age:</label>
		<input class="form-control" type="number" id="Age" name="Age" value="<?= e($fields['Age']) ?>">
	</div>
	<div class="mb-3">
		<label class="form-label" for="Score">Score (up to 2 decimal places):</label>
		<input class="form-control" type="number" id="Score" name="Score" step="0.01" min="-999.99" max="999.99" value="<?= e($fields['Score']) ?>">
	</div>
	<div class="mb-3">
		<label class="form-label" for="EnrollmentDate">Enrollment date:</label>
		<input class="form-control" type="date" id="EnrollmentDate" name="EnrollmentDate" value="<?= e($fields['EnrollmentDate']) ?>">
	</div>
	<div class="mb-3">
		<label class="form-label" for="IsActive">Status:</label>
		<select class="form-select" id="IsActive" name="IsActive">
			<option value="" <?= $fields['IsActive'] === '' ? 'selected' : '' ?>>Not set</option>
			<option value="1" <?= $fields['IsActive'] === '1' ? 'selected' : '' ?>>Active</option>
			<option value="0" <?= $fields['IsActive'] === '0' ? 'selected' : '' ?>>Inactive</option>
		</select>
	</div>
	<button class="btn btn-success" type="submit">Update</button>
	<a class="btn btn-secondary" href="index.php">Cancel</a>
</form>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
