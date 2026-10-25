# Google Sign-In for the Mobile App

Google sign-in for mobile students requires a Google Cloud project, a database migration, and a Web OAuth client ID. Do not commit `web/config/config.local.php` or paste credentials into chat.

## 1. Configure Google OAuth clients

In Google Cloud Console, configure the OAuth consent screen and add your Google account as a test user while the app is in testing mode.

Create an **Android** OAuth client with:

- Package name: `com.example.scholarly_mobile`
- Debug SHA-1: `8E:17:EE:72:1F:5C:E5:77:4D:13:5C:6E:B9:38:1B:B8:43:78:3A:21`

Create a **Web application** OAuth client as well. The Web client ID is used as the server audience. The mobile login flow does not need a client secret.

## 2. Migrate the database

For an existing database, back it up first. In phpMyAdmin, select the target database and import `database/migrate-student-applications.sql` once. This idempotent migration adds the Google identity fields and student application table if they are missing. Do not import the full schema or demo seed into an existing production database.

## 3. Configure PHP

Create `web/config/config.local.php` if it does not exist, preserving any local database settings already in that file. Add the Web client ID:

```php
'google_oauth' => [
    'client_id' => 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
],
```

The PHP cURL and OpenSSL extensions must be enabled so the server can retrieve Google's signing certificates and verify ID tokens.

## 4. Run the app

The public Web client ID is the app's default. From the `mobile` directory, start or reinstall it normally:

```powershell
flutter run
```

To override the default, pass `--dart-define=GOOGLE_SERVER_CLIENT_ID=...`. Never put the client secret in the app.

## Account behavior

The server validates Google's signed ID token, issuer, audience, expiry, and verified email. It links an active student account by Google subject, existing user email, or matching scholar email. An unmatched verified Google account creates a student login. Admin, staff, and inactive accounts cannot use Google student sign-in.
