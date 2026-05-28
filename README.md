# JasaKu Beta

Project ini dipisah menjadi dua bagian:

- `.`: Laravel backend, API, admin dashboard, dan provider dashboard Blade.
- `frontend/provider-landing`: React landing page provider yang bisa di-host terpisah.
- `frontend/customer-landing`: React landing page customer yang bisa di-host terpisah.

## Laravel Backend

Jalankan dari root project:

```bash
composer install
php artisan migrate
php artisan serve
```

Server Laravel sudah disiapkan untuk local network lewat `SERVER_HOST=0.0.0.0`
dan `SERVER_PORT=8000` di `.env`. Dari device lain di Wi-Fi/LAN yang sama,
buka:

```text
http://IP-KOMPUTER:8000
```

Di Windows, IP komputer bisa dicek dengan:

```powershell
ipconfig
```

Route utama:

- `/` diarahkan ke `/admin/login`.
- `/admin/*` memakai Blade Laravel.
- `/customer` diarahkan ke React customer landing.
- `/provider/dashboard` memakai Blade Laravel setelah provider login.
- `/api/*` dipakai frontend React dan dokumentasi Scramble.

## React Frontends

Provider landing:

```bash
cd frontend/provider-landing
npm install
npm run dev
```

Customer landing:

```bash
cd frontend/customer-landing
npm install
npm run dev
```

Konfigurasi URL backend ada di:

```text
frontend/provider-landing/.env
frontend/customer-landing/.env
```

Laravel root tidak lagi memakai Vite atau `npm`. Build frontend dilakukan dari folder React masing-masing.
