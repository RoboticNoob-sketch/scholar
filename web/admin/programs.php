<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role($pdo, ['admin']);
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $academicYear = trim($_POST['academic_year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $scholarIds = array_map('intval', $_POST['scholar_ids'] ?? []);

    if ($id > 0) {
        $pdo->prepare(
            'UPDATE scholarship_programs SET name=?, description=?, amount=?, academic_year=?, semester=?, status=? WHERE id=?'
        )->execute([$name, $description, $amount, $academicYear, $semester, $status, $id]);
        $message = 'Program updated.';
    } else {
        $pdo->prepare(
            'INSERT INTO scholarship_programs (name, description, amount, academic_year, semester, status) VALUES (?,?,?,?,?,?)'
        )->execute([$name, $description, $amount, $academicYear, $semester, $status]);
        $id = (int) $pdo->lastInsertId();
        $message = 'Program created.';
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE enrollments SET status="removed" WHERE program_id=? AND status="active"')->execute([$id]);
    }

    if ($scholarIds) {
        $insert = $pdo->prepare(
            'INSERT INTO enrollments (scholar_id, program_id, status) VALUES (?, ?, "active")
             ON DUPLICATE KEY UPDATE status="active"'
        );
        foreach ($scholarIds as $sid) {
            $insert->execute([$sid, $id]);
        }
    }

    if ($id > 0) {
        $voucherCount = sync_vouchers_for_program($pdo, $id);
        if ($voucherCount > 0) {
            $message .= " $voucherCount voucher(s) added to open distribution batch(es).";
        }
    }

    flash('success', $message);
    redirect('admin/programs.php');
}

render_admin_page($pdo, 'programs', 'Programs', 'admin/programs/index', [], ['datatables' => true]);
