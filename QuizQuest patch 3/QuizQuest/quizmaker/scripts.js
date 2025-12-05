// ---------------------------
// TAB SWITCHING
// ---------------------------
document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener("click", () => {
        document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));

        tab.classList.add("active");
        document.getElementById(tab.dataset.tab).classList.add("active");

        if (tab.dataset.tab === "viewTab") loadQuizzes();
        if (tab.dataset.tab === "updateTab") loadUpdateQuizzes();
    });
});

// ---------------------------
// CREATE QUIZ TAB
// ---------------------------
let currentType = "multiple";
let questions = [];

const editor = document.getElementById("editor");
const preview = document.getElementById("preview");
const addQuestionBtn = document.getElementById("addQuestionBtn");
const submitQuizBtn = document.getElementById("submitQuizBtn");
const quizCodeInput = document.getElementById("quizCodeInput");
const quizTitleInput = document.getElementById("quizTitleInput");

// Load default editor
buildEditor("multiple");
document.querySelector('.type-buttons button[data-type="multiple"]').classList.add('active-type');

document.querySelectorAll(".type-buttons button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".type-buttons button").forEach(b => b.classList.remove("active-type"));
        btn.classList.add("active-type");
        currentType = btn.dataset.type;
        buildEditor(currentType);
    });
});

function buildEditor(type) {
    editor.innerHTML = "";
    const cont = document.createElement("div");

    const typeLabel = document.createElement("div");
    typeLabel.style.fontWeight = "bold";
    typeLabel.style.marginBottom = "8px";
    typeLabel.style.fontSize = "16px";
    typeLabel.textContent = `Enter your Question`;
    cont.appendChild(typeLabel);

    const q = document.createElement("textarea");
    q.id = "questionText";
    q.rows = 3;
    q.style.width = "100%";
    q.style.marginBottom = "10px";
    q.placeholder = "Enter the question text here...";
    cont.appendChild(q);

    if (type === "multiple") {
        const w = document.createElement("div");
        w.id = "choicesDiv";
        w.style.marginTop = "10px";

        for (let i = 0; i < 4; i++) {
            const lbl = document.createElement("label");
            lbl.textContent = "Choice " + String.fromCharCode(65 + i) + ": ";
            const inp = document.createElement("input");
            inp.type = "text";
            inp.className = "choiceInput";

            w.appendChild(lbl);
            w.appendChild(inp);
            w.appendChild(document.createElement("br"));
        }

        const correct = document.createElement("select");
        correct.id = "correctAnswer";
        correct.style.marginTop = "10px";
        ["A","B","C","D"].forEach(c => {
            const opt = document.createElement("option");
            opt.value = c;
            opt.textContent = c;
            correct.appendChild(opt);
        });
        w.appendChild(document.createElement("br"));
        w.appendChild(document.createTextNode("Correct Answer: "));
        w.appendChild(correct);

        cont.appendChild(w);
    } else {
        const label = document.createElement("div");
        label.style.fontWeight = "bold";
        label.style.marginBottom = "5px";
        label.textContent = type === "identification" ? "Enter the answer below:" : "Select True or False:";
        cont.appendChild(label);

        const ans = document.createElement(type === "identification" ? "input" : "select");
        ans.id = "correctAnswer";
        ans.style.marginBottom = "10px";
        ans.style.width = "100%";
        ans.style.padding = "8px";
        ans.style.borderRadius = "6px";
        ans.style.border = "1px solid #ccc";

        if (type === "truefalse") {
            ["True","False"].forEach(v => {
                const opt = document.createElement("option");
                opt.value = v;
                opt.textContent = v;
                ans.appendChild(opt);
            });
        } else {
            ans.placeholder = "Correct answer here";
        }

        cont.appendChild(ans);
    }

    editor.appendChild(cont);
}

function readEditorToQuestion() {
    const text = document.getElementById("questionText").value.trim();
    const correct = document.getElementById("correctAnswer").value;
    if (text === "") { alert("Enter a question text"); return null; }

    let choices = null;
    if (currentType === "multiple") {
        choices = [];
        let allFilled = true;
        document.querySelectorAll(".choiceInput").forEach(c => {
            if (c.value.trim() === "") allFilled = false;
            choices.push(c.value.trim());
        });
        if (!allFilled) { alert("All multiple choice fields must be filled"); return null; }
    }

    return { type: currentType, text, correct, choices };
}

