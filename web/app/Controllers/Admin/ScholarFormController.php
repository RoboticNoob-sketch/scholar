<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

function scholar_course_options(): array
{
    return [
        'BS Information Technology',
        'BS Computer Science',
        'BS Information Systems',
        'BS Business Administration',
        'BS Accountancy',
        'BS Hospitality Management',
        'BS Tourism Management',
        'BS Education',
        'Bachelor of Elementary Education',
        'Bachelor of Secondary Education',
        'BS Civil Engineering',
        'BS Electrical Engineering',
        'BS Mechanical Engineering',
        'BS Agriculture',
        'BS Forestry',
        'BS Environmental Science',
        'BS Biology',
        'BS Mathematics',
        'BS Psychology',
        'BS Criminology',
        'BS Nursing',
        'BS Midwifery',
        'Associate in Computer Technology',
        'Associate in Hotel and Restaurant Management',
    ];
}

function save_scholar_user(PDO $pdo, ?array $scholar, string $username, string $password): void
{
    if ($username === '') {
        return;
    }

    if ($scholar && $scholar['user_id']) {
        if ($password !== '') {
            $pdo->prepare('UPDATE users SET username=?, password_hash=? WHERE id=?')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $scholar['user_id']]);
        } else {
            $pdo->prepare('UPDATE users SET username=? WHERE id=?')->execute([$username, $scholar['user_id']]);
        }
        return;
    }

    $hash = password_hash($password !== '' ? $password : 'password', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, "student", "active")')
        ->execute([$username, $hash]);
    $pdo->prepare('UPDATE scholars SET user_id=? WHERE id=?')
        ->execute([(int) $pdo->lastInsertId(), (int) $scholar['id']]);
}

final class ScholarFormController
{
    public static function handle(PDO $pdo): void
    {
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
    $programIds = array_values(array_unique(array_map('intval', $_POST['program_ids'] ?? [])));
    $removePhoto = isset($_POST['remove_photo']);

    $postId = (int) ($_POST['id'] ?? 0);

    try {
        if ($postId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM scholars WHERE id = ?');
            $stmt->execute([$postId]);
            $scholar = $stmt->fetch();
            if (!$scholar) {
                flash('error', 'Scholar not found.');
                redirect('admin/scholars.php');
            }

            $photoPath = handle_scholar_photo_upload($postId, $scholar['photo_path'] ?? null, $removePhoto);
            $stmt = $pdo->prepare(
                'UPDATE scholars SET student_no=?, first_name=?, last_name=?, course=?, year_level=?, email=?, phone=?, photo_path=?, status=? WHERE id=?'
            );
            $stmt->execute([$studentNo, $firstName, $lastName, $course, $yearLevel, $email, $phone, $photoPath, $status, $postId]);
            save_scholar_user($pdo, $scholar, $username, $password);

            $voucherCount = sync_enrollments_for_scholar($pdo, $postId, $programIds);
            $message = 'Scholar updated.';
            if ($voucherCount > 0) {
                $message .= " $voucherCount voucher(s) synced to open batches.";
            }
            flash('success', $message);
            redirect('admin/scholar-view.php?id=' . $postId);
        }

        $pdo->beginTransaction();
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
            'INSERT INTO scholars (user_id, student_no, first_name, last_name, course, year_level, email, phone, photo_path, qr_token, public_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)'
        )->execute([$userId, $studentNo, $firstName, $lastName, $course, $yearLevel, $email, $phone, $qrToken, $publicId, $status]);
        $newId = (int) $pdo->lastInsertId();

        $photoPath = handle_scholar_photo_upload($newId, null, false);
        if ($photoPath !== null) {
            $pdo->prepare('UPDATE scholars SET photo_path=? WHERE id=?')->execute([$photoPath, $newId]);
        }

        $voucherCount = sync_enrollments_for_scholar($pdo, $newId, $programIds);
        $pdo->commit();

        $message = 'Scholar created.';
        if ($voucherCount > 0) {
            $message .= " $voucherCount voucher(s) synced to open batches.";
        }
        flash('success', $message);
        redirect('admin/scholar-view.php?id=' . $newId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'Could not save scholar. Check student no. and username are unique.');
        redirect($postId > 0 ? 'admin/scholar-form.php?id=' . $postId : 'admin/scholar-form.php');
    }
}

