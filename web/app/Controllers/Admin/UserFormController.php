<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;
use Throwable;

function count_active_admins(PDO $pdo, ?int $excludeId = null): int
{
    $sql = 'SELECT COUNT(*) FROM users WHERE role = "admin" AND status = "active"';
    $params = [];
    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

final class UserFormController
{
    public static function handle(PDO $pdo): void
    {
        $currentAdmin = require_role($pdo, ['admin']);
        $id = (int) ($_GET['id'] ?? 0);
        $user = null;
        $scholar = null;

        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) {
                flash('error', 'User not found.');
                redirect('admin/users.php');
            }
            if ($user['role'] === 'student') {
                $stmt = $pdo->prepare('SELECT id, student_no, first_name, last_name FROM scholars WHERE user_id = ?');
                $stmt->execute([$id]);
                $scholar = $stmt->fetch();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postId = (int) ($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'staff';
            $status = $_POST['status'] ?? 'active';

            if ($username === '') {
                flash('error', 'Username is required.');
                redirect($postId > 0 ? 'admin/user-form.php?id=' . $postId : 'admin/user-form.php');
            }
            if (!in_array($role, ['admin', 'staff', 'student'], true)) {
                flash('error', 'Invalid role selected.');
                redirect($postId > 0 ? 'admin/user-form.php?id=' . $postId : 'admin/user-form.php');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                flash('error', 'Invalid status selected.');
                redirect($postId > 0 ? 'admin/user-form.php?id=' . $postId : 'admin/user-form.php');
            }

            if ($postId > 0) {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$postId]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    flash('error', 'User not found.');
                    redirect('admin/users.php');
                }

                if ((int) $existing['id'] === (int) $currentAdmin['id'] && $status === 'inactive') {
                    flash('error', 'You cannot deactivate your own account.');
                    redirect('admin/user-form.php?id=' . $postId);
                }
                if ($existing['role'] === 'admin' && ($role !== 'admin' || $status === 'inactive')) {
                    if (count_active_admins($pdo, (int) $existing['id']) < 1) {
                        flash('error', 'At least one active admin account is required.');
                        redirect('admin/user-form.php?id=' . $postId);
                    }
                }
                if ((int) $existing['id'] === (int) $currentAdmin['id'] && $role !== 'admin') {
                    flash('error', 'You cannot change your own role.');
                    redirect('admin/user-form.php?id=' . $postId);
                }

                $dup = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $dup->execute([$username, $postId]);
                if ($dup->fetch()) {
                    flash('error', 'Username is already taken.');
                    redirect('admin/user-form.php?id=' . $postId);
                }

                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET username=?, password_hash=?, role=?, status=? WHERE id=?')
                        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $status, $postId]);
                } else {
                    $pdo->prepare('UPDATE users SET username=?, role=?, status=? WHERE id=?')
                        ->execute([$username, $role, $status, $postId]);
                }

                if ($status === 'inactive') {
                    $pdo->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$postId]);
                }

                audit_log($pdo, (int) $currentAdmin['id'], 'user_updated', 'user', $postId, "Updated user $username");
                flash('success', 'User updated.');
                redirect('admin/users.php');
            }

            if ($password === '') {
                $password = 'password';
            }

            $dup = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $dup->execute([$username]);
            if ($dup->fetch()) {
                flash('error', 'Username is already taken.');
                redirect('admin/user-form.php');
            }

            try {
                $pdo->prepare('INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
                $newId = (int) $pdo->lastInsertId();
                audit_log($pdo, (int) $currentAdmin['id'], 'user_created', 'user', $newId, "Created $role user $username");
                flash('success', 'User created.');
                redirect('admin/users.php');
            } catch (Throwable $e) {
                flash('error', 'Could not create user.');
                redirect('admin/user-form.php');
            }
        }

        render_admin_layout($pdo, 'users', $user ? 'Edit User' : 'Add User', function () use ($user, $scholar): void {
            echo '<div class="breadcrumb">Admin / Users / ' . ($user ? 'Edit' : 'Add') . '</div>';
            echo '<div class="page-header"><h1 class="page-title">' . ($user ? 'Edit User' : 'Add User') . '</h1>';
            if ($user) {
                echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/users.php') . '">BACK TO LIST</a>';
            }
            echo '</div>';

            if ($scholar) {
                echo '<div class="toast toast-success" style="position:static;margin-bottom:16px;">';
                echo '<i data-lucide="link"></i><span>Linked scholar: ' . e($scholar['student_no'] . ' · ' . scholar_full_name($scholar));
                echo ' · <a class="link-action" href="' . base_url('admin/scholar-view.php?id=' . (int) $scholar['id']) . '">View profile</a></span></div>';
            } elseif ($user && $user['role'] === 'student') {
                echo '<div class="alert-error" style="margin-bottom:16px;"><i data-lucide="alert-circle"></i><span>This student account has no linked scholar record. Create one from <a class="link-action" href="' . base_url('admin/scholar-form.php') . '">Add Scholar</a>.</span></div>';
            }

            echo '<form method="post" class="card form-grid form-narrow">';
            if ($user) {
                echo '<input type="hidden" name="id" value="' . (int) $user['id'] . '">';
            }

            echo '<div><label class="field-label">Username</label>';
            echo '<input class="input" name="username" value="' . e($user['username'] ?? '') . '" required autocomplete="off"></div>';

            echo '<div><label class="field-label">Password</label>';
            echo '<input class="input" type="password" name="password" autocomplete="new-password"';
            echo $user ? ' placeholder="Leave blank to keep current password">' : ' placeholder="Leave blank for default: password">';
            echo '</div>';

            echo '<div><label class="field-label">Role</label><select class="select" name="role">';
            foreach (['admin' => 'Admin — full portal access', 'staff' => 'Staff — distribution scanner', 'student' => 'Student — mobile app login'] as $val => $label) {
                $sel = ($user['role'] ?? 'staff') === $val ? ' selected' : '';
                echo '<option value="' . $val . '"' . $sel . '>' . e($label) . '</option>';
            }
            echo '</select></div>';

            echo '<div><label class="field-label">Status</label><select class="select" name="status">';
            foreach (['active', 'inactive'] as $s) {
                $sel = ($user['status'] ?? 'active') === $s ? ' selected' : '';
                echo '<option value="' . $s . '"' . $sel . '>' . ucfirst($s) . '</option>';
            }
            echo '</select></div>';

            if (!$user) {
                echo '<p class="login-hint">Staff and admin accounts can log in on the web portal. Student accounts use the mobile app.</p>';
            }

            echo '<button class="btn btn-primary btn-md" type="submit">' . ($user ? 'SAVE CHANGES' : 'CREATE USER') . '</button>';
            echo '</form>';
        });
    }
}