addQuestionBtn.addEventListener("click", () => {
    const q = readEditorToQuestion();
    if (!q) return;
    questions.push(q);
    renderQuestions();
    buildEditor(currentType);
});

function renderQuestions() {
    preview.innerHTML = "";
    const titleDiv = document.createElement("div");
    titleDiv.innerHTML = `<h3>${quizTitleInput.value || "Untitled Quiz"}</h3><b>Quiz Code:</b> ${quizCodeInput.value}`;
    preview.appendChild(titleDiv);

    questions.forEach((q, index) => {
        const div = document.createElement("div");
        div.className = "question-item";
        div.draggable = true;
        div.dataset.index = index;
        div.innerHTML = `<b>${index+1}.</b> ${q.text}<br><i>Type:</i> ${q.type}<br>`;
        if (q.type === "multiple") q.choices.forEach((c,i) => { div.innerHTML += `${String.fromCharCode(65+i)}. ${c}<br>`; });
        div.innerHTML += `<b>Correct:</b> ${q.correct}<br>`;

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove";
        removeBtn.onclick = () => { questions.splice(index,1); renderQuestions(); };
        div.appendChild(removeBtn);

        // Drag & Drop
        div.addEventListener("dragstart", e => { e.dataTransfer.setData("text/plain", index); });
        div.addEventListener("dragover", e => e.preventDefault());
        div.addEventListener("drop", e => {
            const fromIndex = e.dataTransfer.getData("text");
            const toIndex = index;
            const moved = questions.splice(fromIndex,1)[0];
            questions.splice(toIndex,0,moved);
            renderQuestions();
        });

        preview.appendChild(div);
    });
}

submitQuizBtn.addEventListener("click", async () => {
    const class_code = quizCodeInput.value.trim();
    const title = quizTitleInput.value.trim();
    if (!class_code || !title) { alert("Enter quiz code and title"); return; }
    if (questions.length === 0) { alert("Add at least one question"); return; }

    try {
        const res = await fetch('submit_quiz.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({class_code,title,questions})
        });
        const data = await res.json();

        if (data.success) {
            questions = [];
            renderQuestions();
            quizCodeInput.value = '';
            quizTitleInput.value = '';
            buildEditor(currentType);

            const next = confirm("Quiz saved! Do you want to create another quiz?");
            if (!next) {
                document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
                document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));

                const viewTab = document.querySelector('.tab[data-tab="viewTab"]');
                const viewContent = document.getElementById('viewTab');
                if (viewTab && viewContent) {
                    viewTab.classList.add('active');
                    viewContent.classList.add('active');
                }

                loadQuizzes();
            }
        } else {
            alert("Error: "+(data.error||"unknown"));
        }
    } catch(err) { 
        alert("Network error: "+err.message); 
    }
});

