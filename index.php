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
    <link rel="stylesheet" href="style.css">
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
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a class="nav-login" href="Form-folder/login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> logout</a>
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
                    <a class="btn btn-primary" href="Form-folder/login.php">Get started <i class="fa-solid fa-arrow-right"></i></a>
                    <a class="btn btn-light" href="#features"><i class="fa-solid fa-circle-play"></i> Explore features</a>
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

        <section class="stats-section" aria-label="Platform benefits">
            <div><strong>01</strong><span>Centralized learning<br>resources</span></div>
            <div><strong>02</strong><span>Clear progress<br>monitoring</span></div>
            <div><strong>03</strong><span>Better school<br>communication</span></div>
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

        <section class="section experience" id="about">
            <div class="experience-visual reveal"><div class="hex hex-main"><i class="fa-solid fa-graduation-cap"></i><b>Jidanao<br>LMS</b></div><div class="hex hex-a"><i class="fa-solid fa-book"></i></div><div class="hex hex-b"><i class="fa-solid fa-chart-column"></i></div><div class="hex hex-c"><i class="fa-solid fa-users"></i></div><div class="orbit orbit-a"></div><div class="orbit orbit-b"></div></div>
            <div class="experience-copy reveal"><p class="eyebrow blue-text">A better school experience</p><h2>Learning that feels <span>clear and inspiring.</span></h2><p>Our learning management system makes it easier to share knowledge, support every learner, and turn everyday schoolwork into meaningful progress.</p><ul><li><i class="fa-solid fa-circle-check"></i> One secure space for students and teachers</li><li><i class="fa-solid fa-circle-check"></i> Organized lessons, activities, and records</li><li><i class="fa-solid fa-circle-check"></i> Designed for accessible, engaging learning</li></ul><a class="text-link" href="Form-folder/login.php">Enter the learning portal <i class="fa-solid fa-arrow-right"></i></a></div>
        </section>

        <section class="contact" id="contact">
            <div class="contact-copy reveal"><p class="eyebrow"><i class="fa-solid fa-message"></i> Let’s connect</p><h2>Have questions? We’re here to help.</h2><p>Get in touch with the Jidanao LMS team for help accessing the platform or learning more about our school community.</p><a href="mailto:info@jidanaolms.edu.ph" class="btn btn-light">Contact us <i class="fa-solid fa-envelope"></i></a></div>
            <div class="contact-details reveal"><a href="mailto:info@jidanaolms.edu.ph"><i class="fa-solid fa-envelope"></i><span><small>Email us</small>info@jidanaolms.edu.ph</span></a><a href="tel:+630000000000"><i class="fa-solid fa-phone"></i><span><small>Call us</small>School office</span></a></div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-top"><a class="brand" href="#home"><span class="brand-mark"><i class="fa-solid fa-graduation-cap"></i></span><span>Jidanao <strong>LMS</strong></span></a><p>Empowering learners, supporting teachers, and strengthening our school community.</p><div class="social-links"><a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a><a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a><a href="#contact" aria-label="Email"><i class="fa-solid fa-envelope"></i></a></div></div>
        <div class="footer-bottom"><span>© <span id="year"></span> Jidanao Learning Management System.</span><span>Learn today. Lead tomorrow.</span></div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
