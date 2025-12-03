<!DOCTYPE html>
<html>
<head>
    <title>Entry Page</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="assets/css/entry_page.css">
</head>

<body>
<header class="header">
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="entry-layout">

    <!-- LEFT SIDE WITH IMAGE -->
    <div class="left-panel">
        <img src="assets/images/entry_page.gif" class="left-image">
    </div>

    <!-- RIGHT SIDE (EMPTY FOR NOW) -->
    <div class="right-panel">
        
        <div class="right-container">    
            <div class="title">
                <img src="assets/images/quizquest-title.png">
            </div> 

            <p class="subheading">Where every quiz is an adventure!</p>

            <div class="buttons">
                    <button type="button" class="btn-login" onclick="window.location.href='login.php'">
                        Login
                    </button>

                <div class="dropdown">
                    <button class="dropdown-toggle" type="button" id="registerDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Sign Up
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="registerDropdown">
                        <li><a class="dropdown-item" href="register_student.php">Student</a></li>
                        <li><a class="dropdown-item" href="register_teacher.php">Teacher</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>