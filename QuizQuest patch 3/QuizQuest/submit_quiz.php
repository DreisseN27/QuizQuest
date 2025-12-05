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

// Fetch quiz info including class_code
$qInfoStmt = $conn->prepare("SELECT title, class_code FROM quizzes WHERE id = ?");
$qInfoStmt->bind_param("i", $quiz_id);
$qInfoStmt->execute();
$qInfoResult = $qInfoStmt->get_result();
$quizInfo = $qInfoResult->fetch_assoc();
$quizTitle = $quizInfo['title'] ?? 'Unknown Quiz';
$class_code = $quizInfo['class_code'] ?? null;

// Fetch questions and correct answers
$qstmt = $conn->prepare("SELECT id, question_type, correct_answer FROM questions WHERE quiz_id = ?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$result = $qstmt->get_result();

$score = 0;
$total = $result->num_rows;

// Score calculation
while ($row = $result->fetch_assoc()) {
    $q_id = $row['id'];
    $correct = $row['correct_answer'];
    $type = $row['question_type'];

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

// Remove class from active classes if submission successful
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
<style>
body {
    background: #0d1117;
    color: #fff;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-result {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-result:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
}
.card-title {
    font-size: 1.8rem;
    margin-bottom: 1rem;
}
.score {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}
.status {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}
.text-success { color: #4ade80; }
.text-danger { color: #f87171; }
.btn-back {
    border-radius: 10px;
    padding: 0.6rem 1.5rem;
    font-weight: 600;
}
</style>
</head>
<body>

<div class="card-result">
    <h3 class="card-title"><?php echo htmlspecialchars($quizTitle); ?></h3>
    <p class="score">Score: <strong><?php echo $score; ?></strong> / <?php echo $total; ?></p>
    <p class="score">Taken on: <strong><?php echo $taken_at; ?></strong></p>
    <p class="status <?php echo $passed === 'Passed' ? 'text-success' : 'text-danger'; ?>"><?php echo $passed; ?></p>
    <a href="student.php" class="btn btn-success btn-back">Back to Dashboard</a>
</div>

</body>
</html>
