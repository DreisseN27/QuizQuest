<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$quiz_id = $_GET['quiz_id'] ?? '';
if (empty($quiz_id)) die("No quiz selected.");

// Get quiz info
$stmtQuiz = $conn->prepare("SELECT id, title FROM quizzes WHERE id = ?");
$stmtQuiz->bind_param("i", $quiz_id);
$stmtQuiz->execute();
$resultQuiz = $stmtQuiz->get_result();
if ($resultQuiz->num_rows === 0) die("Quiz not found.");
$quiz = $resultQuiz->fetch_assoc();

// Get questions
$qstmt = $conn->prepare("SELECT id, question_text, question_type FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$questions = $qstmt->get_result();

$allQuestions = [];
while ($q = $questions->fetch_assoc()) {
    $allQuestions[] = $q;
}
$totalQuestions = count($allQuestions);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Take Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #0d1117; color: #fff; }
.container { padding-top: 3rem; }
.quiz-card { display: none; background: rgba(255,255,255,0.05); backdrop-filter: blur(15px); border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; }
.quiz-card.active { display: block; }
.btn-primary, .btn-success { border-radius: 10px; padding: 0.6rem 1.8rem; font-weight: 600; }
.progress-container { margin-bottom: 1.5rem; }
.note { font-size: 0.9rem; color: #f87171; margin-bottom: 0.5rem; }
</style>
</head>
<body>

<div class="container">
    <h3 class="mb-4"><?php echo htmlspecialchars($quiz['title']); ?></h3>

    <div class="progress-container">
        <div class="progress">
            <div id="quizProgress" class="progress-bar bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <form method="POST" action="submit_quiz.php" id="quizForm">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

        <?php foreach ($allQuestions as $index => $q): ?>
            <div class="quiz-card" id="question-<?php echo $index; ?>">
                <h5>Question <?php echo $index + 1; ?> of <?php echo $totalQuestions; ?></h5>
                <p class="fw-bold"><?php echo htmlspecialchars($q['question_text']); ?></p>

                <?php if ($q['question_type'] === 'multiple'): ?>
                    <?php
                    $cstmt = $conn->prepare("SELECT choice_label, choice_text FROM choices WHERE question_id = ? ORDER BY choice_label ASC");
                    $cstmt->bind_param("i", $q['id']);
                    $cstmt->execute();
                    $choices = $cstmt->get_result();
                    ?>
                    <?php while ($choice = $choices->fetch_assoc()): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($choice['choice_label']); ?>" required>
                            <label class="form-check-label"><?php echo htmlspecialchars($choice['choice_text']); ?></label>
                        </div>
                    <?php endwhile; ?>

                <?php elseif ($q['question_type'] === 'truefalse'): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="True" required>
                        <label class="form-check-label">True</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="False" required>
                        <label class="form-check-label">False</label>
                    </div>

                <?php elseif ($q['question_type'] === 'identification'): ?>
                    <div class="note">*Case-sensitive</div>
                    <input type="text" class="form-control mt-2" name="answers[<?php echo $q['id']; ?>]" required>
                <?php endif; ?>

                <div class="mt-3 d-flex justify-content-between">
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-outline-light" onclick="prevQuestion()">Previous</button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if ($index < $totalQuestions - 1): ?>
                        <button type="button" class="btn btn-primary" onclick="nextQuestion()">Next</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-success">Submit Quiz</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>

<script>
let currentQuestion = 0;
const totalQuestions = <?php echo $totalQuestions; ?>;
showQuestion(currentQuestion);

function showQuestion(index) {
    document.querySelectorAll('.quiz-card').forEach((card, i) => {
        card.classList.toggle('active', i === index);
    });
    document.getElementById('quizProgress').style.width = ((index) / totalQuestions) * 100 + '%';
}

function nextQuestion() {
    if (currentQuestion < totalQuestions - 1) {
        currentQuestion++;
        showQuestion(currentQuestion);
    }
}

function prevQuestion() {
    if (currentQuestion > 0) {
        currentQuestion--;
        showQuestion(currentQuestion);
    }
}
</script>

</body>
</html>

<?php $conn->close(); ?>