$user = null;
$enrolledIds = [];
$allPrograms = $pdo->query(
    'SELECT id, name, amount FROM scholarship_programs WHERE status="active" ORDER BY name'
)->fetchAll();

if ($scholar && $scholar['user_id']) {
    $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $stmt->execute([$scholar['user_id']]);
    $user = $stmt->fetch();
}
if ($scholar) {
    $stmt = $pdo->prepare('SELECT program_id FROM enrollments WHERE scholar_id = ? AND status = "active"');
    $stmt->execute([(int) $scholar['id']]);
    $enrolledIds = array_map('intval', array_column($stmt->fetchAll(), 'program_id'));
}

$isEdit = (bool) $scholar;
$pageTitle = $isEdit ? 'Edit Scholar' : 'Add Scholar';
$defaults = [
    'student_no' => $scholar['student_no'] ?? '',
    'first_name' => $scholar['first_name'] ?? '',
    'last_name' => $scholar['last_name'] ?? '',
    'course' => $scholar['course'] ?? '',
    'year_level' => $scholar['year_level'] ?? '',
    'email' => $scholar['email'] ?? '',
    'phone' => $scholar['phone'] ?? '',
    'status' => $scholar['status'] ?? 'active',
];
$photoUrl = $isEdit ? scholar_photo_url($scholar['photo_path'] ?? null, (int) $scholar['id']) : null;
$initials = strtoupper(substr($defaults['first_name'], 0, 1) . substr($defaults['last_name'], 0, 1));
$yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate'];
$courses = scholar_course_options();

