<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'] ?? 0;

// Handle class code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['class_code'])) {
        $class_code = trim($_POST['class_code']);

        // Check if the class code exists in quizzes
        $stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Save the class code for this student if not already saved
            $stmtCheck = $conn->prepare("SELECT * FROM student_classes WHERE student_id = ? AND class_code = ?");
            $stmtCheck->bind_param("is", $student_id, $class_code);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();

            if ($checkResult->num_rows === 0) {
                $stmtInsert = $conn->prepare("INSERT INTO student_classes (student_id, class_code) VALUES (?, ?)");
                $stmtInsert->bind_param("is", $student_id, $class_code);
                $stmtInsert->execute();
            }
        } else {
            $error = "Invalid class code.";
        }
    }

    // Handle removal
    if (!empty($_POST['remove_class_code'])) {
        $remove_code = trim($_POST['remove_class_code']);
        $stmtRemove = $conn->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_code = ?");
        $stmtRemove->bind_param("is", $student_id, $remove_code);
        $stmtRemove->execute();
    }
}

// Render active class cards
function renderClassCards($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT sc.class_code, q.title
        FROM student_classes sc
        JOIN quizzes q ON sc.class_code = q.class_code
        WHERE sc.student_id = ?
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="col-md-4 col-sm-6">';
            echo '<div class="card mb-3">';
            echo '<div class="card-body d-flex flex-column">';
            echo '<h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>';
            echo '<p class="card-text">Class Code: ' . htmlspecialchars($row['class_code']) . '</p>';
            echo '<div class="mt-auto d-flex justify-content-between">';
            echo '<a href="class_quizzes.php?class_code=' . urlencode($row['class_code']) . '" class="btn btn-info btn-sm">View Quizzes</a>';
            echo '<form method="POST" style="margin:0;">';
            echo '<input type="hidden" name="remove_class_code" value="' . htmlspecialchars($row['class_code']) . '">';
            echo '<button type="submit" class="btn btn-danger btn-sm">Remove</button>';
            echo '</form>';
            echo '</div>';
            echo '</div></div></div>'; // card-body, card, col
        }
    } else {
        echo '<p class="text-muted">No active class codes entered yet.</p>';
    }
}

// Render completed quizzes
function renderCompletedQuizzes($conn, $student_id) {
    // Select distinct quizzes for this student
    $stmt = $conn->prepare("
        SELECT sq.quiz_id, q.title, sq.score, sq.taken_at
        FROM student_quizzes sq
        JOIN quizzes q ON sq.quiz_id = q.id
        WHERE sq.student_id = ?
        GROUP BY sq.quiz_id
        ORDER BY sq.taken_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($quiz = $result->fetch_assoc()) {
            echo '<div class="col-md-4 col-sm-6">';
            echo '<div class="card mb-3">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($quiz['title']) . '</h5>';
            echo '<p class="small text-muted mb-2">Score: ' . htmlspecialchars($quiz['score']) . '</p>';
            echo '<p class="small text-muted">Taken on: ' . date('M d, Y H:i', strtotime($quiz['taken_at'])) . '</p>';
            echo '</div></div></div>';
        }
    } else {
        echo '<p class="text-muted">No completed quizzes yet.</p>';
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
    <!-- Enter Class Code -->
    <div class="mb-4">
        <form method="POST" class="d-flex gap-2">
            <input type="text" name="class_code" class="form-control form-control-sm" placeholder="Enter Class Code" required>
            <button type="submit" class="btn btn-primary btn-sm">Add Class</button>
        </form>
        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>

    <!-- Active Class Cards -->
    <h4>Active Classes</h4>
    <div class="row g-4 mb-5">
        <?php renderClassCards($conn, $student_id); ?>
    </div>

    <!-- Completed Quizzes -->
    <h4>Completed Quizzes</h4>
    <div class="row g-4">
        <?php renderCompletedQuizzes($conn, $student_id); ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
