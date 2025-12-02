<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to take a quiz.");
}

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id'], $_POST['answers'])) {
    die("Invalid data submitted.");
}

$quiz_id = (int)$_POST['quiz_id'];
$answers = $_POST['answers'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

// Fetch quiz title
$qInfoStmt = $conn->prepare("SELECT title FROM quizzes WHERE id = ?");
$qInfoStmt->bind_param("i", $quiz_id);
$qInfoStmt->execute();
$qInfoResult = $qInfoStmt->get_result();
$quizInfo = $qInfoResult->fetch_assoc();
$quizTitle = $quizInfo['title'] ?? 'Unknown Quiz';

// Fetch questions
$qstmt = $conn->prepare("SELECT id, question_type, correct_answer, class_code FROM questions WHERE quiz_id = ?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$result = $qstmt->get_result();

$score = 0;
$total = $result->num_rows;
$class_code = null;

while ($row = $result->fetch_assoc()) {
    $q_id = $row['id'];
    $correct = $row['correct_answer'];
    $type = $row['question_type'];
    $class_code = $row['class_code'];

    if (!isset($answers[$q_id])) continue;

    $submitted = $answers[$q_id];

    if ($type === 'multiple' && $submitted === $correct) {
        $score++;
    } elseif ($type !== 'multiple' && trim(strtolower($submitted)) === trim(strtolower($correct))) {
        $score++;
    }
}

// Insert into student_quizzes
$insert = $conn->prepare("INSERT INTO student_quizzes (student_id, quiz_id, score, taken_at) VALUES (?, ?, ?, NOW())");
$insert->bind_param("iii", $student_id, $quiz_id, $score);
$successInsert = $insert->execute();

// Remove from active classes
if ($successInsert && $class_code) {
    $del = $conn->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_code = ?");
    $del->bind_param("is", $student_id, $class_code);
    $del->execute();
}

// Pass/fail logic
$passed = ($total > 0 && ($score / $total) >= 0.5) ? 'Passed' : 'Failed';
$taken_at = date('M d, Y H:i');

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quiz Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card text-center">
        <div class="card-header bg-primary text-white">
            Quiz Result
        </div>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($quizTitle); ?></h5>
            <p class="card-text">Score: <?php echo $score; ?> / <?php echo $total; ?></p>
            <p class="card-text">Taken on: <?php echo $taken_at; ?></p>
            <p class="card-text fw-bold">Status: <?php echo $passed; ?></p>
            <a href="student.php" class="btn btn-success">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
