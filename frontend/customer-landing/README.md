# JasaKu Customer Landing

React landing page untuk customer JasaKu/GlowHub. Aplikasi ini berdiri sendiri dan bisa di-host terpisah dari Laravel.

## Local Development

Jalankan Laravel backend dari root project:

```bash
php artisan serve
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

## Environment

Copy `.env.example` menjadi `.env` lalu sesuaikan URL backend:

```text
VITE_BACKEND_URL=http://127.0.0.1:8000
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_PROVIDER_FRONTEND_URL=http://127.0.0.1:5173
```

Landing ini membaca API publik Laravel seperti `/api/categories`, `/api/services`, dan `/api/providers`.

Auth customer memakai API Laravel:

```text
POST /api/auth/login
POST /api/auth/register/customer
POST /api/auth/logout
```
