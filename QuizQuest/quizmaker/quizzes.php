<?php
header('Content-Type: application/json');
require 'db.php';

// Read JSON input if POST body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Determine action
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? null;

if (!$action) {
    echo json_encode(['success'=>false, 'error'=>'No action specified']);
    exit;
}

// ----------------------
// VIEW ALL QUIZZES
// ----------------------
if ($action === 'view') {
    $stmt = $pdo->query("SELECT id, class_code, title FROM quizzes ORDER BY created_at DESC");
    $quizzes = $stmt->fetchAll();
    echo json_encode($quizzes);
    exit;
}

// ----------------------
// DELETE A QUIZ
// ----------------------
if ($action === 'delete') {
    $id = $_POST['id'] ?? $input['id'] ?? null;
    if (!$id) {
        echo json_encode(['success'=>false, 'error'=>'Quiz ID required']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'error'=>'Failed to delete']);
    }
    exit;
}

// ----------------------
// GET QUIZ DETAILS (FOR UPDATE)
// ----------------------
if ($action === 'details') {
    $quiz_id = $_GET['quiz_id'] ?? $input['quiz_id'] ?? null;
    if (!$quiz_id) {
        echo json_encode([]);
        exit;
    }

    // Fetch quiz
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch questions
    $qStmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
    $qStmt->execute([$quiz_id]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch multiple choice options
    foreach ($questions as &$q) {
        if ($q['question_type'] === 'multiple') {
            $cStmt = $pdo->prepare("SELECT choice_label, choice_text FROM choices WHERE question_id = ? ORDER BY choice_label");
            $cStmt->execute([$q['id']]);
            $q['choices'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode(['quiz'=>$quiz, 'questions'=>$questions]);
    exit;
}

// ----------------------
// UPDATE QUIZ
// ----------------------
if ($action === 'update') {
    if (!$input || empty($input['quiz_id']) || empty($input['title']) || !isset($input['questions'])) {
        echo json_encode(['success'=>false, 'error'=>'Invalid data']);
        exit;
    }

    $quiz_id = $input['quiz_id'];
    $title = $input['title'];
    $questions = $input['questions'];
    $deletedQuestions = $input['deletedQuestions'] ?? [];

    try {
        $pdo->beginTransaction();

        // Update quiz title
        $stmt = $pdo->prepare("UPDATE quizzes SET title = ? WHERE id = ?");
        $stmt->execute([$title, $quiz_id]);

        // Delete removed questions
        if (!empty($deletedQuestions)) {
            $in  = str_repeat('?,', count($deletedQuestions) - 1) . '?';
            $stmtDelChoices = $pdo->prepare("DELETE FROM choices WHERE question_id IN ($in)");
            $stmtDelChoices->execute($deletedQuestions);

            $stmtDelQuestions = $pdo->prepare("DELETE FROM questions WHERE id IN ($in)");
            $stmtDelQuestions->execute($deletedQuestions);
        }

        // Insert or update questions
        foreach ($questions as $q) {
            if (!empty($q['id'])) {
                // Update existing question
                $stmtQ = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ?, correct_answer = ? WHERE id = ?");
                $stmtQ->execute([$q['text'], $q['type'], $q['correct'], $q['id']]);

                // For multiple choice, update choices
                if ($q['type'] === 'multiple') {
                    // Delete old choices first
                    $stmtDel = $pdo->prepare("DELETE FROM choices WHERE question_id = ?");
                    $stmtDel->execute([$q['id']]);

                    $stmtC = $pdo->prepare("INSERT INTO choices (question_id, choice_label, choice_text) VALUES (?, ?, ?)");
                    foreach ($q['choices'] as $i => $c) {
                        $label = chr(65 + $i);
                        $stmtC->execute([$q['id'], $label, $c]);
                    }
                }
            } else {
                // Insert new question
                $stmtIns = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
                $stmtIns->execute([$quiz_id, $q['text'], $q['type'], $q['correct']]);
                $question_id = $pdo->lastInsertId();

                if ($q['type'] === 'multiple') {
                    $stmtC = $pdo->prepare("INSERT INTO choices (question_id, choice_label, choice_text) VALUES (?, ?, ?)");
                    foreach ($q['choices'] as $i => $c) {
                        $label = chr(65 + $i);
                        $stmtC->execute([$question_id, $label, $c]);
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success'=>true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}
