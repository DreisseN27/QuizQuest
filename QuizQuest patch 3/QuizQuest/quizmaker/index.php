<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Manager</title>
    <link rel="stylesheet" href="magic.css">
    <style>
        /* Go Back Link Styling */
        .go-back-link{position:absolute;top:20px;left:30px;color:#fff;text-decoration:none;font-weight:bold;transition:0.3s;}
        .go-back-link:hover{color:#FBBF24;}
    </style>
</head>
<body>
    <canvas id="background-canvas"></canvas>


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
                    <label>Class Code:</label>
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

    <!-- Cursor Effect -->
<div id="cursor"></div>
<script>
const cursor=document.getElementById('cursor');document.addEventListener('mousemove',e=>{cursor.style.left=e.clientX+'px';cursor.style.top=e.clientY+'px';});
</script>

<script>
// ===== Particle Background =====
const canvas=document.getElementById('background-canvas');const ctx=canvas.getContext('2d');canvas.width=window.innerWidth;canvas.height=window.innerHeight;let particles=[];for(let i=0;i<250;i++){particles.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,r:Math.random()*2+1,dx:(Math.random()-0.5)*0.3,dy:(Math.random()-0.5)*0.3,color:`hsl(${Math.random()*360},80%,70%)`});}function animateParticles(){ctx.clearRect(0,0,canvas.width,canvas.height);for(let p of particles){ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color;ctx.fill();p.x+=p.dx;p.y+=p.dy;if(p.x<0||p.x>canvas.width)p.dx*=-1;if(p.y<0||p.y>canvas.height)p.dy*=-1;}requestAnimationFrame(animateParticles);}animateParticles();window.addEventListener('resize',()=>{canvas.width=window.innerWidth;canvas.height=window.innerHeight;});

// ===== Mouse Trail =====
document.body.addEventListener('mousemove',e=>{for(let i=0;i<3;i++){const t=document.createElement('div');t.style.position='fixed';t.style.width=t.style.height=Math.random()*6+3+'px';t.style.borderRadius='50%';t.style.background=`hsl(${Math.random()*360},80%,70%)`;t.style.left=e.clientX+'px';t.style.top=e.clientY+'px';t.style.pointerEvents='none';t.style.opacity=Math.random();t.style.transition='all 0.5s linear';document.body.appendChild(t);setTimeout(()=>t.remove(),500);}});
</script>

</body>
</html>

// ===== WORKING =====
