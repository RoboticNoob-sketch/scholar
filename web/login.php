<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND status = "active"');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] === 'student') {
            $error = 'Students should use the Scholarly mobile app to log in.';
        } else {
            login_user($user);
            audit_log($pdo, (int) $user['id'], 'login', 'user', (int) $user['id'], 'User logged in');
            redirect(match ($user['role']) {
                'admin' => 'admin/dashboard.php',
                'staff' => 'staff/dashboard.php',
                default => 'login.php',
            });
        }
    } else {
        audit_log($pdo, null, 'login_failed', 'user', null, 'Web login failed for: ' . $username);
        $error = 'Invalid username or password.';
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'student_web') {
    $error = 'Students should use the Scholarly mobile app to log in.';
}

render_login_layout('Login', function () use ($error): void {
    $school = e(app_config()['school_name']);
    echo '<h2 class="login-title">' . $school . '</h2>';
    echo '<p class="login-subtitle">Scholarship Financial Assistance Monitoring</p>';
    if ($error) {
        echo '<div class="alert-error"><i data-lucide="alert-circle"></i><span>' . e($error) . '</span></div>';
    }
    echo '<form method="post" class="form-grid">';
    echo '<div><label class="field-label">Username</label><input class="input" name="username" required autocomplete="username" placeholder="Enter username"></div>';
    echo '<div><label class="field-label">Password</label><input class="input" type="password" name="password" required autocomplete="current-password" placeholder="Enter password"></div>';
    echo '<p class="login-hint">Admin goes to dashboard. Staff goes to the distribution scanner.</p>';
    echo '<button class="btn btn-primary btn-lg" type="submit">LOG IN</button>';
    echo '<div class="login-demo">Demo accounts · admin / password · staff1 / password</div>';
    echo '</form>';
});
