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

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Add Class Code ---
    if (!empty($_POST['class_code'])) {
        $input_code = trim($_POST['class_code']);

        // Case-insensitive check for class code in classes table
        $stmt = $conn->prepare("SELECT id FROM classes WHERE UPPER(class_code) = UPPER(?)");
        $stmt->bind_param("s", $input_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Check if student already joined this class
            $stmtCheck = $conn->prepare("SELECT * FROM student_classes WHERE student_id = ? AND UPPER(class_code) = UPPER(?)");
            $stmtCheck->bind_param("is", $student_id, $input_code);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();

            if ($checkResult->num_rows === 0) {

    // ✅ Always store class code in UPPERCASE to match leaderboard join
            $cleanCode = strtoupper(trim($input_code));

            $stmtInsert = $conn->prepare("
                INSERT INTO student_classes (student_id, class_code) 
                VALUES (?, ?)
            ");
            $stmtInsert->bind_param("is", $student_id, $cleanCode);
            $stmtInsert->execute();

            $feedback = "<div class='alert alert-success'>Class added and will now appear in Leaderboard!</div>";
            } else {
                $feedback = "<div class='alert alert-info'>You have already joined this class.</div>";
            }

            $stmtCheck->close();
        } else {
            $feedback = "<div class='alert alert-danger'>Invalid class code. Please check with your teacher.</div>";
        }

        $stmt->close();
    }

    // --- Remove Class Code ---
    if (!empty($_POST['remove_class_code'])) {
        $remove_code = trim($_POST['remove_class_code']);
        $stmtRemove = $conn->prepare("DELETE FROM student_classes WHERE student_id = ? AND UPPER(class_code) = UPPER(?)");
        $stmtRemove->bind_param("is", $student_id, $remove_code);
        $stmtRemove->execute();
        $feedback = "<div class='alert alert-warning'>Class removed successfully.</div>";
        $stmtRemove->close();
    }
}

function renderClassCards($conn, $student_id) {
    // Get classes student joined OR has taken at least one quiz
    $stmt = $conn->prepare("
        SELECT DISTINCT c.class_code, c.title AS class_title, c.section, u.full_name AS teacher_name
        FROM classes c
        JOIN users u ON c.teacher_id = u.id
        LEFT JOIN student_classes sc ON UPPER(sc.class_code) = UPPER(c.class_code) AND sc.student_id = ?
        LEFT JOIN student_quizzes sq ON sq.quiz_id IN (SELECT id FROM quizzes WHERE UPPER(class_code) = UPPER(c.class_code)) AND sq.student_id = ?
        WHERE sc.student_id IS NOT NULL OR sq.student_id IS NOT NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $classes = $stmt->get_result();

    if ($classes && $classes->num_rows > 0) {
        while ($class = $classes->fetch_assoc()) {
            $class_code = htmlspecialchars($class['class_code']);
            $class_title = htmlspecialchars($class['class_title']);
            $section = htmlspecialchars($class['section']);
            $teacher_name = htmlspecialchars($class['teacher_name']);

            // Check quiz completion for this class
            $stmtQuizzes = $conn->prepare("
                SELECT q.id, 
                    (SELECT 1 FROM student_quizzes sq WHERE sq.quiz_id = q.id AND sq.student_id = ?) AS taken
                FROM quizzes q
                WHERE UPPER(q.class_code) = UPPER(?)
            ");
            $stmtQuizzes->bind_param("is", $student_id, $class_code);
            $stmtQuizzes->execute();
            $quizzes = $stmtQuizzes->get_result();

            $totalQuizzes = $quizzes->num_rows;
            $completedQuizzes = 0;
            while ($quiz = $quizzes->fetch_assoc()) {
                if ($quiz['taken']) $completedQuizzes++;
            }

            $statusBadge = '';
            if ($totalQuizzes > 0 && $totalQuizzes === $completedQuizzes) {
                $statusBadge = '<span class="badge bg-success ms-2">All Completed</span>';
            } elseif ($completedQuizzes > 0) {
                $statusBadge = '<span class="badge bg-warning ms-2">Some Completed</span>';
            }

            echo '<div class="col-12 col-md-6 col-lg-4">';
            echo '  <a href="student_class.php?class_code=' . urlencode($class_code) . '" class="text-decoration-none">';
            echo '    <div class="card subject-card mb-3 h-100 shadow-sm">';
            echo '      <div class="card-body d-flex flex-column">';
            echo '        <div class="d-flex justify-content-between align-items-start mb-2">';
            echo '          <h5 class="card-title mb-0 text-truncate">' . $class_title . '</h5>';
            echo '          <span class="badge bg-dark">' . $class_code . '</span>' . $statusBadge;
            echo '        </div>';
            echo '        <p class="card-text small text-light mb-0">Section: ' . $section . '</p>';
            echo '        <p class="card-text small text-light mb-2">Teacher: ' . $teacher_name . '</p>';
            echo '        <div class="mt-auto text-end">';
            echo '          <small class="text-light">Click to view quizzes →</small>';
            echo '        </div>';
            echo '      </div>';
            echo '    </div>';
            echo '  </a>';
            echo '</div>';

            $stmtQuizzes->close();
        }
    } else {
        echo '<div class="col-12"><p class="text-muted">No active classes yet.</p></div>';
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
    <link rel="stylesheet" href="assets/css/teacher.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
    <style>
        .subject-card {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 15px;
            color: #fff;
        }
        .subject-card .btn {
            border-color: #fff;
            color: #fff;
        }
        .subject-card .btn:hover {
            background-color: #fff;
            color: #000;
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

    <?php echo $feedback; ?>

    <!-- Active Classes -->
    <h2 class="mb-3">Active Classes</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
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
