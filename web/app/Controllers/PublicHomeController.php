<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

final class PublicHomeController
{
    public static function handle(PDO $pdo): void
    {
        $programs = $pdo->query(
            'SELECT id, name, description, amount, academic_year, semester
             FROM scholarship_programs
             WHERE status = "active"
             ORDER BY academic_year DESC, semester, name'
        )->fetchAll() ?: [];

        $config = \app_config();
        $google = $config['google_oauth'] ?? [];
        $googleConfigured = !empty($google['client_id'])
            && !empty($google['client_secret'])
            && is_file(dirname(__DIR__, 2) . '/auth/google-start.php');

        $mobileDownloadUrl = trim((string) ($config['mobile_download_url'] ?? ''));
        if (!str_starts_with(strtolower($mobileDownloadUrl), 'https://')
            || !filter_var($mobileDownloadUrl, FILTER_VALIDATE_URL)) {
            $mobileDownloadUrl = '';
        }

        \render_public_layout('Scholarship opportunities', static function () use ($programs, $googleConfigured, $mobileDownloadUrl): void {
            \view('public.home', [
                'programs' => $programs,
                'googleConfigured' => $googleConfigured,
                'mobileDownloadUrl' => $mobileDownloadUrl,
            ]);
        }, ['landing' => true]);
    }
}
