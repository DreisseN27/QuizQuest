<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'] ?? 0;

if (!isset($_GET['class_code'])) {
    die("Class not specified.");
}

$class_code = $_GET['class_code'];

// Get class info and teacher
$stmt = $conn->prepare("
    SELECT q.title AS quiz_title, u.full_name AS teacher_name
    FROM quizzes q
    JOIN users u ON q.teacher_id = u.id
    WHERE q.class_code = ?
    LIMIT 1
");
$stmt->bind_param("s", $class_code);
$stmt->execute();
$class_result = $stmt->get_result();

if ($class_result->num_rows === 0) {
    die("Class not found.");
}

$class_info = $class_result->fetch_assoc();

// Get all quizzes for this class
$stmt2 = $conn->prepare("
    SELECT id, title, created_at
    FROM quizzes
    WHERE class_code = ?
    ORDER BY created_at DESC
");
$stmt2->bind_param("s", $class_code);
$stmt2->execute();
$quizzes = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Class Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Class: <?php echo htmlspecialchars($class_info['quiz_title']); ?></h3>
    <p><strong>Teacher:</strong> <?php echo htmlspecialchars($class_info['teacher_name']); ?></p>
    <p><strong>Class Code:</strong> <?php echo htmlspecialchars($class_code); ?></p>

    <h5 class="mt-4">Quizzes Created by Teacher</h5>
    <?php if ($quizzes->num_rows > 0): ?>
        <ul class="list-group">
            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo htmlspecialchars($quiz['title']); ?>
                    <a href="start_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">Take Quiz</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted">No quizzes created yet for this class.</p>
    <?php endif; ?>
</div>
</body>
</html>
<?php $conn->close(); ?>
