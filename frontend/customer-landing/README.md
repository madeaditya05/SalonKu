# JasaKu Customer Landing

React landing page untuk customer JasaKu/GlowHub. Aplikasi ini berdiri sendiri dan bisa di-host terpisah dari Laravel.

## Local Development

Jalankan Laravel backend dari root project:

```bash
php artisan serve --host=0.0.0.0
```

Jalankan React dari folder ini:

```bash
cd frontend/customer-landing
npm install
npm run dev
```

Buka:

```text
http://127.0.0.1:5174
```

Untuk akses dari perangkat lain di jaringan lokal yang sama, buka:

```text
http://IP-LAN-KOMPUTER:5174
```

Saat dibuka lewat IP LAN, frontend otomatis mencoba backend di `http://IP-LAN-KOMPUTER:8000`.

## Environment

Copy `.env.example` menjadi `.env` lalu sesuaikan URL backend:

```text
VITE_BACKEND_URL=http://127.0.0.1:8000
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_PROVIDER_FRONTEND_URL=http://127.0.0.1:5173
```

Jika testing dari perangkat lain, ganti `127.0.0.1` dengan IP LAN komputer server.

Landing ini membaca API publik Laravel seperti `/api/categories`, `/api/services`, dan `/api/providers`.

Auth customer memakai API Laravel:

```text
POST /api/auth/login
POST /api/auth/register/customer
POST /api/auth/logout
```
