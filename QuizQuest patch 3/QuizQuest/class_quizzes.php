<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

$student_id = $_SESSION['user_id'];
$class_code = $_GET['class_code'] ?? '';

if (!$class_code) {
    die("No class code specified.");
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch quizzes for this class
$stmt = $conn->prepare("SELECT id, title, created_at FROM quizzes WHERE class_code = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $class_code);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quizzes in Class <?php echo htmlspecialchars($class_code); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Quizzes for Class Code: <?php echo htmlspecialchars($class_code); ?></h3>
    <div class="row g-4 mt-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($quiz = $result->fetch_assoc()): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                            <p class="small text-muted">Created at: <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></p>
                            <a href="start_quiz.php?class_code=<?php echo urlencode($class_code); ?>&quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">Take Quiz</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No quizzes available for this class yet.</p>
        <?php endif; ?>
    </div>
    <a href="student.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>

<?php $conn->close(); ?>
