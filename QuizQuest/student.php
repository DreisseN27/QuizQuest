<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'] ?? 0;

// Function to render completed quizzes
function renderCompletedQuizzes($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT sq.class_code, q.title, sq.score, sq.taken_at
        FROM student_quizzes sq
        JOIN quizzes q ON sq.class_code = q.class_code
        WHERE sq.student_id = ?
        ORDER BY sq.taken_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($quiz = $result->fetch_assoc()) {
            echo '<div class="col-md-4 col-sm-6">';
            echo '<div class="card subject-card h-100">';
            echo '<div class="card-body d-flex flex-column">';
            echo '<h5 class="card-title">' . htmlspecialchars($quiz['title']) . '</h5>';
            echo '<p class="small text-muted mb-1">Class Code: ' . htmlspecialchars($quiz['class_code']) . '</p>';
            echo '<p class="small text-muted mb-2">Score: ' . htmlspecialchars($quiz['score']) . '</p>';
            echo '<p class="small text-muted">Taken on: ' . date('M d, Y H:i', strtotime($quiz['taken_at'])) . '</p>';
            echo '</div></div></div>';
        }
    } else {
        echo '<p class="text-muted">You haven\'t taken any quizzes yet.</p>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="teacher.css">
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top my-navbar">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
        <ul class="nav nav-pills nav-pills-small w-100 align-items-center">
            <div class="d-flex justify-content-center flex-grow-1 gap-2">
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="quizmaker/index.php">Quizmaker</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Leaderboard</a></li>
            </div>
        </ul>
    </div>
</nav>

<header class="header">
    <div class="logo-container text-center">
        <img src="images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="container mt-5">
    <!-- Take Quiz by Code -->
    <div class="take-quiz-box mb-4">
        <form method="GET" action="take_quiz.php" class="d-flex gap-2">
            <input type="text" name="quiz_code" class="form-control form-control-sm" placeholder="Enter Class Code" required>
            <button type="submit" class="btn btn-primary btn-sm">Take Quiz</button>
        </form>
    </div>

    <!-- Completed Quizzes -->
    <h4 class="mb-3">Completed Quizzes</h4>
    <div class="row g-4">
        <?php renderCompletedQuizzes($conn, $student_id); ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
