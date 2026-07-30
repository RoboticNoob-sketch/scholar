# Scholarly — Scholarship QR Monitoring System

Capstone project: QR-integrated monitoring for scholarship financial assistance distribution.

- **Web (PHP + AJAX + MySQL/XAMPP):** Admin portal + staff QR scanner
- **Mobile (Flutter):** Student app — show profile/voucher QR, status, history
- **Design:** Scholarly dark theme from your prototype zip

## Quick start (Windows + XAMPP)

### 1. Database

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Import:
   - [`database/schema.sql`](database/schema.sql)
   - [`database/seed.sql`](database/seed.sql)

Or from PowerShell:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root < "C:\Users\mikoa\repos\scholarship-qr-monitor\database\schema.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root scholarly_db < "C:\Users\mikoa\repos\scholarship-qr-monitor\database\seed.sql"
```

### 2. Web app

Copy or symlink the project into XAMPP `htdocs`:

```powershell
New-Item -ItemType Junction -Path "C:\xampp\htdocs\scholarship-qr-monitor" -Target "C:\Users\mikoa\repos\scholarship-qr-monitor"
```

Open: **http://localhost/scholarship-qr-monitor/web/login.php**

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Staff | `staff1` | `password` |
| Student (mobile) | `maria.santos` | `password` |

Edit DB credentials in [`web/config/config.php`](web/config/config.php) if your MySQL user/password differs.

### 3. Flutter mobile app

Install [Flutter SDK](https://docs.flutter.dev/get-started/install/windows) and ensure `flutter doctor` passes.

```powershell
cd C:\Users\mikoa\repos\scholarship-qr-monitor\mobile
flutter create . --project-name scholarly_mobile
flutter pub get
flutter run
```

Update API URL in [`mobile/lib/services/api_service.dart`](mobile/lib/services/api_service.dart):

- **Android emulator:** `http://10.0.2.2/scholarship-qr-monitor/web`
- **Physical phone (same Wi‑Fi):** `http://YOUR_PC_IP/scholarship-qr-monitor/web`

## Main flows

1. **Admin** creates program → enrolls scholars → creates batch → generates vouchers → opens batch
2. **Student** opens Flutter app → shows **Claim Voucher** QR on distribution day
3. **Staff** opens web scanner → scans voucher → optional profile verify → claim recorded
4. **Admin** dashboard/reports update with claimed vs pending counts

## QR formats

- Profile: `SCH|{public_id}|{qr_token}`
- Voucher: `VCH|{voucher_code}`

## Project structure

```
scholarship-qr-monitor/
  database/          SQL schema + seed data
  web/               PHP admin, staff, REST API
  mobile/            Flutter student app
  docs/design/       (optional) copy of prototype assets
```

## Demo data

- Open batch: **1st Sem AY 2025-2026 Distribution** (Maria Santos — pending voucher)
- Maria's voucher QR payload: `VCH|VCH-2026-001-MARIA`

## Capstone defense tips

- Run staff scanner on laptop with webcam at distribution desk
- Student demo on Android phone showing live QR
- Admin dashboard shows real-time claim counts after staff scan
