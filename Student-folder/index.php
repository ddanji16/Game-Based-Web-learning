<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../Admin-folder/database.php';

// Security: Ensure only students can access
if (!isset($_SESSION['user_id']) || (int)$_SESSION['usertype'] !== 0) {
    header('Location: ../Form-folder/login.php'); exit;
}

$studentId = $_SESSION['user_id'];

// 1. Fetch Student's Enrolled Courses
$enrolledQuery = "SELECT c.*, u.Firstname as teacher_name 
                  FROM courses c 
                  JOIN enrollments e ON c.id = e.course_id 
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE e.student_id = $studentId AND c.status = 'active'";
$enrolledCourses = mysqli_query($con, $enrolledQuery);

// 2. Fetch "Discover" Courses (Not enrolled yet)
$discoverQuery = "SELECT * FROM courses 
                  WHERE id NOT IN (SELECT course_id FROM enrollments WHERE student_id = $studentId) 
                  AND status = 'active' LIMIT 4";
$discoverCourses = mysqli_query($con, $discoverQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Learning Journey | Jidanao LMS</title>
    <link rel="stylesheet" href="../Admin-folder/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --accent: #4f46e5; --bg-light: #f8fafc; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .course-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; transition: 0.3s; position: relative; overflow: hidden; }
        .course-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .course-card h3 { margin: 10px 0; font-size: 1.2rem; color: #1e293b; }
        .progress-container { height: 8px; background: #f1f5f9; border-radius: 10px; margin: 15px 0; }
        .progress-bar { height: 100%; background: var(--accent); border-radius: 10px; width: 0%; transition: 1s; }
        .btn-play { background: var(--accent); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        
        #learningModal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); z-index: 1000; backdrop-filter: blur(8px); align-items: center; justify-content: center; }
        .modal-body { background: white; width: min(95%, 800px); height: 80vh; border-radius: 24px; position: relative; display: flex; flex-direction: column; overflow: hidden; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .interactive-area { flex: 1; padding: 40px; overflow-y: auto; text-align: center; }
        .speech-bubble { background: #f1f5f9; padding: 20px; border-radius: 20px; margin-bottom: 15px; font-size: 1.3rem; line-height: 1.5; text-align: left; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body style="background: var(--bg-light);">

    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="#"><span class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a>
            <nav>
                <a class="active" href="#"><i class="fa-solid fa-house"></i> Home</a>
                <a href="../my_achievements.php"><i class="fa-solid fa-trophy"></i> My Achievements</a>
               
            </nav>
            <a class="logout" href="../logout.php"><i class="fa-solid fa-power-off"></i> Sign Out</a>
        </aside>

        <main>
            <header>
                <div>
                    <h1>Welcome back, <?= htmlspecialchars($_SESSION['firstname'] ?? 'Learner') ?>! 👋</h1>
                    <p class="subtle">Ready to continue your learning adventure?</p>
                </div>
            </header>

            <section>
                <div class="panel-title"><h2><i class="fa-solid fa-book-bookmark" style="color:var(--accent)"></i> My Courses</h2></div>
                <div class="course-grid">
                    <?php if (mysqli_num_rows($enrolledCourses) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($enrolledCourses)): ?>
                        <article class="course-card">
                            <small class="eyebrow"><?= htmlspecialchars($row['course_code']) ?></small>
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="subtle" style="font-size: 0.9rem;"><?= htmlspecialchars($row['description']) ?></p>
                            
                            <div class="progress-container">
                                <div class="progress-bar" style="width: 0%;"></div>
                            </div>
                            
                            <button class="btn-play" onclick="startCourse(<?= $row['id'] ?>)">
                                Continue Learning <i class="fa-solid fa-circle-play"></i>
                            </button>
                        </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>You haven't enrolled in any courses yet. Discover some below!</p>
                    <?php endif; ?>
                </div>
            </section>

            <section style="margin-top: 40px;">
                <div class="panel-title"><h2><i class="fa-solid fa-compass" style="color:var(--orange)"></i> Discover New Subjects</h2></div>
                <div class="course-grid">
                    <?php if (mysqli_num_rows($discoverCourses) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($discoverCourses)): ?>
                        <article class="course-card" style="border-style: dashed;">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="subtle"><?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...</p>
                            <form method="POST" action="enroll.php">
                                <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
                                <button class="btn-play" style="background: #64748b;">Enroll Now</button>
                            </form>
                        </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No new courses available at the moment.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="learningModal">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="modalTitle">Loading Lesson...</h2>
                <button onclick="closeModal()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div id="interactiveArea" class="interactive-area"></div>
            <div style="padding: 20px; border-top: 1px solid #eee;">
                <button id="nextBtn" class="btn-play">Next Step <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <script>
    function startCourse(courseId) {
        document.getElementById('learningModal').style.display = 'flex';
        document.getElementById('interactiveArea').innerHTML = '<div class="loader">✨ Preparing your adventure...</div>';

        fetch(`get_modules.php?course_id=${courseId}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    renderModule(data.module);
                } else {
                    document.getElementById('interactiveArea').innerHTML = '<h2>No modules found for this course yet!</h2>';
                    document.getElementById('nextBtn').style.display = 'none';
                }
            });
    }

    function renderModule(module) {
        document.getElementById('modalTitle').innerText = module.title;
        const area = document.getElementById('interactiveArea');
        area.innerHTML = '';
        document.getElementById('nextBtn').style.display = 'flex';

        if(module.content_type === 'story') {
            const lines = module.story_script.split('\n');
            let currentLine = 0;
            
            function showLine() {
                if(currentLine < lines.length) {
                    const div = document.createElement('div');
                    div.className = 'speech-bubble';
                    div.innerText = lines[currentLine];
                    area.appendChild(div);
                    area.scrollTop = area.scrollHeight;
                    currentLine++;
                } else {
                    area.innerHTML += '<h2 style="color: #159a75">Story Complete! +10 XP</h2>';
                    document.getElementById('nextBtn').innerText = "Finish & Save Progress";
                    document.getElementById('nextBtn').onclick = () => saveProgress(module.course_id, module.id);
                }
            }
            
            document.getElementById('nextBtn').onclick = showLine;
            showLine();
        } else if (module.content_type === 'text') {
            area.innerHTML = `<div class="speech-bubble">${module.story_script || 'Lesson content goes here.'}</div>`;
            document.getElementById('nextBtn').innerText = "Complete Lesson";
            document.getElementById('nextBtn').onclick = () => saveProgress(module.course_id, module.id);
        }
    }

    function saveProgress(courseId, moduleId) {
        fetch('../save_progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `activity_id=${courseId}_${moduleId}&status=completed&score=100`
        })
        .then(() => {
            alert("Progress Saved! You're doing great!");
            location.reload();
        });
    }

    function closeModal() { document.getElementById('learningModal').style.display = 'none'; }
    </script>
</body>
</html>
