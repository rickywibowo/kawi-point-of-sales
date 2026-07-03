# Tahap 32 - Login & Session UX

## Tujuan

Membuat dashboard bisa login ke API langsung dari browser tanpa perlu mengisi token manual di `localStorage`.

## Frontend

- Header dashboard menampilkan tombol `Login` saat session belum connected.
- Panel login sederhana ditambahkan dengan field email dan password.
- Credential demo default:
  - `owner@kawi.test`
  - `password`
- Setelah login berhasil, token dan tenant context disimpan melalui API client.
- Dashboard menjalankan hydrate API setelah session connected.
- Tombol `Logout` ditampilkan saat session connected.
- Logout memanggil endpoint API, membersihkan token dan tenant context, lalu kembali ke mode demo.
- Quick stats diubah menjadi computed agar angka dashboard ikut berubah setelah store hydrate dari API.

## Store & API Client

- `foundation` store menambahkan state:
  - `isLoadingSession`
  - `loginError`
- `foundation` store menambahkan action:
  - `logout()`
- API client menambahkan `clearApiSession()`.

## Verifikasi

- Frontend build memastikan panel login, session status, dan computed quick stats valid.
- Full Laravel test suite tetap dijalankan untuk memastikan auth/session API tidak regresi.
