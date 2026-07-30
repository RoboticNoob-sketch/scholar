<?php

declare(strict_types=1);

function view(string $name, array $data = []): void
{
    $path = dirname(__DIR__) . '/views/' . str_replace('.', '/', $name) . '.php';
    if (!is_file($path)) {
        throw new RuntimeException('View not found: ' . $name);
    }

    extract($data, EXTR_SKIP);
    require $path;
}

function render_admin_page(PDO $pdo, string $active, string $title, string $view, array $data = [], array $options = []): void
{
    $GLOBALS['page_assets'] = $options;
    render_admin_layout($pdo, $active, $title, static function () use ($view, $data): void {
        view($view, $data);
    });
    unset($GLOBALS['page_assets']);
}

function page_assets(): array
{
    return $GLOBALS['page_assets'] ?? [];
}

function page_uses_datatables(): bool
{
    return !empty(page_assets()['datatables']);
}
