<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    App\Services\GoogleOAuthService::complete($pdo);
} catch (Throwable $error) {
    error_log('Google sign-in failed: ' . $error->getMessage());
    http_response_code(400);
    render_public_layout('Google sign-in', static function (): void {
        view('public.google-error', ['message' => 'Google sign-in could not be completed. Please retry or contact the SK office.']);
    });
}