// ---------------------------
// VIEW / DELETE QUIZZES
// ---------------------------
async function loadQuizzes(){
    const listDiv = document.getElementById("quizList");
    const previewDiv = document.getElementById("quizPreview");
    listDiv.innerHTML = "Loading...";
    previewDiv.innerHTML = "Select a quiz to view questions...";
    try {
        const res = await fetch("quizzes.php?action=view");
        const data = await res.json();
        listDiv.innerHTML = "";
        if(data.length === 0){ listDiv.innerHTML = "No quizzes found."; return; }

        data.forEach(q => {
            const div = document.createElement("div");
            div.className = "quiz-item";
            div.style.display = "flex";
            div.style.justifyContent = "space-between";
            div.style.alignItems = "center";
            div.style.padding = "10px";
            div.style.border = "1px solid #ccc";
            div.style.marginBottom = "5px";

            const titleSpan = document.createElement("span");
            titleSpan.className = "quiz-title";
            titleSpan.textContent = `${q.title} (${q.class_code})`;
            div.appendChild(titleSpan);

            const actionsDiv = document.createElement("div");

            const viewBtn = document.createElement("button");
            viewBtn.textContent = "View";
            viewBtn.style.marginRight = "5px";
            viewBtn.onclick = async () => {
                const res2 = await fetch(`quizzes.php?action=details&quiz_id=${q.id}`);
                const details = await res2.json();
                previewDiv.innerHTML = "";
                details.questions.forEach((ques,index)=>{
                    const qDiv=document.createElement("div");
                    qDiv.className="question-item";
                    qDiv.innerHTML=`<b>${index+1}.</b> ${ques.question_text} <i>(${ques.question_type})</i><br>`;
                    if(ques.question_type==="multiple"){ ques.choices.forEach(c=>{ qDiv.innerHTML+=`${c.choice_label}. ${c.choice_text}<br>`; }); }
                    qDiv.innerHTML+=`<b>Answer:</b> ${ques.correct_answer}<br>`;
                    previewDiv.appendChild(qDiv);
                });
            };
            actionsDiv.appendChild(viewBtn);

            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = "Delete";
            deleteBtn.onclick = () => deleteQuiz(q.id);
            actionsDiv.appendChild(deleteBtn);

            div.appendChild(actionsDiv);
            listDiv.appendChild(div);
        });
    } catch(err){ listDiv.innerHTML = "Error loading quizzes: "+err.message; }
}

async function deleteQuiz(id){
    if(!confirm("Are you sure you want to delete this quiz?")) return;
    try{
        const form = new FormData();
        form.append("action","delete");
        form.append("id",id);
        const res=await fetch("quizzes.php",{method:"POST", body:form});
        const data=await res.json();
        if(data.success) loadQuizzes();
        else alert("Error: "+(data.error||"unknown"));
    } catch(err){ alert("Error: "+err.message); }
}

// ---------------------------
// UPDATE QUIZZES
// ---------------------------
let updateQuestions = [];
let deletedQuestions = [];

function renderUpdateQuestions(){
    const container = document.getElementById("updateQuestionsContainer");
    container.innerHTML = "";
    updateQuestions.forEach((q,index)=>{
        const div = document.createElement("div");
        div.className = "question-item";
        div.draggable = true;
        div.dataset.index = index;

        div.innerHTML = `<b>Q${index+1} (${q.type}):</b><br>
            <textarea style="width:100%" onchange="updateQuestions[${index}].text=this.value">${q.text}</textarea><br>`;

        if(q.type==="multiple") q.choices.forEach((c,i)=>{
            const input = document.createElement("input");
            input.type = "text";
            input.value = c;
            input.style.width = "80%";
            input.onchange = (e)=> updateQuestions[index].choices[i] = e.target.value;
            div.appendChild(document.createTextNode(`Choice ${String.fromCharCode(65+i)}:`));
            div.appendChild(input);
            div.appendChild(document.createElement("br"));
        });

        const correctInput = document.createElement("input");
        correctInput.type = "text";
        correctInput.value = q.correct;
        correctInput.placeholder = "Correct Answer";
        correctInput.onchange = (e)=> updateQuestions[index].correct = e.target.value;
        div.appendChild(document.createTextNode("Correct Answer: "));
        div.appendChild(correctInput);

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove Question";
        removeBtn.style.marginTop = "5px";
        removeBtn.onclick = ()=>{ if(q.id) deletedQuestions.push(q.id); updateQuestions.splice(index,1); renderUpdateQuestions(); renderUpdatePreview(); };
        div.appendChild(document.createElement("br"));
        div.appendChild(removeBtn);

        // Drag & Drop
        div.addEventListener("dragstart", e => { e.dataTransfer.setData("text/plain", index); });
        div.addEventListener("dragover", e => e.preventDefault());
        div.addEventListener("drop", e => {
            const fromIndex = e.dataTransfer.getData("text");
            const toIndex = index;
            const moved = updateQuestions.splice(fromIndex,1)[0];
            updateQuestions.splice(toIndex,0,moved);
            renderUpdateQuestions();
        });

        container.appendChild(div);
    });
    renderUpdatePreview();
}

