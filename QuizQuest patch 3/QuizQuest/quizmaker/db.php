<?php
// db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'quizmaker';
$DB_USER = 'root';
$DB_PASS = ''; // set your MySQL password
$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";


try {
$pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
} catch (PDOException $e) {
http_response_code(500);
echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
exit;
}