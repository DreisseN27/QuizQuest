<?php
session_start();

// Only allow teachers here
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

// DB connection (use your config.php if you have one)
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Safe session values
$teacher_id   = (int) ($_SESSION['user_id'] ?? 0);
$teacher_name = htmlspecialchars($_SESSION['username'] ?? '');

// Render quiz cards for this teacher
function renderQuizCards($conn, $teacher_id) {
    $query = "SELECT id, class_code, title, created_at FROM quizzes WHERE teacher_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo '<div class="alert alert-danger">Failed to prepare statement.</div>';
        return;
    }
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($quiz = $result->fetch_assoc()) {
            $qid = (int)$quiz['id'];
            $title = htmlspecialchars($quiz['title']);
            $class_code = htmlspecialchars($quiz['class_code']);
            $created = date('M d, Y', strtotime($quiz['created_at']));

            echo '<div class="col-md-4 col-sm-6">';
            echo '  <div class="card subject-card h-100">';
            echo '    <div class="card-body d-flex flex-column">';
            echo "      <div class=\"d-flex justify-content-between align-items-start mb-2\">";
            echo "        <h5 class=\"card-title mb-0\">{$title}</h5>";
            echo "        <span class=\"badge bg-primary\">Code: {$class_code}</span>";
            echo '      </div>';
            echo '      <p class="card-text small text-muted mb-3">Manage or review this quiz.</p>';
            echo '      <div class="d-flex gap-2 mb-3">';
            // Edit button calls JS function editQuiz(quizId)
            echo "        <button class=\"btn btn-sm btn-outline-light flex-fill\" onclick=\"editQuiz({$qid})\">Edit</button>";
            echo "        <a href=\"quiz_results.php?class_code=" . urlencode($quiz['class_code']) . "\" class=\"btn btn-sm btn-outline-light flex-fill text-center\">Results</a>";
            echo '      </div>';
            echo '      <div class="mt-auto text-end">';
            echo "        <small class=\"text-muted\">Created: {$created}</small>";
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12"><p class="text-muted">You haven’t created any quizzes yet.</p></div>';
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Teacher Dashboard - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="teacher.css">
<script type="module" src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest Logo">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='profile.php'){echo 'active';} ?>" href="profile.php"><i data-lucide="user"></i> Profile (<?php echo htmlspecialchars($teacher_name); ?>)</a>
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='teacher.php'){echo 'active';} ?>" href="teacher.php"><i data-lucide="layout"></i> Quizzes</a>
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='index.php'){echo 'active';} ?>" href="quizmaker/index.php"><i data-lucide="edit-3"></i> Quizmaker</a>
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='leaderboard.php'){echo 'active';} ?>" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content">
 <div class="avatar-container">
    <span class="greeting">Hello! <?php echo htmlspecialchars($teacher_name); ?></span>
    <img src="https://i.imgur.com/oQEsWSV.png" alt="Freiren Avatar" class="freiren-avatar">
</div>
<h2 class="quizzes-title mb-4">Your Quizzes</h2>
<div class="row g-4">
    <?php renderQuizCards($conn,$teacher_id); ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>
<script>
function editQuiz(quizId){ window.location.href=`quizmaker/index.php?tab=update&quiz_id=${quizId}`; }
lucide.replace();
</script>
</body>
</html>
<?php $conn->close(); ?>
