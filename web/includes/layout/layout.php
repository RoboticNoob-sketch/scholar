<?php

declare(strict_types=1);

function user_initials(string $username): string
{
    $parts = preg_split('/[\s._-]+/', strtolower(trim($username))) ?: [];
    $parts = array_values(array_filter($parts));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    $word = $parts[0] ?? 'SL';
    return strtoupper(substr($word, 0, 2));
}

function school_logo_url(): string
{
    return base_url('assets/images/slsu-tiaong-logo.png');
}

function render_school_logo(string $class = 'brand-logo'): void
{
    echo '<img class="' . e($class) . '" src="' . e(school_logo_url()) . '" alt="SLSU Tiaong logo">';
}

function asset_url(string $path): string
{
    $url = base_url($path);
    $full = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    if (is_file($full)) {
        $url .= '?v=' . filemtime($full);
    }
    return $url;
}

function render_head(string $title): void
{
    $app = e(app_config()['app_name']);
    $assets = [
        'assets/design-system/tokens/fonts.css',
        'assets/design-system/tokens/colors.css',
        'assets/design-system/tokens/typography.css',
        'assets/design-system/tokens/spacing.css',
        'assets/design-system/tokens/effects.css',
        'assets/design-system/tokens/base.css',
        'assets/css/app.css',
    ];
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
    echo "  <meta charset=\"utf-8\">\n";
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    echo '  <link rel="icon" type="image/png" href="' . e(school_logo_url()) . '">' . "\n";
    echo "  <title>{$title} · {$app}</title>\n";
    foreach ($assets as $asset) {
        echo '  <link rel="stylesheet" href="' . e(asset_url($asset)) . "\">\n";
    }
    if (page_uses_datatables()) {
        echo '  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">' . "\n";
        echo '  <link rel="stylesheet" href="' . e(asset_url('assets/css/datatables-theme.css')) . "\">\n";
    }
    echo "</head>\n<body>\n";
}

function render_foot(): void
{
    echo '<script src="https://unpkg.com/lucide@latest"></script>' . "\n";
    if (page_uses_datatables()) {
        echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>' . "\n";
        echo '<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>' . "\n";
        echo '<script src="' . e(asset_url('assets/js/admin-tables.js')) . '"></script>' . "\n";
    }
    echo '<script src="' . e(asset_url('assets/js/app.js')) . '"></script>' . "\n";
    echo "</body></html>\n";
}

function render_flash(): void
{
    $success = flash('success');
    $error = flash('error');
    if ($success) {
        echo '<div class="toast toast-success"><i data-lucide="check-circle"></i><span>' . e($success) . '</span></div>';
    }
    if ($error) {
        echo '<div class="toast toast-error"><i data-lucide="alert-circle"></i><span>' . e($error) . '</span></div>';
    }
}

function render_shell_layout(PDO $pdo, array $roles, array $nav, string $active, string $title, string $roleLabel, callable $content): void
{
    $user = require_role($pdo, $roles);
    $initials = e(user_initials($user['username']));

    render_head($title);
    echo '<div class="app-shell' . (in_array('staff', $roles, true) ? ' staff-shell' : '') . '">';
    echo '<div class="sidebar-overlay"></div>';
    echo '<aside class="sidebar">';
    echo '<div class="sidebar-brand">';
    render_school_logo('brand-logo');
    echo '<div><div class="brand">Scholarly<span class="accent">.</span></div>';
    echo '<div class="brand-sub">' . e(app_config()['school_short'] ?? 'SLSU Tiaong') . '</div></div>';
    echo '</div>';
    echo '<nav class="sidebar-nav">';
    foreach ($nav as $key => [$label, $href, $icon]) {
        $class = $key === $active ? 'nav-item active' : 'nav-item';
        echo '<a class="' . $class . '" href="' . base_url($href) . '">';
        echo '<i data-lucide="' . e($icon) . '"></i><span>' . e($label) . '</span></a>';
    }
    echo '</nav>';
    echo '<div class="sidebar-foot"><span class="badge badge-muted">' . e(strtoupper($roleLabel)) . ' PORTAL</span></div>';
    echo '</aside>';

    echo '<div class="main-area">';
    echo '<header class="topbar">';
    echo '<button type="button" class="icon-btn mobile-only" data-sidebar-toggle aria-label="Open menu"><i data-lucide="menu"></i></button>';
    echo '<div class="topbar-spacer"></div>';
    echo '<div class="topbar-user">';
    echo '<div class="avatar">' . $initials . '</div>';
    echo '<div><div class="name">' . e($user['username']) . '</div><div class="role">' . e($roleLabel) . '</div></div>';
    echo '</div>';
    echo '<a class="btn btn-ghost btn-sm" href="' . base_url('logout.php') . '"><i data-lucide="log-out"></i> LOGOUT</a>';
    echo '</header>';
    echo '<main class="page-content">';
    render_flash();
    $content();
    echo '</main></div></div>';
    render_foot();
}

function render_admin_layout(PDO $pdo, string $active, string $title, callable $content): void
{
    $nav = [
        'dashboard' => ['Dashboard', 'admin/dashboard.php', 'layout-dashboard'],
        'scholars' => ['Scholars', 'admin/scholars.php', 'users'],
        'users' => ['Users', 'admin/users.php', 'user-cog'],
        'programs' => ['Programs', 'admin/programs.php', 'graduation-cap'],
        'batches' => ['Batches', 'admin/batches.php', 'package'],
        'reports' => ['Reports', 'admin/reports.php', 'file-bar-chart'],
        'audit' => ['Audit Logs', 'admin/audit.php', 'scroll-text'],
    ];
    render_shell_layout($pdo, ['admin'], $nav, $active, $title, 'Admin', $content);
}

function render_staff_layout(PDO $pdo, string $active, string $title, callable $content): void
{
    $nav = [
        'dashboard' => ['Dashboard', 'staff/dashboard.php', 'layout-dashboard'],
        'scanner' => ['Scanner', 'staff/scanner.php', 'scan-line'],
        'claims' => ["Today's Claims", 'staff/claims.php', 'clipboard-list'],
    ];
    render_shell_layout($pdo, ['staff'], $nav, $active, $title, 'Staff', $content);
}

function render_login_layout(string $title, callable $content): void
{
    render_head($title);
    echo '<div class="login-page">';
    echo '<div class="login-hero">';
    echo '<div class="login-hero-inner">';
    render_school_logo('brand-logo brand-logo-lg');
    echo '<h1>Scholarly<span class="accent">.</span></h1>';
    echo '<p>' . e(app_config()['school_name']) . '</p>';
    echo '<ul class="login-features">';
    echo '<li><i data-lucide="shield-check"></i> Track claims in real time</li>';
    echo '<li><i data-lucide="qr-code"></i> Secure voucher & profile QR</li>';
    echo '<li><i data-lucide="bar-chart-3"></i> Admin reports & audit trail</li>';
    echo '</ul></div></div>';
    echo '<div class="login-panel"><div class="login-card">';
    $content();
    echo '</div></div></div>';
    render_foot();
}
