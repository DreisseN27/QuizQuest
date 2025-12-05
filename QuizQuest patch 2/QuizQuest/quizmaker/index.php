<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Manager</title>
    <link rel="stylesheet" href="magic.css">
</head>
<body>

    <!-- GO BACK NAVLINK -->
    <a href="../teacher.php" class="go-back-link">← Go Back</a>

    <!-- TABS -->
    <div class="tabs">
        <div class="tab active" data-tab="createTab">Create Quiz</div>
        <div class="tab" data-tab="viewTab">View/Delete Quizzes</div>
        <div class="tab" data-tab="updateTab">Update Quiz</div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="tab-contents">

        <!-- CREATE QUIZ TAB -->
        <div class="tab-content active" id="createTab">
            <div class="left-side">
                <div class="quiz-meta">
                    <label>Quiz Title:</label>
                    <input type="text" id="quizTitleInput" placeholder="Enter quiz title">
                    <label>Quiz Code:</label>
                    <input type="text" id="quizCodeInput" placeholder="Enter quiz code">
                </div>

                <div class="type-buttons">
                    <button data-type="multiple">Multiple Choice</button>
                    <button data-type="identification">Identification</button>
                    <button data-type="truefalse">True / False</button>
                </div>

                <div id="editor-container">
                    <div id="editor"></div>
                </div>

                <div class="controls">
                    <button id="addQuestionBtn">Add Question</button>
                    <button id="submitQuizBtn">Submit Quiz</button>
                </div>
            </div>

            <div class="right-side">
                <h3>Preview Questions</h3>
                <div id="preview"></div>
            </div>
        </div>

        <!-- VIEW/DELETE QUIZZES TAB -->
        <div class="tab-content" id="viewTab">
            <div class="left-side">
                <h3>All Quizzes</h3>
                <div id="quizList">Loading quizzes...</div>
            </div>
            <div class="right-side">
                <h3>Quiz Details</h3>
                <div id="quizPreview">Select a quiz to view questions...</div>
            </div>
        </div>

        <!-- UPDATE QUIZ TAB -->
        <div class="tab-content" id="updateTab">
            <div class="left-side">
                <h3>Update Quiz</h3>
                <label>Select Quiz:</label>
                <select id="updateQuizSelect"></select>
                <div id="updateContent"></div>
            </div>
            <div class="right-side">
                <h3>Quiz Preview</h3>
                <div id="updatePreview">Select a quiz to see questions here...</div>
            </div>
        </div>

    </div>

    <!-- SCRIPT -->
    <script src="scripts.js"></script>
</body>
</html>
