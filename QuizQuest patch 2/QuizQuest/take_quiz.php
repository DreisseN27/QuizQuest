<?php
session_start();

$class_code = trim($_GET['class_code'] ?? '');

if (!$class_code) {
    die("No class code provided.");
}

// Check if the class_code exists in quizzes table
$stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE class_code = ?");
$stmt->bind_param("s", $class_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid class code.");
}

$quiz = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Quiz - <?= htmlspecialchars($quiz['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>
    <p>Class Code: <?= htmlspecialchars($class_code) ?></p>
    <a href="start_quiz.php?class_code=<?= urlencode($class_code) ?>" class="btn btn-primary">
        Start Quiz
    </a>
</div>
</body>
</html>
