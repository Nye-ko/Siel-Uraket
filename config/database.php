<?php

$host = 'localhost';
$database = 'sielUraket';
$username = 'root';
$password = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($host, $username, $password, $database);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and try again.');
}
