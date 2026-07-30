# Deploy Scholarly on Hostinger

This guide covers **shared web hosting** (Premium/Business plans with PHP + MySQL). VPS steps are similar but you manage Apache/Nginx yourself.

## What you need

- Hostinger account with a domain (or subdomain)
- FTP/SFTP access or File Manager in hPanel
- PHP **8.1+** (set in hPanel → Advanced → PHP Configuration)
- MySQL database created in hPanel → Databases

## Recommended layout

Upload the **contents of the `web/` folder** directly into `public_html/` so your site lives at the domain root:

```
public_html/
  index.php
  login.php
  admin/
  api/
  config/
  includes/
  ...
```

Your login URL becomes: `https://yourdomain.com/login.php`

**Alternative:** upload `web/` into `public_html/scholarly/` and set `base_url` to `/scholarly` in `config.local.php`.

---

## Step 1 — Create the database

1. hPanel → **Databases** → **MySQL Databases**
2. Create a database (e.g. `u123456789_scholarly`)
3. Create a user with a strong password and assign **All privileges** to that database
4. Note: **Host**, **Database name**, **Username**, **Password** (host is usually `localhost`)

---

## Step 2 — Import tables

1. hPanel → **Databases** → **phpMyAdmin**
2. Select your database in the left sidebar
3. **Import** → choose `database/hostinger-schema.sql` → Go

Do **not** import `database/seed.sql` on production (demo accounts use password `password`).

---

## Step 3 — Create your admin account

On your PC, generate a bcrypt hash:

```powershell
php -r "echo password_hash('YourStrongAdminPassword', PASSWORD_DEFAULT);"
```

In phpMyAdmin → SQL, run:

```sql
INSERT INTO users (username, password_hash, role, status) VALUES
('admin', 'PASTE_HASH_HERE', 'admin', 'active');
```

Change the password immediately after first login via **Admin → Users** if you add that flow, or update the hash in the database.

---

## Step 4 — Upload PHP files

### Option A: File Manager

1. Zip the **`web/` folder contents** (not the repo root)
2. hPanel → **Files** → **File Manager** → `public_html`
3. Upload and extract the zip

### Option B: FTP (FileZilla)

- Host: from hPanel FTP accounts (often `ftp.yourdomain.com`)
- Upload all files from `web/` into `public_html/`

**Do not upload:** `mobile/`, `.git/`, local XAMPP-only files.

---

## Step 5 — Production config

On the server, in `public_html/config/`:

1. Copy `config.local.php.example` → `config.local.php`
2. Edit with your Hostinger DB credentials:

```php
<?php
return [
    'base_url' => '',
    'db' => [
        'host' => 'localhost',
        'name' => 'u123456789_scholarly',
        'user' => 'u123456789_admin',
        'pass' => 'your-real-password',
    ],
];
```

`config.local.php` is gitignored — credentials stay on the server only.

If the site is in a subfolder:

```php
'base_url' => '/scholarly',
```

---

## Step 6 — Enable HTTPS

1. hPanel → **Websites** → your site → **SSL**
2. Install the free SSL certificate
3. Edit `public_html/.htaccess` and **uncomment** the HTTPS redirect lines:

```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Test: `https://yourdomain.com/login.php`

---

## Step 7 — Verify the web app

| Check | URL |
|-------|-----|
| Login page | `https://yourdomain.com/login.php` |
| Admin dashboard | log in as admin |
| API health | `https://yourdomain.com/api/auth/login.php` (POST JSON) |

Common issues:

| Problem | Fix |
|---------|-----|
| 500 error | Check PHP version ≥ 8.1; verify DB credentials in `config.local.php` |
| Blank styles / broken links | Wrong `base_url` — use `''` for domain root |
| Database connection failed | Host must be `localhost` on shared hosting |
| 403 on config | Expected — `config/.htaccess` blocks direct browser access |

---

## Step 8 — Mobile app (Flutter)

Staff and student phones must reach your **HTTPS** API over the internet (not USB localhost).

1. Open the app → expand **Server URL** on the login screen
2. Enter: `https://yourdomain.com` (no trailing slash; no `/web` if you uploaded to root)
3. Log in with production accounts

For a release APK with the URL baked in, change the default in `mobile/lib/screens/login_screen.dart` and rebuild:

```powershell
cd mobile
flutter build apk --release
```

Optional: add your domain to `mobile/android/app/src/main/res/xml/network_security_config.xml` if you use HTTP during testing (production should use HTTPS only).

---

## Security checklist (before defense / go-live)

- [ ] Strong admin password (not `password`)
- [ ] Do not import demo `seed.sql` on production
- [ ] HTTPS enabled and forced
- [ ] `config.local.php` exists only on server
- [ ] Remove or disable unused demo staff/student accounts
- [ ] Optional: set `allowed_origins` in `config.local.php` to restrict API CORS

---

## Updating after deployment

1. Upload changed PHP files via FTP/File Manager
2. If schema changed, run new SQL migrations in phpMyAdmin
3. Clear Hostinger cache if hPanel caching is enabled

---

## Quick reference — local vs production

| Setting | Local (XAMPP) | Hostinger |
|---------|---------------|-----------|
| `base_url` | `/scholarship-qr-monitor/web` | `''` or `/subfolder` |
| DB host | `127.0.0.1` | `localhost` |
| DB user | `root` | `u123456789_...` |
| Mobile URL | `http://127.0.0.1:8080` (USB) | `https://yourdomain.com` |

---

## Need help?

Share your Hostinger plan, domain name, and whether you want the site at the root or in a subfolder — we can tailor `config.local.php` and test the API endpoints with you.