function renderUpdatePreview(){
    const previewDiv = document.getElementById("updatePreview");
    previewDiv.innerHTML = "";
    updateQuestions.forEach((q,index)=>{
        const div = document.createElement("div");
        div.className = "question-item";
        div.innerHTML = `<b>${index+1} (${q.type}):</b> ${q.text}<br>`;
        if(q.type==="multiple") q.choices.forEach((c,i)=>{ div.innerHTML += `${String.fromCharCode(65+i)}. ${c}<br>`; });
        div.innerHTML += `<b>Correct:</b> ${q.correct}<br>`;
        previewDiv.appendChild(div);
    });
}

async function loadUpdateQuizzes(){
    const select = document.getElementById("updateQuizSelect");
    const previewDiv = document.getElementById("updatePreview");
    select.innerHTML = "<option>Loading...</option>";
    previewDiv.innerHTML = "Select a quiz to see questions here...";

    try{
        const res = await fetch("quizzes.php?action=view");
        const quizzes = await res.json();
        select.innerHTML = "";
        quizzes.forEach(q=>{
            const opt = document.createElement("option");
            opt.value = q.id;
            opt.textContent = `${q.title} (${q.class_code})`;
            select.appendChild(opt);
        });
        if(quizzes.length>0) loadQuizForUpdate(quizzes[0].id);
    } catch(err){ select.innerHTML="<option>Error loading quizzes</option>"; }

    select.onchange = ()=> loadQuizForUpdate(select.value);
}

async function loadQuizForUpdate(quiz_id){
    const container = document.getElementById("updateContent");
    const previewDiv = document.getElementById("updatePreview");
    container.innerHTML="Loading quiz...";
    previewDiv.innerHTML="";

    try{
        const res = await fetch(`quizzes.php?action=details&quiz_id=${quiz_id}`);
        const data = await res.json();
        const quiz = data.quiz;
        const questionsData = data.questions;

        container.innerHTML = `<label>Quiz Title:</label>
    <input type="text" id="updateQuizTitle" value="${quiz.title}" style="width: 52%; box-sizing: border-box;"><br><br>
    <div id="updateQuestionsContainer"></div>
    <button id="updateSubmitBtn">Save Changes</button>`;

// Make the select match the width
const select = document.getElementById("updateQuizSelect");
select.style.width = "50%";
select.style.boxSizing = "border-box";


        updateQuestions = questionsData.map(q=>({
            id:q.id,
            type:q.question_type,
            text:q.question_text,
            correct:q.correct_answer,
            choices:q.choices? q.choices.map(c=>c.choice_text):[]
        }));
        deletedQuestions = [];
        renderUpdateQuestions();
        document.getElementById("updateSubmitBtn").onclick = saveUpdatedQuiz;
    } catch(err){ container.innerHTML="Error loading quiz: "+err.message; }
}

async function saveUpdatedQuiz(){
    const quiz_id = document.getElementById("updateQuizSelect").value;
    const title = document.getElementById("updateQuizTitle").value;
    const payload = { action:"update", quiz_id, title, questions:updateQuestions, deletedQuestions };

    try{
        const res = await fetch("quizzes.php",{
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success){
            alert("Quiz updated successfully!");
            deletedQuestions=[];
        } else alert("Error: "+(data.error||"Unknown error"));
    } catch(err){ alert("Network error: "+err.message); }
}

// Auto-switch tab if URL has ?tab=update&quiz_id=...
const params = new URLSearchParams(window.location.search);
const tabParam = params.get('tab');
const quizIdParam = params.get('quiz_id');

if(tabParam === 'update' && quizIdParam) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));

    const updateTab = document.querySelector('.tab[data-tab="updateTab"]');
    const updateContent = document.getElementById('updateTab');

    if(updateTab && updateContent) {
        updateTab.classList.add('active');
        updateContent.classList.add('active');
        loadUpdateQuizzes();
        setTimeout(() => {
            document.getElementById('updateQuizSelect').value = quizIdParam;
            loadQuizForUpdate(quizIdParam);
        }, 200);
    }
}
