<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['quiz_code']) || empty($data['questions'])) {
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

require 'db.php';

try {
    $pdo->beginTransaction();

    // Insert quiz
    $stmt = $pdo->prepare("INSERT INTO quizzes (quiz_code, title) VALUES (?, ?)");
    try {
        $stmt->execute([$data['quiz_code'], $data['title']]);
    } catch (PDOException $e) {
        // Check if duplicate entry error
        if ($e->getCode() == 23000) {
            echo json_encode(['success'=>false, 'error'=>'Quiz code already exists. Please choose a different code.']);
            $pdo->rollBack();
            exit;
        } else {
            throw $e; // other PDO errors
        }
    }

    $quiz_id = $pdo->lastInsertId();

    // Insert questions
    $qStmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
    $cStmt = $pdo->prepare("INSERT INTO choices (question_id, choice_label, choice_text) VALUES (?, ?, ?)");

    foreach ($data['questions'] as $q) {
        $qStmt->execute([$quiz_id, $q['text'], $q['type'], $q['correct']]);
        $question_id = $pdo->lastInsertId();

        if ($q['type'] === 'multiple') {
            foreach ($q['choices'] as $i => $choiceText) {
                $label = chr(65 + $i);
                $cStmt->execute([$question_id, $label, $choiceText]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success'=>true, 'quiz_id'=>$quiz_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
