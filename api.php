<?php
// ============================================================
// api.php — JSON API untuk index.php (data publik saja)
// ============================================================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// Tidak ada data sensitif, tapi tetap batasi origin
header('Cache-Control: public, max-age=60');

$db   = getDB();
$type = $_GET['type'] ?? 'novels';

try {
    switch ($type) {
        // ── Novel yang sudah published ──
        case 'novels':
            $stmt = $db->query("
                SELECT id, title, synopsis, cover_image, buy_link,
                       year, color, genre, published_at
                FROM novels
                WHERE status = 'published'
                ORDER BY year DESC, id DESC
            ");
            echo json_encode(['data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ── Posts berdasarkan tipe ──
        case 'blog':
        case 'cerpen':
        case 'artikel':
            $stmt = $db->prepare("
                SELECT id, title, cover_image, slug, published_at,
                       SUBSTRING(content, 1, 300) AS excerpt
                FROM posts
                WHERE type = ? AND status = 'published'
                ORDER BY published_at DESC
                LIMIT 12
            ");
            $stmt->execute([$type]);
            echo json_encode(['data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ── Single post (untuk halaman baca) ──
        case 'post':
            $slug = $_GET['slug'] ?? '';
            if ($slug === '') { http_response_code(400); echo json_encode(['error'=>'slug required']); break; }
            $stmt = $db->prepare("
                SELECT id, type, title, content, cover_image, published_at
                FROM posts
                WHERE slug = ? AND status = 'published'
                LIMIT 1
            ");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            if (!$row) { http_response_code(404); echo json_encode(['error'=>'not found']); break; }
            echo json_encode(['data' => $row], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'unknown type']);
    }
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
