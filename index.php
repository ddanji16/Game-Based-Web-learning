<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/Admin-folder/database.php';

function homeE($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function homeTableExists($connection, $table) {
    $statement = mysqli_prepare($connection, 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    mysqli_stmt_bind_param($statement, 's', $table);
    mysqli_stmt_execute($statement);
    return mysqli_num_rows(mysqli_stmt_get_result($statement)) > 0;
}

$role = isset($_SESSION['usertype']) ? (int) $_SESSION['usertype'] : null;
$isLoggedIn = $role !== null;
$isAdmin = $role === 1;
$portalLink = $isAdmin ? 'Admin-folder/Dashboard.php' : 'Form-folder/login.php';
$courses = [];
$announcements = [];

// Contact form submission
if (isset($_POST['contact_submit'])) {
    $cName = trim($_POST['contact_name'] ?? '');
    $cEmail = trim($_POST['contact_email'] ?? '');
    $cSubject = trim($_POST['contact_subject'] ?? '');
    $cMessage = trim($_POST['contact_message'] ?? '');
    if ($cName !== '' && filter_var($cEmail, FILTER_VALIDATE_EMAIL) && $cMessage !== '') {
        mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $statement = mysqli_prepare($con, 'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'ssss', $cName, $cEmail, $cSubject, $cMessage);
        mysqli_stmt_execute($statement);
        $contactSent = true;
    } else {
        $contactError = 'Please fill in your name, a valid email, and a message.';
    }
}

if (homeTableExists($con, 'courses')) {
    $courseResult = mysqli_query($con, "SELECT course_code, title, description FROM courses WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
    if ($courseResult) { $courses = mysqli_fetch_all($courseResult, MYSQLI_ASSOC); }
}
if (homeTableExists($con, 'notifications')) {
    $audiences = $role === 0 ? "('all', 'students')" : ($role === 2 ? "('all', 'teachers')" : "('all')");
    $noticeResult = mysqli_query($con, "SELECT title, message, audience, created_at FROM notifications WHERE audience IN $audiences ORDER BY created_at DESC LIMIT 3");
    if ($noticeResult) { $announcements = mysqli_fetch_all($noticeResult, MYSQLI_ASSOC); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A modern learning management system for connected classrooms.">
    <title>Jidanao LMS | Learn. Grow. Achieve.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#home" aria-label="Jidanao LMS home">
            <span class="brand-mark"><i class="fa-solid fa-graduation-cap"></i></span>
            <span>Jidanao <strong>LMS</strong></span>
        </a>

        <button class="menu-toggle" aria-label="Open navigation" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav" aria-label="Primary navigation">
            <a class="active" href="#home">Home</a>
            <a href="#learning-modules">Modules</a>
            <a href="#courses">Courses</a>
            <a href="#announcements">Announcements</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <?php if ($role === 0): ?>
                <a class="nav-account" href="account.php"><i class="fa-solid fa-circle-user"></i> Account</a>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a class="nav-dashboard" href="Admin-folder/Dashboard.php"><i class="fa-solid fa-table-columns"></i> Admin dashboard</a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a class="nav-login" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            <?php else: ?>
                <a class="nav-login" href="Form-folder/login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Log in</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div>
            <div class="hero-content reveal">
                <p class="eyebrow"><i class="fa-solid fa-sparkles"></i> Learning made connected</p>
                <h1>One place to <span>learn, teach,</span> and grow.</h1>
                <p class="hero-copy">Jidanao LMS brings lessons, assignments, progress, and school communication together in one simple learning space.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?= homeE($portalLink) ?>"><?= $isAdmin ? 'Open dashboard' : ($isLoggedIn ? 'Explore modules' : 'Get started') ?> <i class="fa-solid fa-arrow-right"></i></a>
                    <a class="btn btn-light" href="#learning-modules"><i class="fa-solid fa-book-open"></i> Explore Activities</a>
                </div>  
                <div class="hero-proof">
                    <span><i class="fa-solid fa-check"></i> Easy to use</span>
                    <span><i class="fa-solid fa-check"></i> Built for school</span>
                    <span><i class="fa-solid fa-check"></i> Always connected</span>
                </div>
            </div>

            

            <div class="hero-visual reveal" aria-hidden="true">
                <div class="dashboard-card">
                    <div class="dash-top"><span class="mini-logo"><i class="fa-solid fa-graduation-cap"></i></span><span></span><span></span></div>
                    <div class="welcome-row"><div><small>Good morning, learner</small><b>Ready to achieve more?</b></div><i class="fa-solid fa-sun"></i></div>
                    <div class="progress-panel"><div class="panel-heading"><span>Learning progress</span><b>78%</b></div><div class="progress-track"><span></span></div><small>You are doing great this week!</small></div>
                    <div class="lesson-row"><span class="lesson-icon blue"><i class="fa-solid fa-book-open"></i></span><div><b>Today's lesson</b><small>Science · Living things</small></div><i class="fa-solid fa-arrow-right"></i></div>
                    <div class="lesson-row"><span class="lesson-icon orange"><i class="fa-solid fa-clipboard-check"></i></span><div><b>New assignment</b><small>Mathematics · Due Friday</small></div><i class="fa-solid fa-arrow-right"></i></div>
                </div>
                <div class="floating-card students"><span><i class="fa-solid fa-users"></i></span><div><b>Connected</b><small>Learning community</small></div></div>
                <div class="floating-card trophy"><i class="fa-solid fa-trophy"></i><div><b>Great job!</b><small>New achievement</small></div></div>
            </div>
        </section>

          <section class="section features" id="features">
            <div class="section-heading reveal"><p class="eyebrow blue-text">Everything in one platform</p><h2>Built for every part of the <span>learning journey.</span></h2><p>Simple tools that help students stay engaged, teachers stay organized, and families stay informed.</p></div>
            <div class="feature-grid">
                <article class="feature-card reveal"><span class="feature-icon indigo"><i class="fa-solid fa-book-open"></i></span><h3>Learning Modules</h3><p>Access organized lessons, reading materials, videos, and interactive activities at any time.</p><a href="Form-folder/login.php">Explore lessons <i class="fa-solid fa-arrow-right"></i></a></article>
                <article class="feature-card reveal"><span class="feature-icon teal"><i class="fa-solid fa-chart-line"></i></span><h3>Progress Tracking</h3><p>Follow learner performance and growth with clear, easy-to-understand progress records.</p><a href="Form-folder/login.php">View progress <i class="fa-solid fa-arrow-right"></i></a></article>
                <article class="feature-card reveal"><span class="feature-icon orange"><i class="fa-solid fa-clipboard-list"></i></span><h3>Smart Assignments</h3><p>Keep tasks, submissions, and deadlines in one place so nothing gets missed.</p><a href="Form-folder/login.php">See assignments <i class="fa-solid fa-arrow-right"></i></a></article>
                <article class="feature-card reveal"><span class="feature-icon pink"><i class="fa-solid fa-award"></i></span><h3>Achievements</h3><p>Celebrate effort and learning milestones with certificates and meaningful recognition.</p><a href="Form-folder/login.php">Discover achievements <i class="fa-solid fa-arrow-right"></i></a></article>
            </div>
        </section>

        <section class="stats-section" aria-label="Platform benefits">
            <div><strong>01</strong><span>Centralized learning<br>resources</span></div>
            <div><strong>02</strong><span>Clear progress<br>monitoring</span></div>
            <div><strong>03</strong><span>Better school<br>communication</span></div>
        </section>

        <section class="section learning-modules" id="learning-modules">
            <div class="section-heading reveal"><p class="eyebrow blue-text">LEARN ONE STEP AT A TIME</p><h2>Explore your <span>quarterly learning modules.</span></h2><p>Friendly, guided sample activities for elementary learners. Pick a quarter to see what is waiting for you.</p></div>
            <div class="quarter-tabs" role="tablist" aria-label="Learning quarters">
                <button class="quarter-tab active" data-quarter="q1" role="tab">1st Quarter</button><button class="quarter-tab" data-quarter="q2" role="tab">2nd Quarter</button><button class="quarter-tab" data-quarter="q3" role="tab">3rd Quarter</button><button class="quarter-tab" data-quarter="q4" role="tab">4th Quarter</button>
            </div>
            <div class="module-panels">
                <div class="module-panel active" id="q1"><article class="module-card math"><span><i class="fa-solid fa-calculator"></i></span><p>Mathematics</p><h3>Numbers Around Us</h3><small>Counting, place value, and simple addition</small><a href="<?= homeE($portalLink) ?>">Start activity <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card science"><span><i class="fa-solid fa-seedling"></i></span><p>Science</p><h3>Living Things</h3><small>Plants, animals, and their basic needs</small><a href="<?= homeE($portalLink) ?>">Open lesson <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card english"><span><i class="fa-solid fa-book"></i></span><p>English</p><h3>Reading Adventures</h3><small>Story characters, setting, and new words</small><a href="<?= homeE($portalLink) ?>">Read together <i class="fa-solid fa-arrow-right"></i></a></article></div>
                <div class="module-panel" id="q2"><article class="module-card math"><span><i class="fa-solid fa-shapes"></i></span><p>Mathematics</p><h3>Shapes and Patterns</h3><small>Recognize shapes and continue patterns</small><a href="<?= homeE($portalLink) ?>">Start activity <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card science"><span><i class="fa-solid fa-cloud-sun"></i></span><p>Science</p><h3>Weather Watch</h3><small>Observe weather and stay safe each day</small><a href="<?= homeE($portalLink) ?>">Open lesson <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card filipino"><span><i class="fa-solid fa-comments"></i></span><p>Filipino</p><h3>Wika at Kuwento</h3><small>Pagbasa, pakikinig, at pagsasalaysay</small><a href="<?= homeE($portalLink) ?>">Simulan <i class="fa-solid fa-arrow-right"></i></a></article></div>
                <div class="module-panel" id="q3"><article class="module-card math"><span><i class="fa-solid fa-ruler-combined"></i></span><p>Mathematics</p><h3>Measure and Compare</h3><small>Length, time, and everyday measurement</small><a href="<?= homeE($portalLink) ?>">Start activity <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card science"><span><i class="fa-solid fa-recycle"></i></span><p>Science</p><h3>Our Earth, Our Home</h3><small>Materials, waste, and caring for nature</small><a href="<?= homeE($portalLink) ?>">Open lesson <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card english"><span><i class="fa-solid fa-pen-nib"></i></span><p>English</p><h3>Writing My Ideas</h3><small>Build sentences and describe your world</small><a href="<?= homeE($portalLink) ?>">Write now <i class="fa-solid fa-arrow-right"></i></a></article></div>
                <div class="module-panel" id="q4"><article class="module-card math"><span><i class="fa-solid fa-coins"></i></span><p>Mathematics</p><h3>Money Matters</h3><small>Identify Philippine money and solve problems</small><a href="<?= homeE($portalLink) ?>">Start activity <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card science"><span><i class="fa-solid fa-sun"></i></span><p>Science</p><h3>Light and Sound</h3><small>Explore how light and sound help us</small><a href="<?= homeE($portalLink) ?>">Open lesson <i class="fa-solid fa-arrow-right"></i></a></article><article class="module-card filipino"><span><i class="fa-solid fa-star"></i></span><p>Values Education</p><h3>Being a Good Citizen</h3><small>Kindness, responsibility, and community</small><a href="<?= homeE($portalLink) ?>">Discover more <i class="fa-solid fa-arrow-right"></i></a></article></div>
            </div>
        </section>

        <section class="section live-content" id="courses">
            <div class="section-heading reveal"><p class="eyebrow blue-text">FROM YOUR SCHOOL</p><h2>Courses ready for <span>your next discovery.</span></h2><p>These courses are published by the school through the admin dashboard.</p></div>
            <div class="live-grid">
                <?php if ($courses): foreach ($courses as $course): ?><article class="live-course reveal"><span class="course-symbol"><i class="fa-solid fa-book-open"></i></span><p><?= homeE($course['course_code']) ?></p><h3><?= homeE($course['title']) ?></h3><small><?= homeE($course['description'] ?: 'A new learning space is ready for learners.') ?></small><a href="<?= homeE($portalLink) ?>">View course <i class="fa-solid fa-arrow-right"></i></a></article><?php endforeach; else: ?><article class="empty-state"><i class="fa-solid fa-book-open"></i><h3>New courses will appear here</h3><p>Ask your teacher or school administrator to publish a course.</p><?php if ($isAdmin): ?><a class="btn btn-primary" href="Admin-folder/Dashboard.php">Create a course</a><?php endif; ?></article><?php endif; ?>
            </div>
        </section>

        <section class="section announcements" id="announcements"><div class="section-heading reveal"><p class="eyebrow blue-text">STAY INFORMED</p><h2>School <span>announcements.</span></h2><p>Important updates shared by your school team.</p></div><div class="announcement-list"><?php if ($announcements): foreach ($announcements as $announcement): ?><article class="announcement reveal"><span><i class="fa-solid fa-bullhorn"></i></span><div><p class="announcement-audience">For <?= homeE($announcement['audience']) ?></p><h3><?= homeE($announcement['title']) ?></h3><p><?= homeE($announcement['message']) ?></p><small><?= homeE(date('F j, Y', strtotime($announcement['created_at']))) ?></small></div></article><?php endforeach; else: ?><article class="empty-state"><i class="fa-solid fa-bell"></i><h3>No announcements yet</h3><p>School updates will be shown here as soon as they are published.</p></article><?php endif; ?></div></section>

            
      

        <section class="section experience" id="about">
            <div class="experience-visual reveal"><div class="hex hex-main"><i class="fa-solid fa-graduation-cap"></i><b>Jidanao<br>LMS</b></div><div class="hex hex-a"><i class="fa-solid fa-book"></i></div><div class="hex hex-b"><i class="fa-solid fa-chart-column"></i></div><div class="hex hex-c"><i class="fa-solid fa-users"></i></div><div class="orbit orbit-a"></div><div class="orbit orbit-b"></div></div>
            <div class="experience-copy reveal"><p class="eyebrow blue-text">A better school experience</p><h2>Learning that feels <span>clear and inspiring.</span></h2><p>Our learning management system makes it easier to share knowledge, support every learner, and turn everyday schoolwork into meaningful progress.</p><ul><li><i class="fa-solid fa-circle-check"></i> One secure space for students and teachers</li><li><i class="fa-solid fa-circle-check"></i> Organized lessons, activities, and records</li><li><i class="fa-solid fa-circle-check"></i> Designed for accessible, engaging learning</li></ul><a class="text-link" href="Form-folder/login.php">Enter the learning portal <i class="fa-solid fa-arrow-right"></i></a></div>
        </section>



<section class="contact" id="contact">
            <div class="contact-copy reveal"><p class="eyebrow"><i class="fa-solid fa-message"></i> Let’s connect</p><h2>Have questions? We’re here to help.</h2><p>Get in touch with the Jidanao LMS team for help accessing the platform or learning more about our school community.</p>
            <?php if (!empty($contactSent)): ?><p class="contact-success"><i class="fa-solid fa-circle-check"></i> Thank you! Your message has been sent. We’ll get back to you soon.</p><?php endif; ?>
            <?php if (!empty($contactError)): ?><p class="contact-error"><i class="fa-solid fa-circle-exclamation"></i> <?= homeE($contactError) ?></p><?php endif; ?>
            <form method="post" class="contact-form">
                <div class="contact-row">
                    <input type="text" name="contact_name" placeholder="Your name" required value="<?= homeE($_POST['contact_name'] ?? '') ?>">
                    <input type="email" name="contact_email" placeholder="Your email" required value="<?= homeE($_POST['contact_email'] ?? '') ?>">
                </div>
                <input type="text" name="contact_subject" placeholder="Subject (optional)" value="<?= homeE($_POST['contact_subject'] ?? '') ?>">
                <textarea name="contact_message" placeholder="Write your message..." required><?= homeE($_POST['contact_message'] ?? '') ?></textarea>
                <button type="submit" name="contact_submit" class="btn btn-light">Send message <i class="fa-solid fa-paper-plane"></i></button>
            </form>
            <div class="contact-details"><a href="mailto:info@jidanaolms.edu.ph"><i class="fa-solid fa-envelope"></i><span><small>Email us</small>info@jidanaolms.edu.ph</span></a><a href="tel:+630000000000"><i class="fa-solid fa-phone"></i><span><small>Call us</small>School office</span></a></div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-top"><a class="brand" href="#home"><span class="brand-mark"><i class="fa-solid fa-graduation-cap"></i></span><span>Jidanao <strong>LMS</strong></span></a><p>Empowering learners, supporting teachers, and strengthening our school community.</p><div class="social-links"><a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a><a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a><a href="#contact" aria-label="Email"><i class="fa-solid fa-envelope"></i></a></div></div>
        <div class="footer-bottom"><span>© <span id="year"></span> Jidanao Learning Management System.</span><span>Learn today. Lead tomorrow.</span></div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
