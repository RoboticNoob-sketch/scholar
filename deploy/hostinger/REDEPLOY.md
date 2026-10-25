# Hostinger Git redeploy checklist

Domain: **https://scholarship-qr-monitor.online**

## One-time setup (after first Git pull)

1. **Back up the database** before making schema changes. For an existing installation, do not re-import the full schema or demo seed; check `DEPLOY.md` and apply only migrations that are not already present.
2. **Create admin user** only if the database does not already have one (see `DEPLOY.md`).
3. **Create private production config** from `web/config/config.local.php.example`, enter the Hostinger DB credentials, OAuth client ID/secret and callback URL, and choose a writable private document directory outside `public_html`. Upload it as:
   ```
   public_html/web/config/config.local.php
   ```
  This file is gitignored. Never commit or replace it with placeholders. Keep any existing valid Hostinger config and add `mobile_download_url` only when a signed release APK is hosted over HTTPS.
4. **Enable SSL** in hPanel → Websites → SSL.

## Every redeploy (Git pull)

1. Back up the database and note the current deployed commit.
2. hPanel → **Websites** → **Git** → **Pull** (or Deploy).
3. Confirm `public_html/.htaccess`, `public_html/index.php`, and `public_html/web/config/config.local.php` still exist.
4. Database migrations run automatically on the first request after the pull (see `database/migrations/`). Back up first; do not run them manually.
5. Test the homepage, Google registration, student API, and staff login over HTTPS.

## Expected folder layout after Git pull

```
public_html/              ← Git repo root
  .htaccess               ← routes domain → web/
  index.php
  web/
    login.php
    admin/
    api/
    config/
      config.local.php    ← you upload this once manually
  database/                 ← blocked from web access
  mobile/                   ← blocked from web access
```

## URLs

| Page | URL |
|------|-----|
| Login | https://scholarship-qr-monitor.online/login.php |
| Home | https://scholarship-qr-monitor.online/ |
| Admin | https://scholarship-qr-monitor.online/admin/dashboard.php |
| Mobile API | https://scholarship-qr-monitor.online/api/... |

## If you still see 403

- Delete any duplicate files you moved manually into `public_html/` (keep the Git layout above)
- Ensure PHP version is **8.1+** in hPanel
- Check `web/config/config.local.php` exists on the server

## Mobile app

Server URL: `https://scholarship-qr-monitor.online`

The landing-page APK link remains a placeholder until `mobile_download_url` is set in the private production config to a versioned HTTPS APK. Sign release APKs with a private release keystore; do not distribute debug-signed builds.
