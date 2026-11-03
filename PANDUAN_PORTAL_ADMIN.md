# Teman Baik — Portal Super Admin (terhubung ke API backend)

Backend (`teman_baik_Backend`) **tidak diubah**. Seluruh penyesuaian ada di `Teman_baik_Frontend`.

## 1. Pengaturan backend (`teman_baik_Backend/.env`)

Salin `.env.example` menjadi `.env`, isi koneksi database, lalu pastikan baris berikut ada
(wajib agar session cookie antara `localhost:5173` dan `localhost:8000` berfungsi):

```
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
SESSION_DRIVER=file
```

```
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve          # http://localhost:8000
```

Akun Super Admin bawaan dibuat oleh `database/seeders/DatabaseSeeder.php`
(username `admin.dpmptsp`, kata sandi ada di file seeder tersebut).
**Segera ganti kata sandi** lewat menu Profil & Pengaturan Akun di portal admin.

> Email "Teruskan ke Instansi" memakai `MAIL_MAILER`. Bawaannya `log` (hanya ditulis ke
> `storage/logs`). Isi konfigurasi SMTP agar email benar-benar terkirim.

## 2. Pengaturan frontend

`Teman_baik_Frontend/.env`:

```
VITE_API_BASE_URL=http://localhost:8000/api
```

```
npm install
npm run dev                # http://localhost:5173  (buka dengan "localhost", bukan 127.0.0.1)
```

## 3. Alamat halaman

| Halaman | Alamat |
|---|---|
| Login masyarakat / Beranda / Aduan / Cek & Detail laporan | `/`, `/beranda`, `/aduan`, `/cek-laporan`, `/detail-laporan` |
| Login Super Admin | `/admin/login` |
| Ringkasan & Dashboard | `/admin` |
| Filter & Rekap Pengaduan | `/admin/pengaduan` |
| Kelola Akun Admin | `/admin/akun` |
| Profil & Pengaturan Akun | `/admin/profil` |

## 4. Endpoint backend yang dipakai portal admin

| Fitur | Endpoint |
|---|---|
| Login / logout / sesi | `POST /admin/login`, `POST /admin/logout`, `GET /admin/me` |
| Ganti kata sandi | `POST /admin/ganti-password` |
| Statistik dashboard | `GET /admin/dashboard` |
| Daftar & detail laporan | `GET /admin/pengaduan`, `GET /admin/pengaduan/{tiket}` |
| Verifikasi / tolak / teruskan / balas | `POST /admin/pengaduan/{tiket}/verifikasi, tolak, teruskan, balasan` |
| Unduh bukti & lampiran | `GET /admin/pengaduan/{tiket}/bukti`, `.../lampiran/{id}` |
| Ekspor CSV | `GET /admin/export` |
| Akun admin instansi | `GET/POST /admin/users`, `PUT/DELETE /admin/users/{id}` |
| Data instansi | `GET /admin/instansi` |

Catatan: portal ini khusus peran `super_admin`. Akun `admin_instansi` akan ditolak saat login.
