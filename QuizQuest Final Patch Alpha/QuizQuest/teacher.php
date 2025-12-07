<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost","root","","quizmaker");
if ($mysqli->connect_error) die("Connection failed: ".$mysqli->connect_error);

$teacher_id = (int)$_SESSION['user_id'];
$teacher_name = $_SESSION['username'] ?? 'Teacher';

// detect if inside a class
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
$inside_class = false;
$class_data = null;

if ($class_id) {
    $cstmt = $mysqli->prepare("SELECT id, title, section, class_code, created_at FROM classes WHERE id = ? AND teacher_id = ?");
    $cstmt->bind_param("ii", $class_id, $teacher_id);
    $cstmt->execute();
    $cres = $cstmt->get_result();
    if ($cres && $cres->num_rows) {
        $inside_class = true;
        $class_data = $cres->fetch_assoc();
    } else {
        // invalid/unauthorized class -> ignore
        $class_id = null;
    }
    $cstmt->close();
}

// function to render quizzes
function renderQuizCards($mysqli, $teacher_id, $class_id){
    $stmt = $mysqli->prepare("SELECT id, title, class_code, created_at FROM quizzes WHERE teacher_id = ? AND class_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("ii", $teacher_id, $class_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        while ($q = $res->fetch_assoc()) {
            echo '<div class="col-md-4 col-sm-6">';
            echo '  <div class="card subject-card h-100">';
            echo '    <div class="card-body d-flex flex-column">';
            echo '      <div class="d-flex justify-content-between align-items-start mb-2">';
            echo '        <h5 class="card-title mb-0">'.htmlspecialchars($q['title']).'</h5>';
            echo '        <span class="badge bg-primary">Code: '.htmlspecialchars($q['class_code']).'</span>';
            echo '      </div>';
            echo '      <p class="card-text small text-muted mb-3">Manage or review this quiz.</p>';
            echo '      <div class="d-flex gap-2 mb-3">';
            echo '        <button class="btn btn-sm btn-outline-light flex-fill" onclick="editQuiz('.(int)$q['id'].')">Edit</button>';
            echo '        <a href="quiz_results.php?class_id='.urlencode($class_id).'&quiz_id='.urlencode($q['id']).'" class="btn btn-sm btn-outline-light flex-fill text-center">Results</a>';
            echo '      </div>';
            echo '      <div class="mt-auto text-end">';
            echo '        <small class="text-muted">Created: '.date('M d, Y', strtotime($q['created_at'])).'</small>';
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<p class="text-muted">No quizzes found for this class yet.</p>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Teacher Dashboard - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php"><i data-lucide="user"></i> Profile (<?php echo htmlspecialchars($teacher_name); ?>)</a>
            
            <?php if ($inside_class): ?>
                <a class="nav-item active" href="teacher.php?class_id=<?php echo (int)$class_id; ?>"><i data-lucide="layout"></i> Quizzes</a>
                <a class="nav-item" href="quizmaker/index.php?class_id=<?php echo (int)$class_id; ?>"><i data-lucide="edit-3"></i> Quizmaker</a>
            <?php endif; ?>
            <a class="nav-item" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content">
    <div class="avatar-container">
        <span class="greeting">Hello! <?php echo htmlspecialchars($teacher_name); ?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="avatar" class="freiren-avatar">
    </div>

    <?php if ($inside_class): ?>
        <div class="greeting-box d-flex justify-content-between align-items-center">
            <div class="greeting-text">
                <small>Class</small>
                <h2><?php echo htmlspecialchars($class_data['title']); ?></h2>
                <div class="greeting-box-line"></div>
                <p>Section: <?php echo htmlspecialchars($class_data['section']); ?> — Code: <strong><?php echo htmlspecialchars($class_data['class_code']); ?></strong></p>
            </div>
            <div class="greeting-buttons d-flex gap-2">
                <a href="classes.php" class="btn btn-sm btn-outline-light">Back to Classes</a>
                <a href="quizmaker/index.php?tab=create&class_id=<?php echo (int)$class_id; ?>" class="btn btn-sm btn-primary">+ Create Quiz</a>
            </div>
        </div>

        <h2 class="quizzes-title mb-4"><?php echo htmlspecialchars($class_data['title']); ?> — Quizzes</h2>
        <div class="row g-4">
            <?php renderQuizCards($mysqli, $teacher_id, $class_id); ?>
        </div>
    <?php else: ?>
        <?php header("Location: classes.php"); exit; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script src="teacherscripts.js"></script>
<script>
<?php if ($inside_class): ?>
function editQuiz(qid){
    window.location.href = `quizmaker/index.php?tab=update&quiz_id=${qid}&class_id=<?php echo (int)$class_id; ?>`;
}
<?php endif; ?>
lucide.replace();
</script>
</body>
</html>
<?php $mysqli->close(); ?>
