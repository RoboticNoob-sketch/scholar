# Web application structure

```
web/
├── admin/                      # Thin URL entry shims → Controllers
├── staff/                      # Thin URL entry shims → Controllers
├── api/
│   ├── admin/datatables/       # Server-side DataTables JSON endpoints
│   └── ...                     # Mobile/staff JSON API (unchanged paths)
├── app/
│   ├── Controllers/
│   │   ├── Admin/              # Page logic (dashboard, lists, forms, detail views)
│   │   └── Staff/              # Staff portal pages
│   └── Queries/                # SQL/query layer used by controllers and APIs
├── views/
│   ├── admin/                  # HTML templates (no business logic)
│   └── partials/               # Reusable fragments (datatable shell, etc.)
├── includes/
│   ├── bootstrap.php           # Single entry: session, autoload, PDO, helpers
│   ├── core/                   # autoload, db, helpers, auth
│   ├── layout/                 # layout.php, view.php (render_admin_page)
│   ├── support/                # pagination, datatables helpers
│   └── export/                 # CSV/PDF report templates and exporters
└── assets/                     # CSS, JS, uploads
```

**Flow:** `admin/*.php` shim → `App\Controllers\Admin\*::handle($pdo)` → loads data via `App\Queries\*` → renders `views/admin/*` via `render_admin_page()`.  
List tables fetch rows through `api/admin/datatables/*.php` (server-side DataTables).

Form and detail pages still use `render_admin_layout()` inline HTML until migrated to views.
