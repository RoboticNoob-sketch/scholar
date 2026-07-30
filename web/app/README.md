# Web application structure

```
web/
├── admin/                 # Thin page controllers (URLs stay here for Hostinger)
├── api/
│   ├── admin/datatables/  # Server-side DataTables JSON endpoints
│   └── ...                # Mobile/staff JSON API (unchanged paths)
├── app/
│   └── Queries/           # SQL/query layer used by controllers and APIs
├── views/
│   ├── admin/             # HTML templates (no business logic)
│   └── partials/          # Reusable fragments (datatable shell, etc.)
├── includes/              # Bootstrap, auth, layout, helpers, export
└── assets/                # CSS, JS, uploads
```

**Flow:** `admin/*.php` → loads data via `App\Queries\*` → renders `views/admin/*` via `render_admin_page()`.  
List tables fetch rows through `api/admin/datatables/*.php` (server-side DataTables).
