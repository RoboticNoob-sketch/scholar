# Create `config.local.php` on Hostinger

This file is private server configuration. Do not commit it, paste its secrets into chat, or store it in the repository.

1. In Hostinger File Manager, open `public_html/web/config/`.
2. Create `config.local.php` if it does not already exist. If it exists, preserve the current database values and add or update only the required settings.
3. Use this shape, replacing every placeholder with values from Hostinger and Google Cloud:

```php
<?php

declare(strict_types=1);

return [
    'base_url' => '',
    'mobile_download_url' => '',
    'private_application_storage_dir' => '/home/ACCOUNT/private/scholarly-application-documents',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'user' => 'YOUR_DATABASE_USER',
        'pass' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'google_oauth' => [
        'client_id' => 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'YOUR_WEB_CLIENT_SECRET',
        'redirect_uri' => 'https://scholarship-qr-monitor.online/auth/google-callback.php',
    ],
];
```

The private document directory must be writable by PHP and outside `public_html`. Keep `mobile_download_url` empty until a release-signed APK is hosted at a versioned HTTPS URL.

**Credential safety:** the former version of this guide contained a database password. Rotate that password in Hostinger, update the server-only `config.local.php`, and remove the exposed value from the current Git history before making the repository public or sharing it further.
