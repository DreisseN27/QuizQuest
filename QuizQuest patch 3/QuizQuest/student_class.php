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
        .subject-card .btn:hover {
            background-color: #fff;
            color: #000;
        }
    </style>
</head>

<body>

<div class="container mt-5">
    <div class="mb-4">
        <h3>Class: <?php echo htmlspecialchars($class_info['quiz_title']); ?></h3>
        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($class_info['teacher_name']); ?></p>
        <p><strong>Class Code:</strong> <?php echo htmlspecialchars($class_code); ?></p>
    </div>

    <h5 class="mb-3">Available Quizzes</h5>

    <div class="row g-4">
        <?php if ($quizzes->num_rows > 0): ?>
            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card subject-card h-100">
                        <div class="card-body d-flex flex-column">
                            
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                </h5>
                                <span class="badge bg-dark">
                                    <?php echo htmlspecialchars($class_code); ?>
                                </span>
                            </div>

                            <p class="card-text small text-light mb-3">
                                Click below to take this quiz.
                            </p>

                            <div class="mt-auto">
                                <a href="start_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" 
                                   class="btn btn-sm btn-outline-light w-100">
                                    Take Quiz
                                </a>
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

<?php $conn->close(); ?>
