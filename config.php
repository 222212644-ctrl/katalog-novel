<?php
// ============================================================
// config.php — Konfigurasi Database
// Ganti nilai di bawah sesuai pengaturan hosting Rumahweb Anda
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'batt8713_bataktobaterkini');   // <-- ganti ini
define('DB_USER', 'batt8713');   // <-- ganti ini
define('DB_PASS', ''); // <-- ganti ini
define('DB_CHARSET', 'utf8mb4');

// Upload path
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// ============================================================
// Koneksi PDO — singleton
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jangan tampilkan detail error ke user di produksi
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    return $pdo;
}

// ============================================================
// Helper: sanitize output
// ============================================================
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ============================================================
// Helper: generate slug dari title
// ============================================================
function generateSlug(string $title, int $id = 0): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    $slug = preg_replace('/[\s\-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($id > 0) $slug .= '-' . $id;
    return $slug ?: 'post-' . time();
}

// ============================================================
// Helper: upload gambar dengan validasi ketat
// ============================================================
function uploadImage(array $file, string $prefix = 'img'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;

    // Validasi MIME type yang sesungguhnya (bukan dari $_FILES)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) return false;

    $ext = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => false,
    };
    if (!$ext) return false;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return UPLOAD_URL . $filename;
}

// ============================================================
// CSRF helpers
// ============================================================
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
    // Rotate token setelah verifikasi
    unset($_SESSION['csrf_token']);
}
