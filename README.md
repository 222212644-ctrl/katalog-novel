# Panduan Setup — Website M. Tansiswo Siagian

## Daftar File

| File | Keterangan |
|------|-----------|
| `index.php` | Halaman utama publik (novel, blog, cerpen, artikel) |
| `login.php` | Halaman login penulis |
| `auth.php` | Helper autentikasi (disertakan file lain) |
| `config.php` | Konfigurasi database + helper fungsi |
| `author.php` | Dashboard penulis |
| `author_posts.php` | Kelola blog / cerpen / artikel |
| `author_novels.php` | Kelola katalog novel |
| `baca.php` | Halaman baca publik untuk setiap tulisan |
| `api.php` | JSON API (cadangan, belum dipakai langsung) |
| `theme.css` | CSS tema bersama semua halaman backend |
| `database.sql` | Script SQL untuk buat tabel di phpMyAdmin |
| `generate_hash.php` | Helper satu kali untuk hash password |
| `.htaccess` | Keamanan Apache |
| `uploads/.htaccess` | Keamanan folder uploads |

---

## Langkah Setup di Rumahweb

### 1. Buat Database di phpMyAdmin
1. Login cPanel Rumahweb → phpMyAdmin
2. Buat database baru, catat nama, username, dan password-nya
3. Pilih database tersebut → tab **Import** → pilih `database.sql` → klik **Go**

### 2. Konfigurasi `config.php`
Buka `config.php` dan ganti baris berikut:
```php
define('DB_NAME', 'nama_database_anda');
define('DB_USER', 'user_database_anda');
define('DB_PASS', 'password_database_anda');
```

### 3. Generate Hash Password
Karena `generate_hash.php` hanya bisa diakses dari localhost, gunakan cara ini:

**Opsi A — Via SSH/Terminal hosting:**
```bash
php -r "echo password_hash('bataktobaterkini', PASSWORD_BCRYPT, ['cost'=>12]);"
```

**Opsi B — Buat file PHP sementara:**
Buat file `hash_temp.php` dengan isi:
```php
<?php echo password_hash('bataktobaterkini', PASSWORD_BCRYPT, ['cost'=>12]);
```
Akses di browser, salin hasilnya, lalu **hapus file ini segera**.

Tempel hash yang didapat ke `login.php` pada baris:
```php
const AUTHOR_PASS_HASH = 'HASH_YANG_ANDA_SALIN_DI_SINI';
```

### 4. Upload Semua File
Upload semua file ke folder `public_html` (atau subfolder sesuai domain Anda).
Pastikan struktur folder:
```
public_html/
├── index.php
├── login.php
├── auth.php
├── config.php
├── author.php
├── author_posts.php
├── author_novels.php
├── baca.php
├── api.php
├── theme.css
├── .htaccess
├── uploads/
│   └── .htaccess
├── Profil.png        ← foto profil
├── Sortauli.jpeg     ← cover novel
└── Amangbao.jpeg     ← cover novel
```

### 5. Aktifkan HTTPS
Di `.htaccess`, hapus tanda `#` pada bagian:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Cara Pakai

### Login Penulis
- URL: `https://domain-anda.com/login.php`
- Username: `tansiswo@siagian`
- Password: `bataktobaterkini`

### Setelah Login
- **Dashboard** → ringkasan statistik + akses cepat
- **Tulisan** → buat/edit/hapus blog, cerpen, artikel
- **Novel** → buat/edit/hapus katalog novel
- Setiap konten bisa disimpan sebagai **Draf** dulu, lalu **Diterbitkan**
- Konten yang diterbitkan otomatis muncul di `index.php`

### Pengunjung
- Bisa mengakses semua halaman tanpa login
- Ikon login (👤) ada di pojok kanan atas navigasi

---

## Keamanan yang Sudah Diterapkan
- Password disimpan sebagai bcrypt hash (bukan plain text)
- CSRF token pada semua form POST
- Rate limiting login (5x gagal → kunci 5 menit)
- Session regenerate setelah login sukses
- Validasi MIME type sesungguhnya untuk upload gambar
- Folder `uploads/` tidak bisa eksekusi PHP
- `config.php` dan `auth.php` diblokir akses langsung via `.htaccess`
- Semua output di-escape dengan `htmlspecialchars`
- Prepared statements PDO untuk semua query database
- Security headers (X-Frame-Options, CSP, dll.)
