# Logisync Backend

Sistem manajemen inventory real-time dengan audit trail lengkap dan API terstruktur.

## Fitur Utama

- **Manajemen Barang & Lokasi**: CRUD barang, kategori, dan lokasi penyimpanan dengan QR code generation
- **Stock Opname**: Audit inventori dengan metode per-lokasi, per-kategori, atau manual dengan pencatatan varian stok
- **Transaksi Stok**: Pencatatan masuk/keluar barang dengan validasi ketersediaan dan tracking user
- **Log Aktivitas**: Audit trail otomatis untuk semua perubahan data (Spatie Activity Log) dengan view di admin panel
- **API Terstruktur**: Endpoint authenticated (Sanctum) untuk mobile app dan integrasi eksternal
- **Admin Dashboard**: Filament panel dengan widgets statistik, quick stats, dan navigasi terkelompok

## Stack Teknologi

- **Backend**: Laravel 13 (PHP 8.5)
- **Database**: PostgreSQL
- **Cache/Queue**: Redis
- **Admin UI**: Filament v5 + Livewire v4
- **API Auth**: Laravel Sanctum
- **Activity Log**: Spatie Activity Log v5
- **API Docs**: Scramble (OpenAPI auto-generation)
- **Docker**: Sail (Docker Compose wrapper)
- **Deployment**: Docker image (php:8.5-fpm-alpine multi-stage) via Dokploy

## Setup Lokal

```bash
# Clone & setup
git clone <repo>
cd backend-logisync
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database & cache
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail artisan tinker  # (opsional, seed data)

# Development
vendor/bin/sail npm run dev
vendor/bin/sail artisan serve  # atau buka http://localhost
```

Akses admin: `http://localhost` (Filament panel)  
Akses API docs: `http://localhost/docs/api`

## Struktur Proyek

```
app/
├── Models/              # Eloquent models (User, Item, Transaction, AuditSession, dll)
├── Http/
│   ├── Controllers/Api/ # API endpoints (Auth, Transactions, Locations, Items)
│   ├── Requests/        # Form request validation
│   └── Resources/       # JSON resources
├── Actions/             # Business logic (StartAuditSession, CompleteAuditSession)
├── Filament/
│   ├── Resources/       # Admin CRUD panels + Activity Log resource
│   ├── Pages/           # Custom pages (Dashboard)
│   └── Widgets/         # Stats widgets
└── Providers/           # Service providers

database/
├── migrations/          # Schema
└── seeders/             # (opsional)

routes/
├── api.php             # API routes (auth, transactions, audit, items)
└── web.php             # Web routes (Filament auto-routed)
```

## Testing

```bash
vendor/bin/sail artisan test --compact
```

Coverage: 59 tests (234 assertions) meliputi auth, API, activity logging, Filament resources.

## Deployment

1. Build image dari Docker Hub (CI/CD via GitHub Actions)
2. Deploy via Dokploy dengan env vars:
   - `APP_URL` (HTTPS)
   - `DB_HOST`, `DB_PASSWORD` (external services)
   - `API_DOCS_ENABLED` (gate untuk /docs/api)
   - `SANCTUM_EXPIRATION` (token lifetime)
3. Jalankan migrasi: `php artisan migrate --force`
4. Scheduler untuk cleanup: `activitylog:clean` (daily, retention 90 hari)

## API Highlights

- **POST /api/auth/login** – Login dengan email/password, return token Sanctum
- **POST /api/transactions/in|out** – Catat masuk/keluar barang
- **GET /api/audit-sessions** – Daftar sesi opname dengan status
- **POST /api/audit-sessions/{id}/scan** – Scan barang dalam opname
- **GET /api/items/lookup** – Cari barang by SKU
- Semua endpoint protected dengan `auth:sanctum` middleware

## Admin Panel (Filament)

Navigation groups:
- **Master Data**: Barang, Kategori, Lokasi
- **Stock Opname**: Sesi opname, Item opname
- **Riwayat & Logs**: Transaksi, Log Aktivitas
- **Standalone**: Pengguna

Dashboard menampilkan stats real-time (total barang, stok per-lokasi, transaksi hari ini, variance audit).

## Security

- Token API expire 24 jam (configurable)
- Activity log semua perubahan untuk audit
- Role-based access (admin / gudang)
- Proxy trust untuk HTTPS di behind load balancer
- API docs hanya access admin / local / `API_DOCS_ENABLED=true`

## Lisensi

Internal project.
