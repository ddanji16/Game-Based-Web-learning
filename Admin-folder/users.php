
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Ensure the users table has a grade_level column (Grades 1-6).
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS grade_level TINYINT NULL DEFAULT NULL");

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function back($type, $message)
{
    $_SESSION['users_message'] = [$type, $message];

    header('Location: users.php');
    exit;
}

mysqli_query(
    $con,
    "CREATE TABLE IF NOT EXISTS student_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        activity_title VARCHAR(150) NOT NULL,
        progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
        score DECIMAL(5,2) NULL,
        status ENUM('not_started', 'in_progress', 'completed')
            NOT NULL DEFAULT 'not_started',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        CONSTRAINT fk_progress_student
            FOREIGN KEY (student_id)
            REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        back('error', 'Session expired. Please try again.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
$usertype = (int) ($_POST['usertype'] ?? -1);
        $password = $_POST['password'] ?? '';
        $gradeLevel = isset($_POST['grade_level']) && $_POST['grade_level'] !== ''
            ? (int) $_POST['grade_level']
            : null;
        if ($gradeLevel !== null && ($gradeLevel < 1 || $gradeLevel > 6)) {
            $gradeLevel = null;
        }

        if (
            $firstname === '' ||
            $lastname === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 8 ||
            !in_array($usertype, [0, 1, 2], true)
        ) {
            back(
                'error',
                'Complete all required fields; password must be at least 8 characters.'
            );
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $statement = mysqli_prepare(
            $con,
            'INSERT INTO users (
                Firstname,
                Middlename,
                Lastname,
                Email,
                createpassword,
                confirmpassword,
                UserType,
                grade_level
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        mysqli_stmt_bind_param(
            $statement,
            'ssssssii',
            $firstname,
            $middlename,
            $lastname,
            $email,
            $passwordHash,
            $passwordHash,
            $usertype,
            $gradeLevel
        );

        if (!mysqli_stmt_execute($statement)) {
            back('error', 'Email already exists or account could not be created.');
        }

        back('success', 'User account created.');
    }

    if ($action === 'update') {
        $userId = (int) ($_POST['id'] ?? 0);
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
$email = trim($_POST['email'] ?? '');
        $usertype = (int) ($_POST['usertype'] ?? -1);
        $gradeLevel = isset($_POST['grade_level']) && $_POST['grade_level'] !== ''
            ? (int) $_POST['grade_level']
            : null;
        if ($gradeLevel !== null && ($gradeLevel < 1 || $gradeLevel > 6)) {
            $gradeLevel = null;
        }

        if (
            $userId === $adminId ||
            $firstname === '' ||
            $lastname === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($usertype, [0, 1, 2], true)
        ) {
            back(
                'error',
                'Invalid user details. You cannot edit your own account here.'
            );
        }

$statement = mysqli_prepare(
            $con,
            'UPDATE users
             SET Firstname = ?, Middlename = ?, Lastname = ?, Email = ?, UserType = ?, grade_level = ?
             WHERE id = ?'
        );

        mysqli_stmt_bind_param(
            $statement,
            'ssssiii',
            $firstname,
            $middlename,
            $lastname,
            $email,
            $usertype,
            $gradeLevel,
            $userId
        );

        if (!mysqli_stmt_execute($statement)) {
            back('error', 'Could not update this user.');
        }

        back('success', 'User updated.');
    }

    if ($action === 'delete') {
        $userId = (int) ($_POST['id'] ?? 0);

        if ($userId === $adminId) {
            back('error', 'You cannot delete your own account.');
        }

        $statement = mysqli_prepare($con, 'DELETE FROM users WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $userId);
        mysqli_stmt_execute($statement);

        back('success', 'User deleted.');
    }

    if ($action === 'progress') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $activityTitle = trim($_POST['activity_title'] ?? '');
        $progressPercentage = (int) ($_POST['progress_percentage'] ?? 0);
        $score = ($_POST['score'] ?? '') === ''
            ? null
            : (float) $_POST['score'];
        $status = $_POST['status'] ?? '';

        if (
            $studentId < 1 ||
            $activityTitle === '' ||
            $progressPercentage < 0 ||
            $progressPercentage > 100 ||
            !in_array($status, ['not_started', 'in_progress', 'completed'], true)
        ) {
            back('error', 'Enter valid progress details.');
        }

        $statement = mysqli_prepare(
            $con,
            'INSERT INTO student_progress (
                student_id,
                activity_title,
                progress_percentage,
                score,
                status
            ) VALUES (?, ?, ?, ?, ?)'
        );

        mysqli_stmt_bind_param(
            $statement,
            'isids',
            $studentId,
            $activityTitle,
            $progressPercentage,
            $score,
            $status
        );

        mysqli_stmt_execute($statement);

        back('success', 'Progress record saved.');
    }

    if ($action === 'delete_progress') {
        $progressId = (int) ($_POST['id'] ?? 0);

        $statement = mysqli_prepare(
            $con,
            'DELETE FROM student_progress WHERE id = ?'
        );

        mysqli_stmt_bind_param($statement, 'i', $progressId);
        mysqli_stmt_execute($statement);

        back('success', 'Progress record deleted.');
    }
}

$message = $_SESSION['users_message'] ?? null;
unset($_SESSION['users_message']);

$edit = null;

if (isset($_GET['edit'])) {
    $userId = (int) $_GET['edit'];

    $statement = mysqli_prepare(
        $con,
'SELECT id, Firstname, Middlename, Lastname, Email, UserType, grade_level
         FROM users
         WHERE id = ?'
    );

    mysqli_stmt_bind_param($statement, 'i', $userId);
    mysqli_stmt_execute($statement);

    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
}

$users = mysqli_query(
    $con,
    "SELECT
        u.*,
        ROUND(AVG(p.progress_percentage)) AS average_progress,
        COUNT(p.id) AS records
     FROM users u
     LEFT JOIN student_progress p ON p.student_id = u.id
     GROUP BY u.id
     ORDER BY u.Firstname"
);

$students = mysqli_query(
    $con,
    'SELECT id, Firstname, Lastname, grade_level
     FROM users
     WHERE UserType = 0
     ORDER BY Firstname'
);

$progress = mysqli_query(
    $con,
    "SELECT
        p.*,
        CONCAT_WS(' ', u.Firstname, u.Lastname) AS student
     FROM student_progress p
     JOIN users u ON u.id = p.student_id
     ORDER BY p.updated_at DESC
     LIMIT 20"
);
?>
```
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>User Management</title>
    <link rel="stylesheet" href="dashboard.css" />
    <link rel="stylesheet" href="users.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />
  </head>
  <body>
    <div class="app-shell">
      <aside class="sidebar" id="sidebar">
        <a class="brand" href="Dashboard.php"
          ><span class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a
        >
        <nav>
<a href="Dashboard.php"><i class="fa-solid fa-grid-2"></i> Overview</a>
          <a href="Dashboard.php#courses"><i class="fa-solid fa-book-open"></i> Courses</a>
          <a class="active" href="users.php"><i class="fa-solid fa-users"></i> Users</a>
          <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
          <a href="notifications.php"><i class="fa-solid fa-bell"></i> Announcement</a>
          <a href="activity.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity logs</a>
        </nav>
        <a class="logout" href="../logout.php"
          ><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a
        >
      </aside>
      <main>
        <header>
          <button class="menu-btn" id="menuButton">
            <i class="fa-solid fa-bars"></i>
          </button>
          <div>
            <p class="eyebrow">ADMINISTRATOR PORTAL</p>
            <h1>User management</h1>
            <p class="subtle">
              Manage accounts and track each learner's progress.
            </p>
          </div>
        </header>
        <?php if($message):?>
        <div class="flash <?=h($message[0])?>"><?=h($message[1])?></div>
        <?php endif;?>
        <section class="user-grid">
          <article class="panel">
            <div class="panel-title">
              <div>
                <p class="eyebrow"><?= $edit?'EDIT USER':'CREATE USER'?></p>
                <h2><?= $edit?'Update account':'New account'?></h2>
              </div>
              <?php if($edit):?><a class="text-button" href="users.php"
                >Cancel</a
              ><?php endif;?>
            </div>
            <form method="post" class="stack-form">
              <input
                type="hidden"
                name="csrf_token"
                value="<?=h($_SESSION['csrf_token'])?>"
              /><input
                type="hidden"
                name="action"
                value="<?=$edit?'update':'create'?>"
              /><?php if($edit):?><input
                type="hidden"
                name="id"
                value="<?=$edit['id']?>"
              /><?php endif;?>
              <div class="split">
                <label
                  >First name<input
                    required
                    name="firstname"
                    value="<?=h($edit['Firstname']??'')?>" /></label
                ><label
                  >Middle name<input
                    name="middlename"
                    value="<?=h($edit['Middlename']??'')?>"
                /></label>
              </div>
              <label
                >Last name<input
                  required
                  name="lastname"
                  value="<?=h($edit['Lastname']??'')?>" /></label
              ><label
                >Email<input
                  required
                  type="email"
                  name="email"
                  value="<?=h($edit['Email']??'')?>" /></label
><label
                >Role<select name="usertype">
                  <option value="0" <?= (int)($edit['UserType']??'')===0?'selected':'' ?>>Student</option>
                  <option value="2" <?= (int)($edit['UserType']??'')===2?'selected':'' ?>>Teacher</option>
                  <option value="1" <?= (int)($edit['UserType']??'')===1?'selected':'' ?>>Administrator</option>
                </select></label
              ><label
                >Grade level<select name="grade_level">
                  <option value="">Not set</option>
                  <option value="1" <?= (int)($edit['grade_level']??'')===1?'selected':'' ?>>Grade 1</option>
                  <option value="2" <?= (int)($edit['grade_level']??'')===2?'selected':'' ?>>Grade 2</option>
                  <option value="3" <?= (int)($edit['grade_level']??'')===3?'selected':'' ?>>Grade 3</option>
                  <option value="4" <?= (int)($edit['grade_level']??'')===4?'selected':'' ?>>Grade 4</option>
                  <option value="5" <?= (int)($edit['grade_level']??'')===5?'selected':'' ?>>Grade 5</option>
                  <option value="6" <?= (int)($edit['grade_level']??'')===6?'selected':'' ?>>Grade 6</option>
                </select></label
              ><?php if(!$edit):?><label
                >Password<input
                  required
                  minlength="8"
                  type="password"
                  name="password" /></label
              ><?php endif;?><button class="primary">
                <?=$edit?'Save changes':'Create account'?>
              </button>
            </form>
          </article>
          <article class="panel">
            <div class="panel-title">
              <div>
                <p class="eyebrow">PROGRESS TRACKING</p>
                <h2>Record student progress</h2>
              </div>
            </div>
            <form method="post" class="stack-form">
              <input
                type="hidden"
                name="csrf_token"
                value="<?=h($_SESSION['csrf_token'])?>"
              /><input type="hidden" name="action" value="progress" /><label
>Student<select required name="student_id">
                  <option value="">Select student</option>
                  <?php while($s=mysqli_fetch_assoc($students)):?>
                  <option value="<?=$s['id']?>">
                    <?=h($s['Firstname'].' '.$s['Lastname'])?><?= $s['grade_level'] ? ' (Grade '.h($s['grade_level']).')' : '' ?>
                  </option>
                  <?php endwhile;?>
                </select></label
              ><label
                >Module or activity<input
                  required
                  name="activity_title"
                  placeholder="e.g. Q1 Numbers Around Us"
              /></label>
              <div class="split">
                <label
                  >Completion %<input
                    required
                    type="number"
                    name="progress_percentage"
                    min="0"
                    max="100"
                    value="0" /></label
                ><label
                  >Score<input
                    type="number"
                    name="score"
                    min="0"
                    max="100"
                    step=".01"
                /></label>
              </div>
              <label
                >Status<select name="status">
                  <option value="not_started">Not started</option>
                  <option value="in_progress">In progress</option>
                  <option value="completed">Completed</option>
                </select></label
              ><button class="primary">Save progress</button>
            </form>
          </article>
        </section>
        <section class="panel list-panel">
          <div class="panel-title">
            <div>
              <p class="eyebrow">USER DIRECTORY</p>
              <h2>Accounts</h2>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
<tr>
                  <th>User</th>
                  <th>Role</th>
                  <th>Grade</th>
                  <th>Average progress</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php while($u=mysqli_fetch_assoc($users)):?>
                <tr>
                  <td>
                    <b><?=h($u['Firstname'].' '.$u['Lastname'])?></b
                    ><span><?=h($u['Email'])?></span>
                  </td>
<td>
                    <?=['Student','Administrator','Teacher'][(int)$u['UserType']]?>
                  </td>
                  <td>
                    <?= $u['grade_level'] ? 'Grade '.h($u['grade_level']) : '—' ?>
                  </td>
                  <td>
                    <?php if((int)$u['UserType']===0):?>
                    <div class="bar">
                      <i
                        style="
                          width: <?= (int)
                            ($u['average_progress'] ?? 0) ?>%;
                        "
                      ></i>
                    </div>
                    <?= (int)($u['average_progress']??0)?>%
                    <small
                      ><?= (int)$u['records']?>
                      entries</small
                    ><?php else:?>—<?php endif;?>
                  </td>
                  <td class="actions">
                    <?php if((int)$u['id']!==$adminId):?><a
                      class="icon-button"
                      href="users.php?edit=<?=$u['id']?>"
                      ><i class="fa-solid fa-pen"></i
                    ></a>
                    <form
                      method="post"
                      onsubmit="return confirm('Delete this user?');"
                    >
                      <input
                        type="hidden"
                        name="csrf_token"
                        value="<?=h($_SESSION['csrf_token'])?>"
                      /><input
                        type="hidden"
                        name="action"
                        value="delete"
                      /><input
                        type="hidden"
                        name="id"
                        value="<?=$u['id']?>"
                      /><button class="icon-button danger">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                    <?php endif;?>
                  </td>
                </tr>
                <?php endwhile;?>
              </tbody>
            </table>
          </div>
        </section>
        <section class="panel list-panel">
          <div class="panel-title">
            <div>
              <p class="eyebrow">LEARNER RECORDS</p>
              <h2>Recent progress</h2>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Activity</th>
                  <th>Progress</th>
                  <th>Score</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php while($p=mysqli_fetch_assoc($progress)):?>
                <tr>
                  <td>
                    <b><?=h($p['student'])?></b>
                  </td>
                  <td><?=h($p['activity_title'])?></td>
                  <td>
                    <div class="bar">
                      <i
                        style="
                          width: <?= $p['progress_percentage'] ?>%;
                        "
                      ></i>
                    </div>
                    <?=$p['progress_percentage']?>%
                  </td>
                  <td><?=$p['score']??'—'?></td>
                  <td><?=h(str_replace('_',' ',$p['status']))?></td>
                  <td>
                    <form
                      method="post"
                      onsubmit="return confirm('Remove this progress record?');"
                    >
                      <input
                        type="hidden"
                        name="csrf_token"
                        value="<?=h($_SESSION['csrf_token'])?>"
                      /><input
                        type="hidden"
                        name="action"
                        value="delete_progress"
                      /><input
                        type="hidden"
                        name="id"
                        value="<?=$p['id']?>"
                      /><button class="icon-button danger">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endwhile;?>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
    <script src="dashboard.js"></script>
  </body>
</html>
