<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/Admin-folder/database.php';

function homeE($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function homeTableExists($connection, $table)
{
    $statement = mysqli_prepare(
        $connection,
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
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

if (isset($_POST['contact_submit'])) {
    $cName = trim($_POST['contact_name'] ?? '');
    $cEmail = trim($_POST['contact_email'] ?? '');
    $cSubject = trim($_POST['contact_subject'] ?? '');
    $cMessage = trim($_POST['contact_message'] ?? '');

    if ($cName !== '' && filter_var($cEmail, FILTER_VALIDATE_EMAIL) && $cMessage !== '') {
        mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_messages ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL, subject VARCHAR(200) NOT NULL DEFAULT '', message TEXT NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
    if ($courseResult) {
        $courses = mysqli_fetch_all($courseResult, MYSQLI_ASSOC);
    }
}

if (homeTableExists($con, 'notifications')) {
    $audiences = $role === 0 ? "('all', 'students')" : ($role === 2 ? "('all', 'teachers')" : "('all')");
    $noticeResult = mysqli_query($con, "SELECT title, message, audience, created_at FROM notifications WHERE audience IN $audiences ORDER BY created_at DESC LIMIT 3");
    if ($noticeResult) {
        $announcements = mysqli_fetch_all($noticeResult, MYSQLI_ASSOC);
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="A modern learning management system for connected classrooms."
    />
    <title>Jidanao LMS | Learn. Grow. Achieve.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />
    <link
      rel="stylesheet"
      href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>"
    />
  </head>
  <body>
    <header class="site-header">
      <a class="brand" href="#home" aria-label="Jidanao LMS home">
        <span class="brand-mark"
          ><i class="fa-solid fa-graduation-cap"></i
        ></span>
        <span>Jidanao <strong>LMS</strong></span>
      </a>

      <button
        class="menu-toggle"
        aria-label="Open navigation"
        aria-expanded="false"
      >
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
        <a class="nav-account" href="account.php"
          ><i class="fa-solid fa-circle-user"></i> Account</a
        >
        <?php endif; ?> <?php if ($isAdmin): ?>
        <a class="nav-dashboard" href="Admin-folder/Dashboard.php"
          ><i class="fa-solid fa-table-columns"></i> Admin dashboard</a
        >
        <?php endif; ?> <?php if ($isLoggedIn): ?>
        <a class="nav-login" href="logout.php"
          ><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a
        >
        <?php else: ?>
        <a class="nav-login" href="Form-folder/login.php"
          ><i class="fa-solid fa-arrow-right-to-bracket"></i> Log in</a
        >
        <?php endif; ?>
      </nav>
    </header>

    <main>
      <section class="hero" id="home">
        <div class="hero-orb orb-one"></div>
        <div class="hero-orb orb-two"></div>
        <div class="hero-content reveal">
          <p class="eyebrow">
            <i class="fa-solid fa-sparkles"></i> Learning made connected
          </p>
          <h1>One place to <span>learn, teach,</span> and grow.</h1>
          <p class="hero-copy">
            Jidanao LMS brings lessons, assignments, progress, and school
            communication together in one simple learning space.
          </p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="<?= homeE($portalLink) ?>"
              ><?= $isAdmin ? 'Open dashboard' : ($isLoggedIn ? 'Explore modules' : 'Get started') ?>
              <i class="fa-solid fa-arrow-right"></i
            ></a>
            <a class="btn btn-light" href="#learning-modules"
              ><i class="fa-solid fa-book-open"></i> Explore Activities</a
            >
          </div>
          <div class="hero-proof">
            <span><i class="fa-solid fa-check"></i> Easy to use</span>
            <span><i class="fa-solid fa-check"></i> Built for school</span>
            <span><i class="fa-solid fa-check"></i> Always connected</span>
          </div>
        </div>

        <div class="hero-visual reveal" aria-hidden="true">
          <div class="dashboard-card">
            <div class="dash-top">
              <span class="mini-logo"
                ><i class="fa-solid fa-graduation-cap"></i></span
              ><span></span><span></span>
            </div>
            <div class="welcome-row">
              <div>
                <small>Good morning, learner</small
                ><b>Ready to achieve more?</b>
              </div>
              <i class="fa-solid fa-sun"></i>
            </div>
            <div class="progress-panel">
              <div class="panel-heading">
                <span>Learning progress</span><b>78%</b>
              </div>
              <div class="progress-track"><span></span></div>
              <small>You are doing great this week!</small>
            </div>
            <div class="lesson-row">
              <span class="lesson-icon blue"
                ><i class="fa-solid fa-book-open"></i
              ></span>
              <div>
                <b>Today's lesson</b><small>Science · Living things</small>
              </div>
              <i class="fa-solid fa-arrow-right"></i>
            </div>
            <div class="lesson-row">
              <span class="lesson-icon orange"
                ><i class="fa-solid fa-clipboard-check"></i
              ></span>
              <div>
                <b>New assignment</b><small>Mathematics · Due Friday</small>
              </div>
              <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
          <div class="floating-card students">
            <span><i class="fa-solid fa-users"></i></span>
            <div><b>Connected</b><small>Learning community</small></div>
          </div>
          <div class="floating-card trophy">
            <i class="fa-solid fa-trophy"></i>
            <div><b>Great job!</b><small>New achievement</small></div>
          </div>
        </div>
      </section>

      <section class="section features" id="features">
        <div class="section-heading reveal">
          <p class="eyebrow blue-text">Everything in one platform</p>
          <h2>Built for every part of the <span>learning journey.</span></h2>
          <p>
            Simple tools that help students stay engaged, teachers stay
            organized, and families stay informed.
          </p>
        </div>
        <div class="feature-grid">
          <article class="feature-card reveal">
            <span class="feature-icon indigo"
              ><i class="fa-solid fa-book-open"></i
            ></span>
            <h3>Learning Modules</h3>
            <p>
              Access organized lessons, reading materials, videos, and
              interactive activities at any time.
            </p>
            <a href="#courses"
              >Explore lessons <i class="fa-solid fa-arrow-right"></i
            ></a>
          </article>
          <article class="feature-card reveal">
            <span class="feature-icon teal"
              ><i class="fa-solid fa-chart-line"></i
            ></span>
            <h3>Progress Tracking</h3>
            <p>
              Follow learner performance and growth with clear,
              easy-to-understand progress records.
            </p>
            <a href="account.php"
              >View progress <i class="fa-solid fa-arrow-right"></i
            ></a>
          </article>
          <article class="feature-card reveal">
            <span class="feature-icon orange"
              ><i class="fa-solid fa-clipboard-list"></i
            ></span>
            <h3>Smart Assignments</h3>
            <p>
              Keep tasks, submissions, and deadlines in one place so nothing
              gets missed.
            </p>
            <a href="Form-folder/login.php"
              >See assignments <i class="fa-solid fa-arrow-right"></i
            ></a>
          </article>
          <article class="feature-card reveal">
            <span class="feature-icon pink"
              ><i class="fa-solid fa-award"></i
            ></span>
            <h3>Achievements</h3>
            <p>
              Celebrate effort and learning milestones with certificates and
              meaningful recognition.
            </p>
            <a href="account.php"
              >Discover achievements <i class="fa-solid fa-arrow-right"></i
            ></a>
          </article>
        </div>
      </section>

      <section class="stats-section" aria-label="Platform benefits">
        <div>
          <strong>01</strong><span>Centralized learning<br />resources</span>
        </div>
        <div>
          <strong>02</strong><span>Clear progress<br />monitoring</span>
        </div>
        <div>
          <strong>03</strong><span>Better school<br />communication</span>
        </div>
      </section>

      <section class="section learning-modules" id="learning-modules">
        <div class="section-heading reveal">
          <p class="eyebrow blue-text">LEARN ONE STEP AT A TIME</p>
          <h2>Explore your <span>quarterly learning modules.</span></h2>
          <p>
            Friendly, guided sample activities for elementary learners. Pick a
            quarter to see what is waiting for you.
          </p>
        </div>

        <div class="quarter-tabs" role="tablist" aria-label="Learning quarters">
          <button class="quarter-tab active" data-quarter="q1" role="tab">
            1st Quarter</button
          ><button class="quarter-tab" data-quarter="q2" role="tab">
            2nd Quarter</button
          ><button class="quarter-tab" data-quarter="q3" role="tab">
            3rd Quarter</button
          ><button class="quarter-tab" data-quarter="q4" role="tab">
            4th Quarter
          </button>
        </div>
        <div class="module-panels">
          <div class="module-panel active" id="q1">
            <article class="module-card math">
              <span><i class="fa-solid fa-calculator"></i></span>
              <p>Mathematics</p>
              <h3>Numbers Around Us</h3>
              <small>Counting, place value, and simple addition</small>
              <button class="play-btn" type="button" data-type="quiz" data-id="math1">
                Start quiz <i class="fa-solid fa-circle-play"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-seedling"></i></span>
              <p>Science</p>
              <h3>Living Things</h3>
              <small>Plants, animals, and their basic needs</small>
              <button class="play-btn" type="button" data-type="story" data-id="science1">
                Read story <i class="fa-solid fa-book-open"></i>
              </button>
            </article>
            <article class="module-card english">
              <span><i class="fa-solid fa-book"></i></span>
              <p>English</p>
              <h3>Reading Adventures</h3>
              <small>Story characters, setting, and new words</small>
              <button class="play-btn" type="button" data-type="challenge" data-id="english1">
                Try challenge <i class="fa-solid fa-bolt"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-puzzle-piece"></i></span><p>Science</p><h3>Animal Detectives</h3><small>Match animals to their homes and needs</small><em class="difficulty easy">Easy</em><button class="play-btn" type="button" data-id="science5">Play mission <i class="fa-solid fa-bolt"></i></button>
            </article>
            <article class="module-card filipino">
              <span><i class="fa-solid fa-book-open-reader"></i></span><p>Reading</p><h3>The Honest Kite</h3><small>Read, remember, and answer the story test</small><em class="difficulty medium">Medium</em><button class="play-btn" type="button" data-id="reading1">Read & test <i class="fa-solid fa-book-open"></i></button>
            </article>
          </div>
          <div class="module-panel" id="q2">
            <article class="module-card math">
              <span><i class="fa-solid fa-shapes"></i></span>
              <p>Mathematics</p>
              <h3>Shapes and Patterns</h3>
              <small>Recognize shapes and continue patterns</small>
              <button class="play-btn" type="button" data-type="quiz" data-id="math2">
                Start quiz <i class="fa-solid fa-circle-play"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-cloud-sun"></i></span>
              <p>Science</p>
              <h3>Weather Watch</h3>
              <small>Observe weather and stay safe each day</small>
              <button class="play-btn" type="button" data-type="challenge" data-id="science2">
                Try challenge <i class="fa-solid fa-bolt"></i>
              </button>
            </article>
            <article class="module-card filipino">
              <span><i class="fa-solid fa-comments"></i></span>
              <p>Filipino</p>
              <h3>Wika at Kuwento</h3>
              <small>Pagbasa, pakikinig, at pagsasalaysay</small>
              <button class="play-btn" type="button" data-type="story" data-id="filipino1">
                Read story <i class="fa-solid fa-book-open"></i>
              </button>
            </article>
            <article class="module-card math">
              <span><i class="fa-solid fa-ranking-star"></i></span><p>Mathematics</p><h3>Quiz Bee: Quick Think</h3><small>Race through addition and subtraction rounds</small><em class="difficulty medium">Medium</em><button class="play-btn" type="button" data-id="math5">Join quiz bee <i class="fa-solid fa-trophy"></i></button>
            </article>
            <article class="module-card english">
              <span><i class="fa-solid fa-spell-check"></i></span><p>English</p><h3>Grammar Garden</h3><small>Grow your garden by choosing the best sentence</small><em class="difficulty hard">Hard</em><button class="play-btn" type="button" data-id="english3">Start challenge <i class="fa-solid fa-bolt"></i></button>
            </article>
          </div>
          <div class="module-panel" id="q3">
            <article class="module-card math">
              <span><i class="fa-solid fa-ruler-combined"></i></span>
              <p>Mathematics</p>
              <h3>Measure and Compare</h3>
              <small>Length, time, and everyday measurement</small>
              <button class="play-btn" type="button" data-type="quiz" data-id="math3">
                Start quiz <i class="fa-solid fa-circle-play"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-recycle"></i></span>
              <p>Science</p>
              <h3>Our Earth, Our Home</h3>
              <small>Materials, waste, and caring for nature</small>
              <button class="play-btn" type="button" data-type="challenge" data-id="science3">
                Try challenge <i class="fa-solid fa-bolt"></i>
              </button>
            </article>
            <article class="module-card english">
              <span><i class="fa-solid fa-pen-nib"></i></span>
              <p>English</p>
              <h3>Writing My Ideas</h3>
              <small>Build sentences and describe your world</small>
              <button class="play-btn" type="button" data-type="story" data-id="english2">
                Read story <i class="fa-solid fa-book-open"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-flask"></i></span><p>Science</p><h3>Kitchen Scientists</h3><small>Predict what happens in everyday experiments</small><em class="difficulty medium">Medium</em><button class="play-btn" type="button" data-id="science6">Run experiment <i class="fa-solid fa-flask"></i></button>
            </article>
            <article class="module-card math">
              <span><i class="fa-solid fa-clock"></i></span><p>Mathematics</p><h3>Time Travelers</h3><small>Read clocks and solve daily schedule puzzles</small><em class="difficulty hard">Hard</em><button class="play-btn" type="button" data-id="math6">Solve puzzle <i class="fa-solid fa-puzzle-piece"></i></button>
            </article>
          </div>
          <div class="module-panel" id="q4">
            <article class="module-card math">
              <span><i class="fa-solid fa-coins"></i></span>
              <p>Mathematics</p>
              <h3>Money Matters</h3>
              <small>Identify Philippine money and solve problems</small>
              <button class="play-btn" type="button" data-type="challenge" data-id="math4">
                Try challenge <i class="fa-solid fa-bolt"></i>
              </button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-sun"></i></span>
              <p>Science</p>
              <h3>Light and Sound</h3>
              <small>Explore how light and sound help us</small>
              <button class="play-btn" type="button" data-type="quiz" data-id="science4">
                Start quiz <i class="fa-solid fa-circle-play"></i>
              </button>
            </article>
            <article class="module-card filipino">
              <span><i class="fa-solid fa-star"></i></span>
              <p>Values Education</p>
              <h3>Being a Good Citizen</h3>
              <small>Kindness, responsibility, and community</small>
              <button class="play-btn" type="button" data-type="story" data-id="filipino2">
                Read story <i class="fa-solid fa-book-open"></i>
              </button>
            </article>
            <article class="module-card english">
              <span><i class="fa-solid fa-medal"></i></span><p>English</p><h3>Final Quiz Bee</h3><small>Mixed vocabulary, reading, and grammar rounds</small><em class="difficulty hard">Hard</em><button class="play-btn" type="button" data-id="english4">Enter quiz bee <i class="fa-solid fa-trophy"></i></button>
            </article>
            <article class="module-card science">
              <span><i class="fa-solid fa-earth-americas"></i></span><p>Science</p><h3>Planet Protectors</h3><small>Make smart choices for a healthier planet</small><em class="difficulty medium">Medium</em><button class="play-btn" type="button" data-id="science7">Take action <i class="fa-solid fa-leaf"></i></button>
            </article>
          </div>
        </div>
      </section>

      <section class="section live-content" id="courses">
        <div class="section-heading reveal">
          <p class="eyebrow blue-text">FROM YOUR SCHOOL</p>
          <h2>Courses ready for <span>your next discovery.</span></h2>
          <p>
            These courses are published by the school through the admin
            dashboard.
          </p>
        </div>
        <div class="live-grid">
          <?php if ($courses): foreach ($courses as $course): ?>
          <article class="live-course reveal">
            <span class="course-symbol"
              ><i class="fa-solid fa-book-open"></i
            ></span>
            <p><?= homeE($course['course_code']) ?></p>
            <h3><?= homeE($course['title']) ?></h3>
            <small
              ><?= homeE($course['description'] ?: 'A new learning space is ready for learners.') ?></small
            ><a href="./Student-folder/index.php">Enroll course <i class="fa-solid fa-arrow-right"></i
            ></a>
          </article>
          <?php endforeach; else: ?>
          <article class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <h3>New courses will appear here</h3>
            <p>Ask your teacher or school administrator to publish a course.</p>
            <?php if ($isAdmin): ?><a
              class="btn btn-primary"
              href="Admin-folder/Dashboard.php"
              >Create a course</a
            ><?php endif; ?>
          </article>
          <?php endif; ?>
        </div>
      </section>

      <section class="section announcements" id="announcements">
        <div class="section-heading reveal">
          <p class="eyebrow blue-text">STAY INFORMED</p>
          <h2>School <span>announcements.</span></h2>
          <p>Important updates shared by your school team.</p>
        </div>
        <div class="announcement-list">
          <?php if ($announcements): foreach ($announcements as $announcement): ?>
          <article class="announcement reveal">
            <span><i class="fa-solid fa-bullhorn"></i></span>
            <div>
              <p class="announcement-audience">
                For <?= homeE($announcement['audience']) ?>
              </p>
              <h3><?= homeE($announcement['title']) ?></h3>
              <p><?= homeE($announcement['message']) ?></p>
              <small
                ><?= homeE(date('F j, Y', strtotime($announcement['created_at']))) ?></small
              >
            </div>
          </article>
          <?php endforeach; else: ?>
          <article class="empty-state">
            <i class="fa-solid fa-bell"></i>
            <h3>No announcements yet</h3>
            <p>
              School updates will be shown here as soon as they are published.
            </p>
          </article>
          <?php endif; ?>
        </div>
      </section>

      <section class="section experience" id="about">
        <div class="experience-visual reveal">
          <div class="hex hex-main">
            <i class="fa-solid fa-graduation-cap"></i><b>Jidanao<br />LMS</b>
          </div>
          <div class="hex hex-a"><i class="fa-solid fa-book"></i></div>
          <div class="hex hex-b"><i class="fa-solid fa-chart-column"></i></div>
          <div class="hex hex-c"><i class="fa-solid fa-users"></i></div>
          <div class="orbit orbit-a"></div>
          <div class="orbit orbit-b"></div>
        </div>
        <div class="experience-copy reveal">
          <p class="eyebrow blue-text">A better school experience</p>
          <h2>Learning that feels <span>clear and inspiring.</span></h2>
          <p>
            Our learning management system makes it easier to share knowledge,
            support every learner, and turn everyday schoolwork into meaningful
            progress.
          </p>
          <ul>
            <li>
              <i class="fa-solid fa-circle-check"></i> One secure space for
              students and teachers
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i> Organized lessons,
              activities, and records
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i> Designed for accessible,
              engaging learning
            </li>
          </ul>
          <a class="text-link" href="Form-folder/login.php"
            >Enter the learning portal <i class="fa-solid fa-arrow-right"></i
          ></a>
        </div>
      </section>

      <section class="contact" id="contact">
        <div class="contact-copy reveal">
          <p class="eyebrow">
            <i class="fa-solid fa-message"></i> Let’s connect
          </p>
          <h2>Have questions? We’re here to help.</h2>
          <p>
            Get in touch with the Jidanao LMS team for help accessing the
            platform or learning more about our school community.
          </p>
          <?php if (!empty($contactSent)): ?>
          <p class="contact-success">
            <i class="fa-solid fa-circle-check"></i> Thank you! Your message has
            been sent. We’ll get back to you soon.
          </p>
          <?php endif; ?>
          <?php if (!empty($contactError)): ?>
          <p class="contact-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?=
            homeE($contactError) ?>
          </p>
          <?php endif; ?>
          <form method="post" class="contact-form">
            <div class="contact-row">
              <input
                type="text"
                name="contact_name"
                placeholder="Your name"
                required
                value="<?= homeE($_POST['contact_name'] ?? '') ?>"
              />
              <input
                type="email"
                name="contact_email"
                placeholder="Your email"
                required
                value="<?= homeE($_POST['contact_email'] ?? '') ?>"
              />
            </div>
            <input
              type="text"
              name="contact_subject"
              placeholder="Subject (optional)"
              value="<?= homeE($_POST['contact_subject'] ?? '') ?>"
            />
            <textarea
              name="contact_message"
              placeholder="Write your message..."
              required
            >
<?= homeE($_POST['contact_message'] ?? '') ?></textarea
            >
            <button type="submit" name="contact_submit" class="btn btn-light">
              Send message <i class="fa-solid fa-paper-plane"></i>
            </button>
          </form>
          <div class="contact-details">
            <a href="mailto:info@jidanaolms.edu.ph"
              ><i class="fa-solid fa-envelope"></i
              ><span><small>Email us</small>info@jidanaolms.edu.ph</span></a
            ><a href="tel:+630000000000"
              ><i class="fa-solid fa-phone"></i
              ><span><small>Call us</small>School office</span></a
            >
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="footer-top">
        <a class="brand" href="#home"
          ><span class="brand-mark"
            ><i class="fa-solid fa-graduation-cap"></i></span
          ><span>Jidanao <strong>LMS</strong></span></a
        >
        <p>
          Empowering learners, supporting teachers, and strengthening our school
          community.
        </p>
        <div class="social-links">
          <a href="#" aria-label="Facebook"
            ><i class="fa-brands fa-facebook-f"></i></a
          ><a href="#" aria-label="Instagram"
            ><i class="fa-brands fa-instagram"></i></a
          ><a href="#contact" aria-label="Email"
            ><i class="fa-solid fa-envelope"></i
          ></a>
        </div>
      </div>
      <div class="footer-bottom">
        <span
          >© <span id="year"></span> Jidanao Learning Management System.</span
        ><span>Learn today. Lead tomorrow.</span>
      </div>
    </footer>

    <div id="learningModal" class="learning-overlay" aria-hidden="true">
      <div class="learning-content" role="dialog" aria-modal="true" aria-label="Interactive learning activity">
        <button class="close-overlay" type="button" aria-label="Close activity">×</button>
        <div id="activity-container"></div>
      </div>
    </div>

    <style>
      .play-btn {
        margin-top: 12px;
        width: 100%;
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
      }

      .play-btn:hover {
        transform: translateY(-1px);
      }

      .learning-overlay {
        position: fixed;
        inset: 0;
        background: rgba(8, 15, 31, 0.84);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 99999;
        backdrop-filter: blur(6px);
      }

      .learning-content {
        width: min(100%, 720px);
        background: #fff;
        border-radius: 24px;
        padding: 32px 28px 24px;
        position: relative;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
      }

      .close-overlay {
        position: absolute;
        top: 12px;
        right: 14px;
        border: 0;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
        color: #475569;
      }

      .activity-badge {
        display: inline-block;
        margin-bottom: 8px;
        padding: 6px 10px;
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
      }

      .activity-steps {
        display: grid;
        gap: 10px;
        margin-top: 18px;
      }

      .quiz-option {
        width: 100%;
        border: 1px solid #dbeafe;
        background: #f8fbff;
        padding: 12px 14px;
        border-radius: 12px;
        text-align: left;
        cursor: pointer;
        font-weight: 600;
        color: #0f172a;
      }

      .quiz-option:hover {
        border-color: #60a5fa;
        background: #eef6ff;
      }

      .story-box {
        background: linear-gradient(135deg, #f8fafc, #eff6ff);
        padding: 18px;
        border-radius: 16px;
        line-height: 1.8;
        color: #1e293b;
        margin-top: 12px;
      }

      .activity-nav {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 18px;
      }

      .activity-nav button {
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        cursor: pointer;
        font-weight: 700;
      }

      .activity-nav .primary {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #fff;
      }

      .activity-nav .secondary {
        background: #e2e8f0;
        color: #0f172a;
      }
    </style>

    <script src="script.js"></script>
    <script>
      const activityData = {
        math1: {
          title: 'Numbers Around Us',
          category: 'Math Quiz',
          type: 'quiz',
          difficulty: 'Easy',
          intro: 'Solve a few quick number challenges and earn stars.',
          questions: [
            { prompt: 'What is 5 + 3?', options: ['7', '8', '9'], answer: 0 },
            { prompt: 'Which number comes after 10?', options: ['9', '11', '12'], answer: 1 },
            { prompt: 'What is 4 + 4?', options: ['6', '7', '8'], answer: 2 }
          ],
          reward: 'You earned 10 stars!'
        },
        science1: {
          title: 'The Tiny Seed',
          category: 'Science Story',
          type: 'story',
          difficulty: 'Easy',
          intro: 'Follow Sunny the seed as he grows.',
          pages: [
            'Sunny the little seed slept quietly under the warm soil.',
            'One morning, the rain tapped softly on the ground.',
            'Soon, Sunny stretched up and reached for the sunlight.',
            'He grew strong and proudly became a green sprout.'
          ],
          reward: 'You unlocked a nature badge!',
          questions: [
            { prompt: 'What helped Sunny grow?', options: ['Rain and sunlight', 'Snow and darkness', 'A loud song'], answer: 0 },
            { prompt: 'What did Sunny become?', options: ['A stone', 'A green sprout', 'A kite'], answer: 1 }
          ]
        },
        english1: {
          title: 'Word Challenge',
          category: 'English Challenge',
          type: 'challenge',
          difficulty: 'Easy',
          intro: 'Choose the right word to complete the sentence.',
          questions: [
            { prompt: 'The cat is very ___ today.', options: ['happy', 'table', 'door'], answer: 0 },
            { prompt: 'We read a ___ before bed.', options: ['story', 'car', 'shoe'], answer: 0 }
          ],
          reward: 'You completed the challenge!'
        },
        math2: {
          title: 'Shapes and Patterns',
          category: 'Math Quiz',
          type: 'quiz',
          difficulty: 'Easy',
          intro: 'Spot the shape pattern and answer carefully.',
          questions: [
            { prompt: 'How many sides does a triangle have?', options: ['2', '3', '4'], answer: 1 },
            { prompt: 'Which shape has 4 equal sides?', options: ['Circle', 'Rectangle', 'Square'], answer: 2 }
          ],
          reward: 'You earned 8 stars!'
        },
        science2: {
          title: 'Weather Explorer',
          category: 'Science Challenge',
          type: 'challenge',
          difficulty: 'Easy',
          intro: 'Pick the best choice for a rainy day.',
          questions: [
            { prompt: 'What should you wear when it is raining?', options: ['Sandals', 'Raincoat', 'Shorts'], answer: 1 },
            { prompt: 'What do we use to see in the dark?', options: ['Flashlight', 'Spoon', 'Book'], answer: 0 }
          ],
          reward: 'You completed a weather mission!'
        },
        filipino1: {
          title: 'Ang Bahay ng Pusa',
          category: 'Filipino Story',
          type: 'story',
          difficulty: 'Medium',
          intro: 'Basahin ang kwento at alamin ang mensahe.',
          pages: [
            'Si Pusa ay may pusong mabait at laging handa tumulong.',
            'Sa tuwing may problema, siya ay nagiging masaya at mapagbigay.',
            'Ang kanyang kwento ay nagpapakita ng pag-ibig sa pamilya.'
          ],
          reward: 'Naunawaan mo ang kwento!',
          questions: [
            { prompt: 'Ano ang ugali ni Pusa?', options: ['Mabait at matulungin', 'Madamot', 'Laging natutulog'], answer: 0 },
            { prompt: 'Ano ang ipinapakita ng kuwento?', options: ['Pag-ibig sa pamilya', 'Pag-aaksaya', 'Pagiging makasarili'], answer: 0 }
          ]
        },
        math3: {
          title: 'Measure and Compare',
          category: 'Math Quiz',
          type: 'quiz',
          difficulty: 'Medium',
          intro: 'Compare sizes and measurements with confidence.',
          questions: [
            { prompt: 'Which is longer?', options: ['Pencil', 'Ruler', 'Marker'], answer: 1 },
            { prompt: 'What comes after 15?', options: ['14', '16', '17'], answer: 1 }
          ],
          reward: 'You earned 9 stars!'
        },
        science3: {
          title: 'Earth Helpers',
          category: 'Science Challenge',
          type: 'challenge',
          difficulty: 'Medium',
          intro: 'Choose the best action for caring for our planet.',
          questions: [
            { prompt: 'What should you do with plastic bottles?', options: ['Throw them away', 'Reuse or recycle them', 'Leave them outside'], answer: 1 },
            { prompt: 'How can we help nature?', options: ['Plant trees', 'Litter everywhere', 'Waste water'], answer: 0 }
          ],
          reward: 'You helped protect Earth!'
        },
        english2: {
          title: 'My Story Journal',
          category: 'English Story',
          type: 'story',
          difficulty: 'Medium',
          intro: 'Read a short journal entry and feel inspired.',
          pages: [
            'I wrote about the clouds in the sky and the bright morning light.',
            'I smiled when I saw the birds flying above the trees.',
            'I felt brave and ready to learn something new.'
          ],
          reward: 'You finished the reading activity!',
          questions: [
            { prompt: 'What did the writer see above the trees?', options: ['Birds', 'Boats', 'Buses'], answer: 0 },
            { prompt: 'How did the writer feel at the end?', options: ['Ready to learn', 'Sleepy and sad', 'Angry'], answer: 0 }
          ]
        },
        math4: {
          title: 'Money Matters',
          category: 'Math Challenge',
          type: 'challenge',
          difficulty: 'Medium',
          intro: 'Practice simple money choices and solve each task.',
          questions: [
            { prompt: 'Which coin is worth 25 cents?', options: ['1-centavo', '25-centavo', '10-centavo'], answer: 1 },
            { prompt: 'What is 10 + 5?', options: ['12', '15', '20'], answer: 1 }
          ],
          reward: 'You completed the money challenge!'
        },
        science4: {
          title: 'Light and Sound',
          category: 'Science Quiz',
          type: 'quiz',
          difficulty: 'Easy',
          intro: 'Learn how light and sound help us every day.',
          questions: [
            { prompt: 'What helps us see in the dark?', options: ['Light', 'Rain', 'Wind'], answer: 0 },
            { prompt: 'What do we hear with?', options: ['Ears', 'Hands', 'Eyes'], answer: 0 }
          ],
          reward: 'Great work! You finished the quiz.'
        },
        filipino2: {
          title: 'Pagiging Mabuting Kaibigan',
          category: 'Values Story',
          type: 'story',
          difficulty: 'Medium',
          intro: 'Basahin ang kwento tungkol sa pagiging mabuti.',
          pages: [
            'Ang isang mabuting kaibigan ay laging handang makinig.',
            'Tinutulungan niya ang iba sa panahon ng hirap.',
            'Sa ganitong paraan, ang samahan ay nagiging mas masaya.'
          ],
          reward: 'You finished the values story!',
          questions: [
            { prompt: 'What does a good friend do?', options: ['Listen and help', 'Ignore everyone', 'Take things'], answer: 0 },
            { prompt: 'What makes friendship happier?', options: ['Helping others', 'Fighting', 'Being unkind'], answer: 0 }
          ]
        },
        science5: {
          title: 'Animal Detectives', category: 'Science Mission', type: 'challenge', difficulty: 'Easy',
          intro: 'Find the best home and need for each animal.',
          questions: [
            { prompt: 'Where does a fish live?', options: ['Water', 'A tree', 'A desert'], answer: 0 },
            { prompt: 'What do all animals need?', options: ['Food and water', 'A television', 'A pencil'], answer: 0 },
            { prompt: 'Which animal has feathers?', options: ['Bird', 'Cat', 'Fish'], answer: 0 }
          ], reward: 'Great detective work!'
        },
        reading1: {
          title: 'The Honest Kite', category: 'Reading Story', type: 'story', difficulty: 'Medium',
          intro: 'Read carefully. A memory test comes after the story.',
          pages: [
            'Mia found a bright red kite beside the school fence.',
            'She asked her classmates whose kite it was, but nobody answered.',
            'Mia brought the kite to her teacher, who found its name on the handle.',
            'The kite returned to Ben, and Mia felt proud that she had been honest.'
          ],
          questions: [
            { prompt: 'Where did Mia find the kite?', options: ['Beside the school fence', 'In a market', 'Under her bed'], answer: 0 },
            { prompt: 'How did Mia help return it?', options: ['She gave it to her teacher', 'She hid it', 'She threw it away'], answer: 0 },
            { prompt: 'What value did Mia show?', options: ['Honesty', 'Anger', 'Carelessness'], answer: 0 }
          ], reward: 'You remembered the story and showed careful reading!'
        },
        math5: {
          title: 'Quiz Bee: Quick Think', category: 'Mathematics Quiz Bee', type: 'quiz', difficulty: 'Medium',
          intro: 'Answer carefully through four quiz-bee rounds.',
          questions: [
            { prompt: 'What is 12 - 5?', options: ['5', '7', '8'], answer: 1 },
            { prompt: 'What is 6 + 9?', options: ['14', '15', '16'], answer: 1 },
            { prompt: 'Which is greater?', options: ['18', '8', '3'], answer: 0 },
            { prompt: 'What is 2 + 2 + 2?', options: ['4', '5', '6'], answer: 2 }
          ], reward: 'You finished the quiz-bee round!'
        },
        english3: {
          title: 'Grammar Garden', category: 'English Challenge', type: 'challenge', difficulty: 'Hard',
          intro: 'Choose the sentence that helps your grammar garden grow.',
          questions: [
            { prompt: 'Choose the correct sentence.', options: ['She are happy.', 'She is happy.', 'She am happy.'], answer: 1 },
            { prompt: 'Choose the plural word.', options: ['Book', 'Books', 'Bookish'], answer: 1 },
            { prompt: 'Which word is an action?', options: ['Jump', 'Blue', 'Chair'], answer: 0 }
          ], reward: 'Your grammar garden is blooming!'
        },
        science6: {
          title: 'Kitchen Scientists', category: 'Science Experiment', type: 'challenge', difficulty: 'Medium',
          intro: 'Make a prediction, then choose the result that makes sense.',
          questions: [
            { prompt: 'What happens to ice in a warm place?', options: ['It melts', 'It grows leaves', 'It becomes louder'], answer: 0 },
            { prompt: 'Which object usually floats on water?', options: ['A stone', 'A dry leaf', 'A metal coin'], answer: 1 },
            { prompt: 'What do plants need to grow?', options: ['Sunlight and water', 'Only toys', 'Darkness only'], answer: 0 }
          ], reward: 'You made smart science predictions!'
        },
        math6: {
          title: 'Time Travelers', category: 'Mathematics Puzzle', type: 'challenge', difficulty: 'Hard',
          intro: 'Solve time and schedule puzzles from morning to night.',
          questions: [
            { prompt: 'What comes after 7:00?', options: ['6:00', '7:30', '5:00'], answer: 1 },
            { prompt: 'If class starts at 8 and ends at 9, how long is it?', options: ['1 hour', '2 hours', '30 hours'], answer: 0 },
            { prompt: 'Which comes first?', options: ['Breakfast', 'Bedtime', 'Midnight'], answer: 0 }
          ], reward: 'You solved the schedule!'
        },
        english4: {
          title: 'Final Quiz Bee', category: 'English Quiz Bee', type: 'quiz', difficulty: 'Hard',
          intro: 'A mixed final round of words, reading, and grammar.',
          questions: [
            { prompt: 'A synonym for big is…', options: ['Large', 'Tiny', 'Quiet'], answer: 0 },
            { prompt: 'Which word completes: They ___ playing?', options: ['is', 'are', 'am'], answer: 1 },
            { prompt: 'What is the main idea of a story?', options: ['Its central message', 'The page number', 'The longest word'], answer: 0 },
            { prompt: 'Which is a complete sentence?', options: ['Running fast.', 'The dog runs fast.', 'Because the dog.'], answer: 1 }
          ], reward: 'You completed the final quiz bee!'
        },
        science7: {
          title: 'Planet Protectors', category: 'Science Mission', type: 'challenge', difficulty: 'Medium',
          intro: 'Choose actions that protect our shared home.',
          questions: [
            { prompt: 'What should we do with reusable bottles?', options: ['Use them again', 'Throw them anywhere', 'Hide them'], answer: 0 },
            { prompt: 'How can we save electricity?', options: ['Turn off unused lights', 'Leave lights on', 'Open the refrigerator'], answer: 0 },
            { prompt: 'Where should litter go?', options: ['In a proper bin', 'On the road', 'In a river'], answer: 0 }
          ], reward: 'You are a true planet protector!'
        }
      };

      let currentStep = 0;
      let currentScore = 0;
      let activeActivityId = null;

      document.querySelectorAll('.play-btn').forEach((button) => {
        const activity = activityData[button.dataset.id];
        if (activity && !button.parentElement.querySelector('.difficulty')) {
          const level = document.createElement('em');
          level.className = 'difficulty ' + activity.difficulty.toLowerCase();
          level.textContent = activity.difficulty;
          button.parentElement.insertBefore(level, button);
        }
      });

      document.querySelectorAll('.play-btn').forEach((button) => {
        button.addEventListener('click', () => {
          const modal = document.getElementById('learningModal');
          const container = document.getElementById('activity-container');
          const activity = activityData[button.dataset.id];

          activeActivityId = button.dataset.id;
          currentStep = 0;
          currentScore = 0;
          activity.comprehensionStarted = false;
          syncActivityProgress(activeActivityId, activity ? activity.title : activeActivityId, 'started', 0);
          modal.style.display = 'flex';
          modal.setAttribute('aria-hidden', 'false');

          if (!activity) {
            container.innerHTML = '<h2>Coming soon</h2><p>This activity will be added soon.</p>';
            return;
          }

          if (activity.type === 'story') {
            renderStoryActivity(activity, container);
          } else {
            renderQuizActivity(activity, container);
          }
        });
      });

      document.querySelector('.close-overlay').addEventListener('click', closeLearningModal);
      document.getElementById('learningModal').addEventListener('click', (event) => {
        if (event.target.id === 'learningModal') {
          closeLearningModal();
        }
      });

      function closeLearningModal() {
        const modal = document.getElementById('learningModal');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
      }

      function syncActivityProgress(activityId, activityTitle, status, score) {
        fetch('save_progress.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ activity_id: activityId, activity_title: activityTitle, status, score })
        }).catch(() => {});
      }

      function completeActivity(activity) {
        const total = activity.questions ? activity.questions.length : activity.pages.length;
        const score = activity.questions ? Math.round((currentScore / total) * 100) : 100;
        syncActivityProgress(activeActivityId, activity.title, 'completed', score);
      }

      function renderQuizActivity(activity, container) {
        const question = activity.questions[currentStep];

        container.innerHTML = `
          <span class="activity-badge">${activity.category}</span>
          <h2>${activity.title}</h2>
          <p>${activity.intro}</p>
          <div class="story-box">
            <h3>${question.prompt}</h3>
            <div class="activity-steps">
              ${question.options.map((option, index) => `<button class="quiz-option" type="button" data-index="${index}">${option}</button>`).join('')}
            </div>
          </div>
          <div class="activity-nav">
            <span>Question ${currentStep + 1} of ${activity.questions.length}</span>
            <span>Score: ${currentScore}</span>
          </div>
        `;

        container.querySelectorAll('.quiz-option').forEach((button) => {
          button.addEventListener('click', () => {
            const selected = Number(button.getAttribute('data-index'));
            if (selected === question.answer) {
              currentScore += 1;
            }

            if (currentStep + 1 < activity.questions.length) {
              currentStep += 1;
              renderQuizActivity(activity, container);
            } else {
              completeActivity(activity);
              container.innerHTML = `
                <span class="activity-badge">${activity.category}</span>
                <h2>${activity.title}</h2>
                <div class="story-box">
                  <h3>🎉 Activity complete!</h3>
                  <p>You scored ${currentScore} out of ${activity.questions.length}.</p>
                  <p>${activity.reward}</p>
                </div>
                <div class="activity-nav">
                  <button class="secondary" type="button" onclick="closeLearningModal()">Close</button>
                  <button class="primary" type="button" onclick="location.reload()">Try another</button>
                </div>
              `;
            }
          });
        });
      }

      function renderStoryActivity(activity, container) {
        const page = activity.pages[currentStep];

        container.innerHTML = `
          <span class="activity-badge">${activity.category}</span>
          <h2>${activity.title}</h2>
          <p>${activity.intro}</p>
          <div class="story-box">${page}</div>
          <div class="activity-nav">
            <button class="secondary" type="button" ${currentStep === 0 ? 'disabled' : ''} onclick="goToStoryStep(${currentStep - 1})">Previous</button>
            <button class="primary" type="button" onclick="goToStoryStep(${currentStep + 1})">${currentStep === activity.pages.length - 1 ? 'Finish' : 'Next'}</button>
          </div>
        `;
      }

      function goToStoryStep(step) {
        currentStep = step;
        const container = document.getElementById('activity-container');
        const selectedActivity = activityData[activeActivityId] || activityData.science1;

        if (currentStep >= 0 && currentStep < selectedActivity.pages.length) {
          renderStoryActivity(selectedActivity, container);
        } else if (currentStep >= selectedActivity.pages.length) {
          if (selectedActivity.questions && !selectedActivity.comprehensionStarted) {
            selectedActivity.comprehensionStarted = true;
            currentStep = 0;
            currentScore = 0;
            renderQuizActivity(selectedActivity, container);
            return;
          }
          completeActivity(selectedActivity);
          container.innerHTML = `
            <span class="activity-badge">${selectedActivity.category}</span>
            <h2>${selectedActivity.title}</h2>
            <div class="story-box">
              <h3>📖 Story complete!</h3>
              <p>${selectedActivity.reward}</p>
            </div>
            <div class="activity-nav">
              <button class="secondary" type="button" onclick="closeLearningModal()">Close</button>
              <button class="primary" type="button" onclick="location.reload()">Try another</button>
            </div>
          `;
        }
      }
    </script>
  </body>
</html>
