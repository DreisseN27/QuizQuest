<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$class_code = $_GET['class_code'] ?? '';

if (empty($class_code)) {
    die("No class code provided.");
}

// Get quiz_id from class_code
$stmtQuiz = $conn->prepare("SELECT id, title FROM quizzes WHERE class_code = ?");
$stmtQuiz->bind_param("s", $class_code);
$stmtQuiz->execute();
$resultQuiz = $stmtQuiz->get_result();

if ($resultQuiz->num_rows === 0) {
    die("Invalid class code or quiz not found.");
}

$quiz = $resultQuiz->fetch_assoc();
$quiz_id = $quiz['id'];

// Get questions for that quiz
$qstmt = $conn->prepare("
    SELECT id, question_text, question_type
    FROM questions
    WHERE quiz_id = ?
    ORDER BY id ASC
");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$questions = $qstmt->get_result();

if ($questions->num_rows === 0) {
    die("No questions found for this quiz.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
    <form method="POST" action="submit_quiz.php">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

        <?php while ($q = $questions->fetch_assoc()): ?>
            <div class="mb-3">
                <label class="form-label"><?php echo htmlspecialchars($q['question_text']); ?></label>
                <?php if ($q['question_type'] === 'multiple'): ?>
                    <?php
                    // Fetch choices for this question
                    $cstmt = $conn->prepare("SELECT choice_label, choice_text FROM choices WHERE question_id = ?");
                    $cstmt->bind_param("i", $q['id']);
                    $cstmt->execute();
                    $choices = $cstmt->get_result();
                    ?>
                    <?php while ($choice = $choices->fetch_assoc()): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                   name="answers[<?php echo $q['id']; ?>]" 
                                   value="<?php echo htmlspecialchars($choice['choice_label']); ?>" required>
                            <label class="form-check-label"><?php echo htmlspecialchars($choice['choice_text']); ?></label>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <input type="text" class="form-control" 
                           name="answers[<?php echo $q['id']; ?>]" required>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <button type="submit" class="btn btn-primary">Submit Quiz</button>
    </form>
</div>
</body>
</html>

<?php $conn->close(); ?>
