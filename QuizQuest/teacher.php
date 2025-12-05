<?php
session_start();

// If not logged in or not a teacher, block access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Logged in teacher info
$teacher_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name']; // or store full_name in session if you prefer

// Function to render quiz cards for THIS teacher
function renderQuizCards($conn, $teacher_id, $teacher_name) {
    $query = "SELECT * FROM quizzes WHERE teacher_id = ? ORDER BY created_at DESC";
    $stmt  = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($quiz = $result->fetch_assoc()) {

            echo '<div class="col-md-4 col-sm-6">';
            echo '  <div class="card subject-card h-100">';
            echo '    <div class="card-body d-flex flex-column">';

            // Top: Teacher name + quiz info
            echo '      <div class="d-flex justify-content-between align-items-start mb-2">';
            echo '        <div>';
            echo '          <h5 class="card-title mb-0">' . htmlspecialchars($teacher_name) . '</h5>';
            echo '          <small class="text-muted">Quiz: ' . htmlspecialchars($quiz['title']) . '</small>';
            echo '        </div>';
            echo '        <span class="badge bg-primary">Code: ' . htmlspecialchars($quiz['quiz_code']) . '</span>';
            echo '      </div>';

            echo '      <p class="card-text small text-muted mb-3">Manage or review this quiz.</p>';

            // Actions
            echo '      <ul class="list-group list-group-flush mb-3 flex-grow-1">';
            echo '        <li class="list-group-item d-flex justify-content-between align-items-center px-0">';
            echo '          Edit Quiz';
            echo '          <a href="quizmaker/edit_quiz.php?quiz_code=' . urlencode($quiz['quiz_code']) . '" class="btn btn-sm btn-outline-light">Edit</a>';
            echo '        </li>';
            echo '        <li class="list-group-item d-flex justify-content-between align-items-center px-0">';
            echo '          View Results';
            echo '          <a href="quiz_results.php?quiz_code=' . urlencode($quiz['quiz_code']) . '" class="btn btn-sm btn-outline-light">Results</a>';
            echo '        </li>';
            echo '      </ul>';

            echo '      <div class="mt-auto d-flex justify-content-between">';
            echo '        <small class="text-muted">Created: ' . date('M d, Y', strtotime($quiz['created_at'])) . '</small>';
            echo '      </div>';

            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<p class="text-muted">You haven’t created any quizzes yet.</p>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="teacher.css">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top my-navbar">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="nav nav-pills nav-pills-small w-100 align-items-center">
        <div class="d-flex justify-content-center flex-grow-1 gap-2">
          <li class="nav-item">
            <a class="nav-link" href="profile.php">Profile <?php echo htmlspecialchars($teacher_name); ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="quizmaker/index.php">Quizmaker</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Leaderboard</a>
          </li>
        </div>

        <!-- Right side: Logout -->
        <li class="nav-item ms-auto me-3">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>
</nav>

<header class="header">
    <div class="logo-container text-center">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<main class="container py-3 mt-2">
    <div class="row g-4">
        <?php renderQuizCards($conn, $teacher_id, $teacher_name); ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
