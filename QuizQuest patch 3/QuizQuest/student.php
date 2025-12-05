<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'] ?? 0;
$student_name = $_SESSION['username'] ?? 'Student';

// Handle class code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['class_code'])) {
        $class_code = trim($_POST['class_code']);

        $stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
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
        SELECT sc.class_code, q.title AS quiz_title, u.full_name AS teacher_name
        FROM student_classes sc
        JOIN quizzes q ON sc.class_code = q.class_code
        JOIN users u ON q.teacher_id = u.id
        WHERE sc.student_id = ?
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $class_code = htmlspecialchars($row['class_code']);
            $quiz_title = htmlspecialchars($row['quiz_title']);
            $teacher_name = htmlspecialchars($row['teacher_name']);

            echo '<div class="col-md-4 col-sm-6">';
            echo '  <div class="card subject-card h-100">';
            echo '    <div class="card-body d-flex flex-column">';
            echo '      <div class="d-flex justify-content-between align-items-start mb-2">';
            echo '        <h5 class="card-title mb-0">' . $quiz_title . '</h5>';
            echo '        <span class="badge bg-primary">Code: ' . $class_code . '</span>';
            echo '      </div>';
            echo '      <p class="card-text small text-muted mb-3">Teacher: ' . $teacher_name . '</p>';
            echo '      <div class="mt-auto d-flex gap-2">';
            echo '        <a href="student_class.php?class_code=' . urlencode($class_code) . '" class="btn btn-sm btn-outline-light flex-fill">View Class</a>';
            echo '        <form method="POST" style="margin:0; flex:1;">';
            echo '          <input type="hidden" name="remove_class_code" value="' . $class_code . '">';
            echo '          <button type="submit" class="btn btn-sm btn-outline-danger flex-fill">Remove</button>';
            echo '        </form>';
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12"><p class="text-muted">No active class codes entered yet.</p></div>';
    }
    $stmt->close();
}

// Render completed quizzes
function renderCompletedQuizzes($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT sq.quiz_id, sq.score, sq.taken_at, q.title AS quiz_title, u.full_name AS teacher_name, q.class_code
        FROM student_quizzes sq
        JOIN quizzes q ON sq.quiz_id = q.id
        JOIN users u ON q.teacher_id = u.id
        WHERE sq.student_id = ?
        ORDER BY sq.taken_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($quiz = $result->fetch_assoc()) {
            $quiz_title = htmlspecialchars($quiz['quiz_title']);
            $teacher_name = htmlspecialchars($quiz['teacher_name']);
            $class_code = htmlspecialchars($quiz['class_code']);
            $score = htmlspecialchars($quiz['score']);
            $taken_at = date('M d, Y H:i', strtotime($quiz['taken_at']));

            echo '<div class="col-md-4 col-sm-6">';
            echo '  <div class="card subject-card h-100">';
            echo '    <div class="card-body d-flex flex-column">';
            echo '      <h5 class="card-title mb-2">' . $quiz_title . '</h5>';
            echo '      <p class="card-text small text-muted mb-1">Teacher: ' . $teacher_name . '</p>';
            echo '      <p class="card-text small text-muted mb-1">Class Code: ' . $class_code . '</p>';
            echo '      <p class="card-text small text-muted mb-1">Score: ' . $score . '</p>';
            echo '      <p class="card-text small text-muted mt-auto">Taken on: ' . $taken_at . '</p>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12"><p class="text-muted">No completed quizzes yet.</p></div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="teacher.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
    <style>
        .subject-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
<canvas id="background-canvas"></canvas>
<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest Logo">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='profile.php'){echo 'active';} ?>" href="profile.php">
                <i data-lucide="user"></i> Profile (<?php echo htmlspecialchars($student_name); ?>)
            </a>
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='student.php'){echo 'active';} ?>" href="student.php">
                <i data-lucide="layout"></i> Quizzes
            </a>
            <a class="nav-item <?php if(basename($_SERVER['PHP_SELF'])=='leaderboard.php'){echo 'active';} ?>" href="leaderboard.php">
                <i data-lucide="award"></i> Leaderboard
            </a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content container mt-4">
    <div class="avatar-container d-flex align-items-center gap-3 mb-4">
        <span class="greeting h5 mb-0">Hello! <?php echo htmlspecialchars($student_name); ?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="Avatar" class="freiren-avatar rounded-circle" width="50" height="50">
    </div>

    <!-- Add Class -->
    <form method="POST" class="d-flex gap-2 mb-4">
        <input type="text" name="class_code" class="form-control form-control-sm" placeholder="Enter Class Code" required>
        <button type="submit" class="btn btn-primary btn-sm">Add Class</button>
    </form>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Active Classes -->
    <h2 class="mb-3">Active Classes</h2>
    <div class="row g-4">
        <?php renderClassCards($conn, $student_id); ?>
    </div>

    <!-- Completed Quizzes -->
    <h2 class="mt-5 mb-3">Completed Quizzes</h2>
    <div class="row g-4">
        <?php renderCompletedQuizzes($conn, $student_id); ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>
<script>lucide.replace();</script>
</body>
</html>

<?php $conn->close(); ?>
