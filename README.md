# AI Web Builder

Aplikasi web builder berbasis AI yang memungkinkan Anda membuat website profesional dengan cepat menggunakan prompt AI. Aplikasi ini mendukung multiple AI providers (OpenRouter & Google Gemini) dan fitur lengkap untuk generate, edit, improve, dan publish website.

## ✨ Fitur

- 🤖 **AI-Powered Generation**: Generate website dari prompt menggunakan OpenRouter (Claude, GPT-4, dll) atau Google Gemini
- 📝 **Paste Code Mode**: Import website dari HTML, CSS, dan JavaScript yang sudah ada
- 🎨 **Customizable Design**: Pilih style, color palette, dan sections yang diinginkan
- ✏️ **Live Editor**: Edit HTML, CSS, dan JavaScript langsung di aplikasi
- 🚀 **Publish & Share**: Publish website dan dapatkan URL publik
- 🔄 **Improve Feature**: Perbaiki website dengan instruksi tambahan ke AI
- 📦 **Export**: Download project sebagai file HTML lengkap

## 📋 Requirements

- **PHP**: >= 8.2
- **Composer**: Latest version
- **Node.js**: >= 18.x
- **NPM** atau **Yarn**
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite
- **AI API Key**: 
  - OpenRouter API Key (dari https://openrouter.ai/keys) **ATAU**
  - Google Gemini API Key (dari https://makersuite.google.com/app/apikey)

## 🚀 Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/khoirulanam20/web-builder.git
cd web-builder/laravel
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Setup Environment

```bash
# Copy file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_builder
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Konfigurasi AI Provider

Pilih salah satu AI provider dan tambahkan API key di file `.env`:

**Opsi A: OpenRouter (Recommended)**
```env
OPENROUTER_API_KEY=sk-or-v1-xxxxxxxxxxxxx
OPENROUTER_MODEL=anthropic/claude-3.5-sonnet
OPENROUTER_HTTP_REFERER=http://localhost:8000
OPENROUTER_TITLE="AI Web Generator"
```

**Opsi B: Google Gemini**
```env
GOOGLE_GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxx
GOOGLE_GEMINI_MODEL=gemini-2.5-flash
```

> **Catatan**: Minimal pilih salah satu provider. Jika menggunakan OpenRouter, Anda bisa akses berbagai model AI seperti Claude, GPT-4, dll.

### 6. Setup Database

```bash
# Run migrations
php artisan migrate

# (Optional) Seed database dengan data dummy
php artisan db:seed
```

### 7. Build Frontend Assets

```bash
# Development build (dengan hot reload)
npm run dev

# Production build
npm run build
```

### 8. Jalankan Aplikasi

**Development Mode** (dengan hot reload):
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (jika belum menjalankan npm run dev)
npm run dev
```

**Production Mode**:
```bash
# Build assets terlebih dahulu
npm run build

# Jalankan server
php artisan serve
```

Aplikasi akan berjalan di: **http://localhost:8000**

## 📝 Setup Lengkap dengan Script Otomatis

Anda juga bisa menggunakan script setup otomatis:

```bash
composer run setup
```

Script ini akan:
- Install Composer dependencies
- Copy `.env.example` ke `.env` (jika belum ada)
- Generate application key
- Run migrations
- Install NPM dependencies
- Build frontend assets

## 🔧 Konfigurasi Tambahan

### Storage

Pastikan folder `storage` dan `bootstrap/cache` memiliki permission write:

```bash
chmod -R 775 storage bootstrap/cache
```

### Queue (Opsional)

Jika ingin menggunakan queue untuk proses generate yang berat:

```bash
php artisan queue:work
```

### Mail Configuration (Opsional)

Jika ingin mengaktifkan fitur email, konfigurasi di `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## 📖 Cara Penggunaan

### 1. Generate Website dari Prompt

1. Login ke aplikasi
2. Klik **"BUAT WEBSITE BARU"**
3. Pilih AI Provider (OpenRouter atau Google Gemini)
4. Isi informasi website (nama, deskripsi, target audiens)
5. Pilih style & tone, color palette
6. Atur sections yang diinginkan (drag & drop untuk mengubah urutan)
7. (Opsional) Upload gambar referensi
8. Masukkan prompt detail tambahan
9. Klik **"GENERATE WEBSITE"**

### 2. Import dari Code

1. Klik **"MODE PASTE CODE"**
2. Paste HTML, CSS, dan JavaScript Anda
3. Klik **"SIMPAN DARI CODE"**

### 3. Edit & Improve Website

1. Buka project dari dashboard
2. Klik tab **HTML**, **CSS**, atau **JS** untuk melihat kode
3. Klik **"EDIT"** untuk mengedit kode secara manual
4. Atau klik **"IMPROVE"** untuk meminta AI memperbaiki website dengan instruksi tambahan

### 4. Publish Website

1. Di halaman detail project, klik **"PUBLISH"**
2. Website akan dipublish dan tersedia di URL publik
3. URL akan muncul di sidebar **INFO PROJECT**
4. Klik URL untuk membuka di tab baru, atau klik tombol **COPY** untuk menyalin

## 🗂️ Struktur Project

```
laravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── GenerateController.php      # Handle generate & import code
│   │   ├── ProjectController.php       # CRUD projects
│   │   ├── ProjectUpdateController.php # Update code & improve
│   │   └── PublishController.php       # Publish website
│   ├── Models/
│   │   ├── Project.php
│   │   └── GeneratedFile.php
│   └── Services/
│       └── AIGeneratorService.php       # Service untuk AI generation
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Projects/
│       │   │   ├── Create.vue          # Form generate dari prompt
│       │   │   ├── ImportCode.vue      # Form import code
│       │   │   ├── Show.vue             # Detail & preview project
│       │   │   └── Index.vue            # Dashboard projects
│       │   └── Dashboard.vue
│       └── Components/
│           └── SectionManager.vue      # Drag & drop sections
├── storage/
│   └── app/
│       └── projects/                   # File HTML/CSS/JS project
└── public/
    └── sites/                          # Published websites
```

## 🔐 Authentication

Aplikasi menggunakan Laravel Breeze untuk authentication. User dapat:
- Register akun baru
- Login / Logout
- Mengelola project milik sendiri

## 🧪 Testing

```bash
# Run tests
php artisan test

# Atau dengan PHPUnit
./vendor/bin/phpunit
```

## 📦 Production Deployment

### 1. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 2. Build Assets

```bash
npm run build
```

### 3. Setup Web Server

Pastikan web server (Nginx/Apache) mengarah ke folder `public/` dan memiliki permission untuk:
- `storage/` folder
- `bootstrap/cache/` folder

### 4. Environment Variables

Pastikan semua environment variables di `.env` sudah dikonfigurasi dengan benar, terutama:
- `APP_ENV=production`
- `APP_DEBUG=false`
- Database credentials
- AI API keys

## 🐛 Troubleshooting

### Error: "OPENROUTER_API_KEY tidak ditemukan"
- Pastikan API key sudah ditambahkan di file `.env`
- Format key harus dimulai dengan `sk-` atau `sk-or-`
- Dapatkan API key di: https://openrouter.ai/keys

### Error: "GOOGLE_GEMINI_API_KEY tidak ditemukan"
- Pastikan API key sudah ditambahkan di file `.env`
- Dapatkan API key di: https://makersuite.google.com/app/apikey

### Error: "Storage permission denied"
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Error: "Vite manifest not found"
```bash
npm run build
```

### Website tidak muncul setelah publish
- Pastikan folder `public/sites/` memiliki permission write
- Cek apakah file `index.html` sudah tersimpan di `storage/app/projects/{project_id}/`

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🤝 Contributing

Thank you for considering contributing to this project! Please feel free to submit issues or pull requests.

## 📞 Support

Jika ada pertanyaan atau masalah, silakan buat issue di repository ini.

---

**Happy Building! 🚀**
