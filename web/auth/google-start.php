<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    App\Services\GoogleOAuthService::begin();
} catch (RuntimeException $error) {
    http_response_code(503);
    render_public_layout('Google sign-in setup', static function (): void {
        view('public.google-setup', ['redirectUri' => absolute_url('auth/google-callback.php')]);
    });
}