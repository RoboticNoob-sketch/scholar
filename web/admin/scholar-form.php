<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$scholar = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM scholars WHERE id = ?');
    $stmt->execute([$id]);
    $scholar = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role($pdo, ['admin']);
    $studentNo = trim($_POST['student_no'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $yearLevel = trim($_POST['year_level'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $postId = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($postId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM scholars WHERE id = ?');
        $stmt->execute([$postId]);
        $scholar = $stmt->fetch();
        if (!$scholar) {
            flash('error', 'Scholar not found.');
            redirect('admin/scholars.php');
        }
        $id = $postId;

        $stmt = $pdo->prepare(
            'UPDATE scholars SET student_no=?, first_name=?, last_name=?, course=?, year_level=?, email=?, phone=?, status=? WHERE id=?'
        );
        $stmt->execute([$studentNo, $firstName, $lastName, $course, $yearLevel, $email, $phone, $status, $id]);
        if ($scholar['user_id'] && $username !== '') {
            if ($password !== '') {
                $pdo->prepare('UPDATE users SET username=?, password_hash=? WHERE id=?')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $scholar['user_id']]);
            } else {
                $pdo->prepare('UPDATE users SET username=? WHERE id=?')->execute([$username, $scholar['user_id']]);
            }
        } elseif (!$scholar['user_id'] && $username !== '') {
            $hash = password_hash($password !== '' ? $password : 'password', PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, "student", "active")')
                ->execute([$username, $hash]);
            $pdo->prepare('UPDATE scholars SET user_id=? WHERE id=?')->execute([(int) $pdo->lastInsertId(), $id]);
        }
        flash('success', 'Scholar updated.');
        redirect('admin/scholar-view.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $userId = null;
        if ($username !== '') {
            $hash = password_hash($password !== '' ? $password : 'password', PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, "student", "active")')
                ->execute([$username, $hash]);
            $userId = (int) $pdo->lastInsertId();
        }
        $publicId = generate_public_id($pdo);
        $qrToken = generate_token(32);
        $pdo->prepare(
            'INSERT INTO scholars (user_id, student_no, first_name, last_name, course, year_level, email, phone, qr_token, public_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $studentNo, $firstName, $lastName, $course, $yearLevel, $email, $phone, $qrToken, $publicId, $status]);
        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();
        flash('success', 'Scholar created.');
        redirect('admin/scholar-view.php?id=' . $newId);
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Could not save scholar. Check student no. and username are unique.');
        redirect('admin/scholar-form.php');
    }
}

$user = null;
if ($scholar && $scholar['user_id']) {
    $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $stmt->execute([$scholar['user_id']]);
    $user = $stmt->fetch();
}

render_admin_layout($pdo, 'scholars', $scholar ? 'Edit Scholar' : 'Add Scholar', function () use ($scholar, $user): void {
    echo '<div class="page-header"><h1 class="page-title">' . ($scholar ? 'Edit Scholar' : 'Add Scholar') . '</h1></div>';
    echo '<form method="post" class="card form-grid form-narrow">';
    if ($scholar) {
        echo '<input type="hidden" name="id" value="' . (int) $scholar['id'] . '">';
    }
    $fields = [
        'student_no' => $scholar['student_no'] ?? '',
        'first_name' => $scholar['first_name'] ?? '',
        'last_name' => $scholar['last_name'] ?? '',
        'course' => $scholar['course'] ?? '',
        'year_level' => $scholar['year_level'] ?? '',
        'email' => $scholar['email'] ?? '',
        'phone' => $scholar['phone'] ?? '',
    ];
    foreach ($fields as $name => $value) {
        $label = ucwords(str_replace('_', ' ', $name));
        echo '<div><label class="field-label">' . e($label) . '</label><input class="input" name="' . e($name) . '" value="' . e($value) . '" required></div>';
    }
    echo '<div><label class="field-label">Status</label><select class="select" name="status">';
    foreach (['active', 'inactive'] as $s) {
        $sel = ($scholar['status'] ?? 'active') === $s ? ' selected' : '';
        echo '<option value="' . $s . '"' . $sel . '>' . ucfirst($s) . '</option>';
    }
    echo '</select></div>';
    echo '<div><label class="field-label">Mobile login username</label><input class="input" name="username" value="' . e($user['username'] ?? '') . '"></div>';
    echo '<div><label class="field-label">Password (leave blank to keep)</label><input class="input" type="password" name="password"></div>';
    echo '<button class="btn btn-primary btn-md" type="submit">SAVE SCHOLAR</button>';
    echo '</form>';
});
