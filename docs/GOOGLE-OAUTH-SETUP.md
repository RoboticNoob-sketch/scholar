# Google Sign-In Setup

Google credentials are intentionally kept out of the repository. Do not paste the client secret into chat, screenshots, or source files.

## Create the Google OAuth client

1. Open Google Cloud Console and select or create a project.
2. Configure the Google Auth Platform consent screen. Use an external audience for public student accounts and add your own Google account as a test user while the app is in testing mode.
3. Create an OAuth client with application type **Web application**.
4. Add this authorized redirect URI exactly for local XAMPP testing:

   `http://localhost/scholarship-qr-monitor/web/auth/google-callback.php`

   Redirect URIs must match exactly. Register the eventual HTTPS Hostinger callback separately after the production domain is confirmed.

## Store credentials locally

The XAMPP-only file `web/config/config.local.php` is not included in the repository; create it if it is missing. It can contain only this returned array because the app merges it with `config.php`, which already holds the local database settings. Fill in the two Google values from Google Cloud Console:

```php
<?php

declare(strict_types=1);

return [
    'google_oauth' => [
        'client_id' => 'YOUR_CLIENT_ID',
        'client_secret' => 'YOUR_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/scholarship-qr-monitor/web/auth/google-callback.php',
    ],
];
```

`config.local.php` is ignored by Git; never commit it. The app requests only the standard `openid`, `email`, and `profile` scopes. No JavaScript origin or extra Google API is needed for this server-side authorization-code flow.

## Verify local setup

Open `http://localhost/scholarship-qr-monitor/web/`, choose **Apply with Google**, and sign in with an account listed as a test user. Google should return to the registered callback and open the student application portal.

Google sign-in creates or links a student account using Google's verified email. Application review is handled by the SK admin. Application approval does not create scholar enrollment or authorize funding; program and batch financial approvals remain separate.

Student applications require a square 2x2 JPG/PNG photo and an enrollment form (PDF/JPG/PNG). Files are limited to 2 MB and 5 MB respectively, stored outside the public web root, and streamed only to authenticated admins. Before opening the portal to the public, publish the barangay's full privacy notice and set the approved data-retention/contact process for these documents.