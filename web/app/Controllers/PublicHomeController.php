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
        $googleConfigured = \App\Services\GoogleOAuthService::configured();

        render_public_layout('Scholarship opportunities', static function () use ($programs, $googleConfigured): void {
            view('public.home', ['programs' => $programs, 'googleConfigured' => $googleConfigured]);
        });
    }
}