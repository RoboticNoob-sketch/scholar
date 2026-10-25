<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND status = "active"');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] === 'student') {
            $error = 'Students use Google sign-in from the public programs page for applications, and the mobile app for voucher claims.';
        } else {
            login_user($user);
            audit_log($pdo, (int) $user['id'], 'login', 'user', (int) $user['id'], 'User logged in');
            redirect(match ($user['role']) {
                'admin' => 'admin/dashboard.php',
                'approver' => 'admin/approvals.php',
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
    $error = 'Students use Google sign-in from the public programs page for applications, and the mobile app for voucher claims.';
}

render_login_layout('Login', function () use ($error): void {
    echo '<p class="lg-eyebrow">Staff portal</p>';
    echo '<h2>Welcome back</h2>';
    echo '<p class="lg-sub">Sign in to manage scholars, approvals and claims.</p>';
    if ($error) {
        echo '<div class="lg-alert" role="alert"><i data-lucide="alert-circle"></i><span>' . e($error) . '</span></div>';
    }
    echo '<form method="post" class="lg-form">';
    echo csrf_field();
    echo '<label class="lg-field"><span>Username</span><div class="lg-input"><i data-lucide="user"></i><input name="username" required autocomplete="username" placeholder="Enter username" autofocus></div></label>';
    echo '<label class="lg-field"><span>Password</span><div class="lg-input"><i data-lucide="lock"></i><input type="password" name="password" required autocomplete="current-password" placeholder="Enter password" data-pw><button type="button" class="lg-eye" data-pw-toggle aria-label="Show password"><i data-lucide="eye"></i></button></div></label>';
    echo '<button class="lg-submit" type="submit">Log in <i data-lucide="arrow-right"></i></button>';
    echo '<p class="lg-note"><i data-lucide="info"></i> Admins manage records, approvers review requests, and staff scan claims. Students sign in with Google on the programs page or use the mobile app.</p>';
    echo '</form>';
});
