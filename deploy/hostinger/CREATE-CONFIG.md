# Create config.local.php on Hostinger (one time)

Git cannot push `config.local.php` — it contains your database password.

## Fastest: upload from your PC

1. On your PC, open:
   `C:\Users\mikoa\repos\scholarship-qr-monitor\deploy\hostinger\config.local.php`
2. Hostinger File Manager → `public_html/web/config/`
3. Click **Upload** → select that file
4. Done — refresh https://scholarship-qr-monitor.online/health.php

## Or create in File Manager

1. `public_html/web/config/` → **New file** → `config.local.php`
2. Paste:

```php
<?php
return [
    'base_url' => '',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u626072890_scholarship',
        'user' => 'u626072890_slsu',
        'pass' => 'Mikael0727$',
        'charset' => 'utf8mb4',
    ],
];
```

3. Save → test health.php

Delete this note from the server after setup if you copy it there.
