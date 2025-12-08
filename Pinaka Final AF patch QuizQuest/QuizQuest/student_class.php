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

// Get class info and teacher from classes table
$stmt = $conn->prepare("
    SELECT c.title AS class_title, c.section, u.full_name AS teacher_name
    FROM classes c
    JOIN users u ON c.teacher_id = u.id
    WHERE UPPER(c.class_code) = UPPER(?)
    LIMIT 1
");
$stmt->bind_param("s", $class_code);
$stmt->execute();
$class_result = $stmt->get_result();

if ($class_result->num_rows === 0) {
    die("Class not found.");
}

$class_info = $class_result->fetch_assoc();

// Get all quizzes for this class along with completion status
$stmt2 = $conn->prepare("
    SELECT q.id, q.title, q.created_at,
           (SELECT 1 FROM student_quizzes sq WHERE sq.quiz_id = q.id AND sq.student_id = ?) AS taken
    FROM quizzes q
    WHERE UPPER(q.class_code) = UPPER(?)
    ORDER BY q.created_at DESC
");
$stmt2->bind_param("is", $student_id, $class_code);
$stmt2->execute();
$quizzes = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Class Details - <?php echo htmlspecialchars($class_info['class_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/teacher.css">

    <style>
        body {
            background-color: #0f172a;
            color: #fff;
        }
        .subject-card {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 15px;
            color: #fff;
        }
        .subject-card .btn {
            border-color: #fff;
            color: #fff;
        }
        .subject-card .btn:hover:not(:disabled) {
            background-color: #fff;
            color: #000;
        }
        .btn-completed {
            background-color: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<div class="container mt-5">
    <!-- Back to Dashboard Button -->
    <div class="mb-3">
        <a href="student.php" class="btn btn-sm btn-outline-light">
            &larr; Back to Dashboard
        </a>
    </div>
<body>
<div class="container mt-5">
    <div class="mb-4">
        <h3><?php echo htmlspecialchars($class_info['class_title']); ?></h3>
        <p><strong>Section:</strong> <?php echo htmlspecialchars($class_info['section']); ?></p>
        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($class_info['teacher_name']); ?></p>
        <p><strong>Class Code:</strong> <?php echo htmlspecialchars($class_code); ?></p>
    </div>

    <h5 class="mb-3">Available Quizzes</h5>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <?php if ($quizzes->num_rows > 0): ?>
            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                <div class="col">
                    <div class="card subject-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                                <span class="badge bg-dark"><?php echo htmlspecialchars($class_code); ?></span>
                            </div>
                            <p class="card-text small text-light mb-3">
                                <?php echo $quiz['taken'] ? 'You have completed this quiz.' : 'Click below to take this quiz.'; ?>
                            </p>
                            <div class="mt-auto">
                                <?php if ($quiz['taken']): ?>
                                    <button class="btn btn-sm btn-completed w-100" disabled>Completed</button>
                                <?php else: ?>
                                    <a href="start_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" 
                                       class="btn btn-sm btn-outline-light w-100">Take Quiz</a>
                                <?php endif; ?>
                                <div class="text-end mt-2">
                                    <small>Created: <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No quizzes created yet for this class.</p>
        <?php endif; ?>
    </div>
</div>



</body>
</html>

<?php
$stmt->close();
$stmt2->close();
$conn->close();
?>
