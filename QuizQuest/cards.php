<?php
function renderTeacherCards($conn) {
    // Fetch all subjects
    $subjectQuery = "SELECT * FROM subjects ORDER BY updated_at DESC";
    $subjectResult = $conn->query($subjectQuery);

    if ($subjectResult->num_rows > 0) {
        while ($subject = $subjectResult->fetch_assoc()) {
            $subjectId = $subject['id'];

            // Fetch quizzes for this subject
            $quizQuery = "SELECT * FROM quizzes WHERE subject_id = $subjectId ORDER BY created_at ASC";
            $quizResult = $conn->query($quizQuery);
            $quizCount = $quizResult->num_rows;

            echo '<div class="col-md-4 col-sm-6">';
            echo '<div class="card subject-card h-100">';
            echo '<div class="card-body d-flex flex-column">';
            echo '<div class="d-flex justify-content-between align-items-start mb-2">';
            echo '<div>';
            echo '<h5 class="card-title mb-0">' . htmlspecialchars($subject['name']) . '</h5>';
            echo '<small class="text-muted">Teacher: ' . htmlspecialchars($subject['teacher_name']) . '</small>';
            echo '</div>';
            echo '<span class="badge bg-primary">' . $quizCount . ' quiz' . ($quizCount != 1 ? 'zes' : '') . '</span>';
            echo '</div>';

            echo '<p class="card-text small text-muted mb-3">' . htmlspecialchars($subject['description']) . '</p>';

            echo '<ul class="list-group list-group-flush mb-3 flex-grow-1">';
            if ($quizCount > 0) {
                while ($quiz = $quizResult->fetch_assoc()) {
                    echo '<li class="list-group-item d-flex justify-content-between align-items-center px-0">';
                    echo htmlspecialchars($quiz['title']);
                    echo '<a href="quizmaker/quizzes.php?quiz_code=' . urlencode($quiz['quiz_code']) . '" class="btn btn-sm btn-outline-primary">Take</a>';
                    echo '</li>';
                }
            } else {
                echo '<li class="list-group-item px-0 text-muted">No quizzes yet</li>';
            }
            echo '</ul>';

            echo '<div class="mt-auto d-flex justify-content-between">';
            echo '<button class="btn btn-sm btn-outline-secondary">View all</button>';
            echo '<small class="text-muted">Last updated: ' . date('M d, Y', strtotime($subject['updated_at'])) . '</small>';
            echo '</div>';

            echo '</div></div></div>'; // Close card, card-body, col
        }
    } else {
        echo '<p class="text-muted">No subjects available yet.</p>';
    }
}
?>
