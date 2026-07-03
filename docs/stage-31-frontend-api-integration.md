# Tahap 31 - Frontend API Integration Foundation

## Tujuan

Memulai transisi dashboard Vue dari data demo Pinia ke data API nyata, tanpa membuat dashboard kosong ketika user belum login.

## Frontend

- Service `resources/js/services/api.js` dibuat sebagai API client kecil.
- API client mendukung:
  - `VITE_API_BASE_URL`
  - Bearer token dari `localStorage.kawi_api_token`
  - Tenant header `X-Business-Id`
  - Branch header `X-Branch-Id`
  - error object `ApiError`
- `foundation` store menambahkan:
  - `login()`
  - `loadSession()`
  - `apiStatus`
  - `apiMessage`
  - tenant context persistence
- Store berikut mendapat action `loadFromApi()`:
  - master data
  - inventory
  - POS
  - purchasing
  - accounting
  - reports
  - customers
  - user access
  - audit

## Dashboard

- `App.vue` memanggil `loadDashboard()` saat mounted.
- Jika belum login atau tenant context belum siap, dashboard tetap memakai data demo.
- Tombol `Connect Demo API` ditambahkan untuk login demo memakai `owner@kawi.test`.
- Setelah login berhasil, dashboard hydrate dari endpoint API yang sudah ada.

## Catatan

Tahap ini belum membuat form login penuh. Fokusnya adalah fondasi API client dan hydrate store agar tahap berikutnya bisa membangun autentikasi UI, routing, dan halaman modul nyata di atas pola yang sama.
