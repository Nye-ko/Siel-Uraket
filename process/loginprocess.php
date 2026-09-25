<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuthService.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$emailError = '';
$passwordError = '';

if ($email === '') {
    $emailError = 'Enter your email.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailError = 'Enter a valid email address.';
}

if ($password === '') {
    $passwordError = 'Enter your password.';
}

if ($emailError !== '' || $passwordError !== '') {
    $_SESSION['loginForm'] = [
        'email' => $email,
        'formError' => '',
        'emailError' => $emailError,
        'passwordError' => $passwordError,
    ];

    header('Location: ../auth/login.php');
    exit;
}

$authService = new AuthService($db);
$user = $authService->login($email, $password);

if (!$user) {
    $_SESSION['loginForm'] = [
        'email' => $email,
        'formError' => "That email and password don't match an account.",
        'emailError' => '',
        'passwordError' => '',
    ];

    header('Location: ../auth/login.php');
    exit;
}

$_SESSION['currentUser'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'fullName' => $user['full_name'],
    'role' => $user['role'],
];

header('Location: ../' . $user['role'] . '/dashboard.php');
exit;