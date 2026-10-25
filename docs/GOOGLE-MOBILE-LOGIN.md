# Google Sign-In for the Mobile App

Google sign-in for mobile students requires a Google Cloud project, a database migration, and a Web OAuth client ID. Do not commit `web/config/config.local.php` or paste credentials into chat.

## 1. Configure Google OAuth clients

In Google Cloud Console, configure the OAuth consent screen and add your Google account as a test user while the app is in testing mode.

Create an **Android** OAuth client with:

- Package name: `com.example.scholarly_mobile`
- Debug SHA-1: `8E:17:EE:72:1F:5C:E5:77:4D:13:5C:6E:B9:38:1B:B8:43:78:3A:21`

Create a **Web application** OAuth client as well. The Web client ID is used as the server audience. The mobile login flow does not need a client secret.

For a distributed release APK, also create an Android OAuth client in the same Google Cloud project using the release keystore SHA-1. The debug and release certificates are different.

The Android package ID remains `com.example.scholarly_mobile`; use the same Web client ID as `serverClientId` in the app and as the server-side audience. Never put the Web client secret in the APK.

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

The public Web client ID is the app's default, and new installs use the production API. From the `mobile` directory, run:

```powershell
flutter run
```

For local XAMPP testing, override the server with `flutter run --dart-define=API_BASE_URL=http://192.168.1.44`. Never put the Google client secret in the app.

## 5. Prepare a signed release APK

Create a private JKS keystore from the `mobile` directory. `keytool` prompts for passwords; enter them locally and do not share them or commit the keystore:

```powershell
keytool -genkeypair -v -keystore android/app/upload-keystore.jks -storetype JKS -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Copy `android/key.properties.example` to `android/key.properties` and replace its placeholders with the keystore values. Both `key.properties` and `*.jks` are gitignored. Never commit or upload them.

Get the release SHA-1 for Google Cloud using:

```powershell
keytool -list -v -keystore android/app/upload-keystore.jks -alias upload
```

Register that SHA-1 as an Android OAuth client in the same Google Cloud project. Keep the existing Web client ID as the server client ID.

Build the signed release APK:

```powershell
flutter build apk --release
```

The APK is written to `build/app/outputs/flutter-apk/app-release.apk`. Back up the keystore securely; losing it prevents signing future updates with the same app identity.

## Account behavior

The server validates Google's signed ID token, issuer, audience, expiry, and verified email. It links an active student account by Google subject, existing user email, or matching scholar email. An unmatched verified Google account creates a student login. Admin, staff, and inactive accounts cannot use Google student sign-in.
