<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ApplicationDocumentService
{
    private const PHOTO_MAX_BYTES = 2097152;
    private const FORM_MAX_BYTES = 5242880;

    public static function store(?array $file, string $kind, bool $required): ?string
    {
        if (!in_array($kind, ['photo_2x2', 'enrollment_form'], true)) {
            throw new RuntimeException('Unsupported application document.');
        }
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException($kind === 'photo_2x2' ? 'Upload a square 2x2 photo.' : 'Upload your current enrollment form.');
            }
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('A document upload failed. Choose the file again and retry.');
        }

        $size = (int) ($file['size'] ?? 0);
        $maxSize = $kind === 'photo_2x2' ? self::PHOTO_MAX_BYTES : self::FORM_MAX_BYTES;
        if ($size < 1 || $size > $maxSize) {
            throw new RuntimeException($kind === 'photo_2x2' ? 'The 2x2 photo must be 5 MB or smaller.' : 'The enrollment form must be 10 MB or smaller.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if ($kind === 'photo_2x2') {
            if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
                throw new RuntimeException('The 2x2 photo must be a JPG or PNG image.');
            }
            $dimensions = @getimagesize($file['tmp_name']);
            if (!$dimensions || $dimensions[0] < 300 || $dimensions[1] < 300
                || max($dimensions[0], $dimensions[1]) > 8000
                || abs($dimensions[0] - $dimensions[1]) / max($dimensions[0], $dimensions[1]) > 0.02) {
                throw new RuntimeException('The 2x2 photo must be square and at least 300 by 300 pixels.');
            }
            $extension = $mime === 'image/jpeg' ? 'jpg' : 'png';
        } else {
            $extension = match ($mime) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                default => throw new RuntimeException('The enrollment form must be a PDF, JPG, or PNG file.'),
            };
            if ($mime === 'application/pdf' && file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') {
                throw new RuntimeException('The enrollment form PDF is invalid.');
            }
        }

        $directory = self::directory();
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the private document folder.');
        }
        $resolvedDirectory = realpath($directory);
        $projectRoot = dirname(__DIR__, 3);
        $publicRoot = is_dir($projectRoot . '/web') ? $projectRoot : dirname(__DIR__, 2);
        $resolvedPublicRoot = realpath($publicRoot);
        if ($resolvedDirectory === false || ($resolvedPublicRoot !== false
            && ($resolvedDirectory === $resolvedPublicRoot || str_starts_with($resolvedDirectory, $resolvedPublicRoot . DIRECTORY_SEPARATOR)))) {
            throw new RuntimeException('Private application storage must be outside the public web folder.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('The private document folder is not writable.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Could not save the private document.');
        }
        @chmod($target, 0640);
        return $filename;
    }

    public static function delete(?string $filename): void
    {
        if ($filename === null || !preg_match('/^[a-f0-9]{32}\.(jpg|png|pdf)$/', $filename)) {
            return;
        }
        $path = self::directory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    public static function path(string $filename): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|pdf)$/', $filename)) {
            return null;
        }
        $root = realpath(self::directory());
        $path = realpath(self::directory() . DIRECTORY_SEPARATOR . $filename);
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            return null;
        }
        return $path;
    }

    public static function copyToScholarPhoto(string $filename, int $scholarId): string
    {
        $source = self::path($filename);
        if ($source === null || $scholarId < 1) {
            throw new RuntimeException('The uploaded 2x2 photo is unavailable.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($source) ?: '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new RuntimeException('The uploaded profile photo is invalid.'),
        };

        $directory = scholar_uploads_dir();
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the scholar profile-photo folder.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('The scholar profile-photo folder is not writable.');
        }

        $profileFilename = 'scholar-' . $scholarId . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!copy($source, $directory . DIRECTORY_SEPARATOR . $profileFilename)) {
            throw new RuntimeException('Could not copy the 2x2 photo to the scholar profile.');
        }
        @chmod($directory . DIRECTORY_SEPARATOR . $profileFilename, 0640);
        return $profileFilename;
    }

    private static function directory(): string
    {
        $configured = trim((string) (app_config()['private_application_storage_dir'] ?? ''));
        if ($configured === '') {
            throw new RuntimeException('Private application storage must be configured outside the public web folder.');
        }
        return rtrim($configured, '\\/');
    }
}