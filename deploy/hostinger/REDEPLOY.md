# Hostinger Git redeploy checklist

Domain: **https://scholarship-qr-monitor.online**

## One-time setup (after first Git pull)

1. **Import database** (if not done): phpMyAdmin → `u626072890_scholarship` → import `database/hostinger-schema.sql`
2. **Create admin user** in phpMyAdmin (see `DEPLOY.md`)
3. **Upload production config** — copy `deploy/hostinger/config.local.php` from your PC to the server as:
   ```
   public_html/web/config/config.local.php
   ```
   (This file is not in Git because it contains your DB password.)
4. **Enable SSL** in hPanel → Websites → SSL

## Every redeploy (Git pull)

1. hPanel → **Websites** → **Git** → **Pull** (or Deploy)
2. Confirm `public_html/.htaccess` and `public_html/index.php` exist (repo root)
3. Confirm `public_html/web/config/config.local.php` still exists (Git does not overwrite it if you created it manually)
4. Test: **https://scholarship-qr-monitor.online/login.php**

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
| Admin | https://scholarship-qr-monitor.online/admin/dashboard.php |
| Mobile API | https://scholarship-qr-monitor.online/api/... |

## If you still see 403

- Delete any duplicate files you moved manually into `public_html/` (keep the Git layout above)
- Ensure PHP version is **8.1+** in hPanel
- Check `web/config/config.local.php` exists on the server

## Mobile app

Server URL: `https://scholarship-qr-monitor.online`