render_admin_layout($pdo, 'scholars', $pageTitle, function () use (
    $scholar,
    $user,
    $allPrograms,
    $enrolledIds,
    $isEdit,
    $pageTitle,
    $defaults,
    $photoUrl,
    $initials,
    $yearLevels,
    $courses
): void {
    echo '<div class="breadcrumb">Admin / Scholars / ' . ($isEdit ? e(scholar_full_name($scholar)) : 'Add') . '</div>';
    echo '<div class="page-header">';
    echo '<div><h1 class="page-title">' . e($pageTitle) . '</h1>';
    echo '<div class="page-subtitle">' . ($isEdit ? 'Update profile, photo, programs, and mobile access' : 'Register a scholar for programs and distribution') . '</div></div>';
    echo '<div class="table-actions btn-group">';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/scholars.php') . '"><i data-lucide="arrow-left"></i> BACK</a>';
    if ($isEdit) {
        echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/scholar-view.php?id=' . (int) $scholar['id']) . '">VIEW PROFILE</a>';
    }
    echo '</div></div>';

    echo '<div class="scholar-form-layout">';
    echo '<form method="post" enctype="multipart/form-data" class="card form-grid scholar-form-card">';
    if ($isEdit) {
        echo '<input type="hidden" name="id" value="' . (int) $scholar['id'] . '">';
    }

    echo '<div class="form-section">';
    echo '<div class="form-section-head"><div class="card-title"><i data-lucide="id-card"></i> Student identity</div>';
    echo '<p>Official records used on vouchers, reports, and the mobile app.</p></div>';
    echo '<div class="photo-upload">';
    echo '<div class="scholar-avatar-wrap">';
    if ($photoUrl) {
        echo '<img class="photo-preview scholar-avatar" id="photoPreview" src="' . e($photoUrl) . '" alt="Scholar photo" width="96" height="96">';
    } else {
        echo '<div class="photo-preview placeholder scholar-avatar" id="photoPreview">' . e($initials !== '' ? $initials : '?') . '</div>';
    }
    echo '</div>';
    echo '<div class="photo-upload-fields">';
    echo '<label class="field-label" for="photo">Profile photo</label>';
    echo '<input class="input" id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">';
    echo '<p class="field-hint">JPG, PNG, or WebP · max 2 MB · shown on scholar profile for staff verification.</p>';
    if ($photoUrl) {
        echo '<label class="checkbox-row" style="margin-top:10px"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>';
    }
    echo '</div></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label" for="student_no">Student number</label>';
    echo '<input class="input" id="student_no" name="student_no" value="' . e($defaults['student_no']) . '" placeholder="2022-01456" required autocomplete="off"></div>';
    echo '<div><label class="field-label" for="status">Account status</label>';
    echo '<select class="select" id="status" name="status">';
    foreach (['active' => 'Active — can receive vouchers', 'inactive' => 'Inactive — blocked from claims'] as $val => $label) {
        $sel = $defaults['status'] === $val ? ' selected' : '';
        echo '<option value="' . $val . '"' . $sel . '>' . e($label) . '</option>';
    }
    echo '</select></div>';
    echo '</div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label" for="first_name">First name</label>';
    echo '<input class="input" id="first_name" name="first_name" value="' . e($defaults['first_name']) . '" placeholder="Maria" required autocomplete="given-name"></div>';
    echo '<div><label class="field-label" for="last_name">Last name</label>';
    echo '<input class="input" id="last_name" name="last_name" value="' . e($defaults['last_name']) . '" placeholder="Santos" required autocomplete="family-name"></div>';
    echo '</div></div>';

    echo '<div class="form-section">';
    echo '<div class="form-section-head"><div class="card-title"><i data-lucide="graduation-cap"></i> Academic information</div>';
    echo '<p>Shown on the scholar profile and distribution desk.</p></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label" for="course">Course</label>';
    echo '<input class="input" id="course" name="course" list="course-list" value="' . e($defaults['course']) . '" placeholder="BS Information Technology" required>';
    echo '<datalist id="course-list">';
    foreach ($courses as $course) {
        echo '<option value="' . e($course) . '">';
    }
    if ($defaults['course'] !== '' && !in_array($defaults['course'], $courses, true)) {
        echo '<option value="' . e($defaults['course']) . '">';
    }
    echo '</datalist></div>';
    echo '<div><label class="field-label" for="year_level">Year level</label>';
    echo '<select class="select" id="year_level" name="year_level" required>';
    echo '<option value="" disabled' . ($defaults['year_level'] === '' ? ' selected' : '') . '>Select year level</option>';
    foreach ($yearLevels as $level) {
        $sel = $defaults['year_level'] === $level ? ' selected' : '';
        echo '<option value="' . e($level) . '"' . $sel . '>' . e($level) . '</option>';
    }
    if ($defaults['year_level'] !== '' && !in_array($defaults['year_level'], $yearLevels, true)) {
        echo '<option value="' . e($defaults['year_level']) . '" selected>' . e($defaults['year_level']) . '</option>';
    }
    echo '</select></div>';
    echo '</div></div>';

    echo '<div class="form-section">';
    echo '<div class="form-section-head"><div class="card-title"><i data-lucide="book-open"></i> Program enrollment</div>';
    echo '<p>Select scholarship programs for this scholar. Vouchers sync automatically to open batches.</p></div>';
    if ($allPrograms) {
        echo '<div class="program-checklist">';
        foreach ($allPrograms as $program) {
            $checked = in_array((int) $program['id'], $enrolledIds, true) ? ' checked' : '';
            echo '<label class="program-checklist-item">';
            echo '<input type="checkbox" name="program_ids[]" value="' . (int) $program['id'] . '"' . $checked . '>';
            echo '<div><strong>' . e($program['name']) . '</strong><span>' . e(format_money((float) $program['amount'])) . ' grant</span></div>';
            echo '</label>';
        }
        echo '</div>';
    } else {
        echo '<div class="empty-state" style="padding:20px 16px">No active programs yet. <a class="link-action" href="' . base_url('admin/program-form.php') . '">Create a program</a> first.</div>';
    }
    echo '</div>';

    echo '<div class="form-section">';
    echo '<div class="form-section-head"><div class="card-title"><i data-lucide="phone"></i> Contact details</div>';
    echo '<p>Optional — helps staff verify identity at the desk.</p></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label" for="email">Email</label>';
    echo '<input class="input" id="email" type="email" name="email" value="' . e($defaults['email']) . '" placeholder="m.santos@stateu.edu.ph" autocomplete="email"></div>';
    echo '<div><label class="field-label" for="phone">Phone</label>';
    echo '<input class="input" id="phone" type="tel" name="phone" value="' . e($defaults['phone']) . '" placeholder="09XX XXX XXXX" autocomplete="tel"></div>';
    echo '</div></div>';

    echo '<div class="form-section">';
    echo '<div class="form-section-head"><div class="card-title"><i data-lucide="smartphone"></i> Mobile app access</div>';
    echo '<p>Students log in on the Scholarly mobile app to view QR codes and claim status.</p></div>';
    echo '<div><label class="field-label" for="username">Mobile username</label>';
    echo '<input class="input" id="username" name="username" value="' . e($user['username'] ?? '') . '" placeholder="maria.santos" autocomplete="off">';
    echo '<p class="field-hint">Use a unique login name. Leave blank only if this scholar will not use the mobile app yet.</p></div>';
    echo '<div><label class="field-label" for="password">Password</label>';
    echo '<input class="input" id="password" type="password" name="password" autocomplete="new-password" placeholder="' . ($isEdit ? 'Leave blank to keep current password' : 'Leave blank for default: password') . '">';
    echo '<p class="field-hint">Share credentials securely. Scholars should change their password after first login.</p></div>';
    echo '</div>';

    echo '<div class="form-actions">';
    echo '<button class="btn btn-primary btn-md" type="submit">' . ($isEdit ? 'SAVE CHANGES' : 'CREATE SCHOLAR') . '</button>';
    echo '<a class="btn btn-ghost btn-md" href="' . base_url($isEdit ? 'admin/scholar-view.php?id=' . (int) $scholar['id'] : 'admin/scholars.php') . '">Cancel</a>';
    echo '</div>';
    echo '</form>';

    echo '<aside class="form-aside">';
    if ($isEdit) {
        echo '<div class="card"><div class="card-title"><i data-lucide="hash"></i> System IDs</div>';
        echo '<div class="stat-row"><span>Public ID</span><span>' . e($scholar['public_id']) . '</span></div>';
        echo '<div class="stat-row"><span>Scholar ID</span><span>#' . (int) $scholar['id'] . '</span></div>';
        echo '<p class="field-hint" style="margin-top:12px">Profile QR codes stay valid when you edit name, photo, or course.</p></div>';
    } else {
        echo '<div class="card hint-box">';
        echo '<strong style="display:block;margin-bottom:8px;color:var(--text-primary)">Quick setup</strong>';
        echo '1. Fill identity and academic info<br>2. Check program enrollment<br>3. Add mobile username<br>4. Save — vouchers sync to open batches';
        echo '</div>';
    }
    echo '<div class="card hint-box">';
    echo '<strong style="display:block;margin-bottom:8px;color:var(--text-primary)">Program enrollment</strong>';
    echo 'Checking a program here enrolls the scholar immediately. If a batch is already open, pending vouchers are created automatically.';
    echo '</div>';
    echo '<div class="card hint-box">';
    echo '<strong style="display:block;margin-bottom:8px;color:var(--text-primary)">Distribution checklist</strong>';
    echo 'Set status to <strong>Inactive</strong> to block claims without deleting history.';
    echo '</div>';
    echo '</aside>';
    echo '</div>';

    echo '<script>';
    echo 'document.getElementById("photo")?.addEventListener("change", function (event) {';
    echo '  const file = event.target.files && event.target.files[0];';
    echo '  const preview = document.getElementById("photoPreview");';
    echo '  if (!file || !preview) return;';
    echo '  const reader = new FileReader();';
    echo '  reader.onload = function () {';
    echo '    if (preview.tagName === "IMG") { preview.src = reader.result; }';
    echo '    else {';
    echo '      const img = document.createElement("img");';
    echo '      img.id = "photoPreview";';
    echo '      img.className = "photo-preview scholar-avatar";';
    echo '      img.width = 96;';
    echo '      img.height = 96;';
    echo '      img.alt = "Scholar photo preview";';
    echo '      img.src = reader.result;';
    echo '      preview.replaceWith(img);';
    echo '    }';
    echo '  };';
    echo '  reader.readAsDataURL(file);';
    echo '});';
    echo '</script>';
});

    }
}
