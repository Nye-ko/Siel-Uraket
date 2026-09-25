<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../auth/signup.php');
	exit;
}

require_once __DIR__ . '/../config/database.php';

$minimumPasswordLength = 8;
$role = $_POST['role'] ?? '';
$fullName = trim($_POST['fullName'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$organization = trim($_POST['orgUnit'] ?? '');
$agreedToTerms = isset($_POST['agreedToTerms']);
$fieldErrors = [];
$formError = '';

$validRoles = ['freelancer', 'client'];

if (!in_array($role, $validRoles, true)) {
	$formError = "Choose whether you're signing up as a student or as faculty/staff.";
}

if ($fullName === '') {
	$fieldErrors['fullName'] = 'Enter your full name.';
}

if ($email === '') {
	$fieldErrors['email'] = 'Enter your email.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$fieldErrors['email'] = 'Enter a valid email address.';
}

if (strlen($password) < $minimumPasswordLength) {
	$fieldErrors['password'] = "Use at least {$minimumPasswordLength} characters.";
}

if ($confirmPassword === '') {
	$fieldErrors['confirmPassword'] = 'Confirm your password.';
} elseif ($confirmPassword !== $password) {
	$fieldErrors['confirmPassword'] = "Passwords don't match.";
}

if ($organization === '') {
	$fieldErrors['orgUnit'] = $role === 'freelancer'
		? 'Enter your college.'
		: 'Enter your college or office.';
}

if (!$agreedToTerms) {
	$fieldErrors['agreedToTerms'] = 'You need to agree to continue.';
}

if ($formError !== '' || !empty($fieldErrors)) {
	$_SESSION['signupForm'] = [
		'role' => $role,
		'fullName' => $fullName,
		'email' => $email,
		'orgUnit' => $organization,
		'agreedToTerms' => $agreedToTerms,
		'formError' => $formError,
		'fieldErrors' => $fieldErrors,
	];

	header('Location: ../auth/signup.php');
	exit;
}

$existingUserStatement = $db->prepare(
	'SELECT id FROM users WHERE email = ? LIMIT 1'
);
$existingUserStatement->bind_param('s', $email);
$existingUserStatement->execute();
$existingUserStatement->store_result();

if ($existingUserStatement->num_rows > 0) {
	$existingUserStatement->close();
	$_SESSION['signupForm'] = [
		'role' => $role,
		'fullName' => $fullName,
		'email' => $email,
		'orgUnit' => $organization,
		'agreedToTerms' => $agreedToTerms,
		'formError' => '',
		'fieldErrors' => [
			'email' => 'An account with this email already exists.',
		],
	];

	header('Location: ../auth/signup.php');
	exit;
}
$existingUserStatement->close();

$transactionStarted = false;

try {
	$db->begin_transaction();
	$transactionStarted = true;

	$userId = 'user-' . bin2hex(random_bytes(16));
	$passwordHash = password_hash($password, PASSWORD_DEFAULT);

	$userStatement = $db->prepare(
		'INSERT INTO users (id, role, full_name, email, password_hash)
		 VALUES (?, ?, ?, ?, ?)'
	);
	$userStatement->bind_param('sssss', $userId, $role, $fullName, $email, $passwordHash);
	$userStatement->execute();
	$userStatement->close();

	$profileStatement = $db->prepare(
		'INSERT INTO user_profiles (user_id, organization)
		 VALUES (?, ?)'
	);
	$profileStatement->bind_param('ss', $userId, $organization);
	$profileStatement->execute();
	$profileStatement->close();

	$db->commit();

} catch (mysqli_sql_exception $error) {
	if ($transactionStarted) {
		$db->rollback();
	}

	if ($error->getCode() === 1062) {
		$fieldErrors['email'] = 'An account with this email already exists.';
	} else {
		$formError = 'We could not create your account. Please try again.';
	}

	$_SESSION['signupForm'] = [
		'role' => $role,
		'fullName' => $fullName,
		'email' => $email,
		'orgUnit' => $organization,
		'agreedToTerms' => $agreedToTerms,
		'formError' => $formError,
		'fieldErrors' => $fieldErrors,
	];

	header('Location: ../auth/signup.php');
	exit;
}

session_regenerate_id(true);
$_SESSION['currentUser'] = [
	'id' => $userId,
	'fullName' => $fullName,
	'email' => $email,
	'role' => $role,
];

header('Location: ../' . $role . '/profile.php');
exit;
