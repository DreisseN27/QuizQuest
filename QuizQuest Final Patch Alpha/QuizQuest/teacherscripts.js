// ===== Canvas Background =====
const canvas = document.getElementById('background-canvas');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

let particles = [];
for (let i = 0; i < 250; i++) {
    particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        r: Math.random() * 2 + 1,
        dx: (Math.random() - 0.5) * 0.3,
        dy: (Math.random() - 0.5) * 0.3,
        color: `hsl(${Math.random() * 360}, 80%, 70%)`
    });
}

function animateParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (let p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
        p.x += p.dx;
        p.y += p.dy;
        if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
    }
    requestAnimationFrame(animateParticles);
}
animateParticles();

window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
});


// ===== Card Hover Sparkles =====
document.querySelectorAll('.subject-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        for (let i = 0; i < 2; i++) {
            const sparkle = document.createElement('div');
            sparkle.style.position = 'absolute';
            sparkle.style.width = sparkle.style.height = Math.random() * 4 + 2 + 'px';
            sparkle.style.borderRadius = '50%';
            sparkle.style.background = `hsl(${Math.random() * 360},100%,80%)`;
            sparkle.style.top = e.offsetY + 'px';
            sparkle.style.left = e.offsetX + 'px';
            sparkle.style.pointerEvents = 'none';
            sparkle.style.opacity = Math.random();
            sparkle.style.transition = 'all 0.8s linear';
            card.appendChild(sparkle);
            setTimeout(() => sparkle.remove(), 800);
        }
    });
});

// ===== Click Particle Burst =====
document.querySelectorAll('.subject-card').forEach(card => {
    card.addEventListener('click', e => {
        for (let i = 0; i < 15; i++) {
            const burst = document.createElement('div');
            burst.style.position = 'absolute';
            burst.style.width = burst.style.height = Math.random() * 6 + 4 + 'px';
            burst.style.borderRadius = '50%';
            burst.style.background = `hsl(${Math.random() * 360},90%,70%)`;
            burst.style.top = e.offsetY + 'px';
            burst.style.left = e.offsetX + 'px';
            burst.style.pointerEvents = 'none';
            burst.style.opacity = 1;
            burst.style.transition = 'all 0.8s ease-out';
            card.appendChild(burst);
            setTimeout(() => {
                burst.style.transform = `translate(${(Math.random() - 0.5) * 100}px, ${(Math.random() - 0.5) * 100}px)`;
                burst.style.opacity = 0;
            }, 10);
            setTimeout(() => burst.remove(), 800);
        }
    });
});

// ===== Floating Card Particles =====
document.querySelectorAll('.subject-card').forEach(card => {
    setInterval(() => {
        const particle = document.createElement('div');
        particle.style.position = 'absolute';
        particle.style.width = particle.style.height = Math.random() * 3 + 2 + 'px';
        particle.style.borderRadius = '50%';
        particle.style.background = `hsl(${Math.random() * 360},90%,70%)`;
        particle.style.top = Math.random() * card.offsetHeight + 'px';
        particle.style.left = Math.random() * card.offsetWidth + 'px';
        particle.style.opacity = 0.3;
        particle.style.pointerEvents = 'none';
        particle.style.transition = 'all 2s linear';
        card.appendChild(particle);
        setTimeout(() => particle.remove(), 2000);
    }, 400);
});

// ===== Mouse Trail =====
document.body.addEventListener('mousemove', e => {
    for (let i = 0; i < 3; i++) {
        const trail = document.createElement('div');
        trail.style.position = 'fixed';
        trail.style.width = trail.style.height = Math.random() * 6 + 3 + 'px';
        trail.style.borderRadius = '50%';
        trail.style.background = `hsl(${Math.random() * 360},80%,70%)`;
        trail.style.left = e.clientX + 'px';
        trail.style.top = e.clientY + 'px';
        trail.style.pointerEvents = 'none';
        trail.style.opacity = Math.random();
        trail.style.transition = 'all 0.5s linear';
        document.body.appendChild(trail);
        setTimeout(() => trail.remove(), 500);
    }
});

// ===== Sidebar Tabs Switching =====
document.querySelectorAll(".sidebar-tab").forEach(tab => {
    tab.addEventListener("click", () => {
        document.querySelectorAll(".sidebar-tab").forEach(t => t.classList.remove("active"));
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));

        tab.classList.add("active");
        const tabId = tab.dataset.tab;
        document.getElementById(tabId).classList.add("active");

        // Load content dynamically
        if(tabId === "viewTab") loadQuizzes();
        if(tabId === "updateTab") loadUpdateQuizzes();
        if(tabId === "createTab") buildEditor();
    });
});

// Optional: auto-open tab via URL params
const params = new URLSearchParams(window.location.search);
const tabParam = params.get('tab');
const quizIdParam = params.get('quiz_id');
if(tabParam && document.querySelector(`.sidebar-tab[data-tab="${tabParam}"]`)) {
    document.querySelector(`.sidebar-tab[data-tab="${tabParam}"]`).click();
    if(tabParam === "updateTab" && quizIdParam) {
        setTimeout(() => {
            document.getElementById('updateQuizSelect').value = quizIdParam;
            loadQuizForUpdate(quizIdParam);
        }, 200);
    }
}
