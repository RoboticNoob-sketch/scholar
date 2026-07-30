<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$programId = (int) ($_GET['program_id'] ?? 0);
$programs = $pdo->query('SELECT id, name FROM scholarship_programs WHERE status = "active" ORDER BY name')->fetchAll();

render_admin_page($pdo, 'scholars', 'Scholars', 'admin/scholars/index', [
    'programs' => $programs,
    'programId' => $programId,
], ['datatables' => true]);
