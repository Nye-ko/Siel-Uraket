<?php

function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatDate(string $dateString): string {
    $date = new DateTime($dateString);
    return $date->format('M d, Y');
}

function money($value) {
    return is_numeric($value) ? number_format((float)$value) : $value;
}

function logoutAndRedirect() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}

?>
