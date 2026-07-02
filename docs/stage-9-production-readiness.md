# Tahap 9 - Production Readiness

Status: complete.

## Tujuan

Tahap ini menyiapkan fondasi operasional agar backend Laravel dan frontend Vue lebih mudah dipasang ke hosting terpisah:

- Backend API di Laravel Cloud atau hosting Laravel lain.
- Frontend Vue/Vite di Cloudflare Pages.
- Monitoring sederhana lewat health endpoint publik.
- Environment variable yang jelas untuk build dan release tracking.

## Endpoint Health

Endpoint publik:

```http
GET /api/health
```

Response sukses:

```json
{
  "status": "ok",
  "checks": {
    "app": {
      "status": "ok"
    },
    "database": {
      "status": "ok"
    },
    "runtime": {
      "status": "ok"
    },
    "release": {
      "version": "local",
      "channel": "local"
    }
  }
}
```

Jika koneksi database gagal, endpoint mengembalikan status HTTP `503` dengan status `degraded`.

## Environment Backend

Variabel yang penting untuk production:

- `APP_NAME`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL`
- `FRONTEND_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_STORE`
- `QUEUE_CONNECTION`
- `SESSION_DRIVER`
- `KAWI_APP_VERSION`
- `KAWI_BUILD_SHA`
- `KAWI_RELEASE_CHANNEL`

## Environment Frontend

Variabel yang dibaca saat build Vite:

- `VITE_APP_NAME`
- `VITE_API_BASE_URL`
- `VITE_BACKEND_HEALTH_URL`

Untuk Cloudflare Pages, arahkan `VITE_API_BASE_URL` ke backend production, misalnya:

```env
VITE_API_BASE_URL=https://api.example.com/api
VITE_BACKEND_HEALTH_URL=https://api.example.com/api/health
```

## Checklist Deploy

1. Set environment backend production.
2. Jalankan migration dengan seed awal yang diperlukan.
3. Build frontend dengan `VITE_API_BASE_URL` production.
4. Cek `GET /api/health` setelah deploy backend.
5. Cek login dan tenant header dari frontend.
6. Jalankan smoke test POS: buka shift, buat transaksi, cek laporan.

## Test

Automated test:

- `tests/Feature/Foundation/HealthCheckTest.php`

Command:

```bash
php artisan test
npm run build
```
