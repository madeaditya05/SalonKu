# JasaKu Provider Landing

React landing page untuk provider/mitra JasaKu. Aplikasi ini berdiri sendiri dan bisa di-host terpisah dari Laravel.

## Local Development

Jalankan Laravel backend dari root project:

```bash
php artisan serve
```

Jalankan React dari folder ini:

```bash
cd frontend/provider-landing
npm install
npm run dev
```

Buka:

```text
http://127.0.0.1:5173
```

## Environment

Copy `.env.example` menjadi `.env` lalu sesuaikan URL backend:

```text
VITE_BACKEND_URL=http://127.0.0.1:8000
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Login mitra melakukan POST ke backend Laravel agar session dashboard Blade dibuat, lalu Laravel mengarahkan user ke `/provider/dashboard`.
