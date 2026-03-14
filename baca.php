<?php
// ============================================================
// baca.php — Halaman baca publik untuk blog / cerpen / artikel
// ============================================================
require_once 'config.php';
require_once 'auth.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: index.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    ?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="theme.css"></head>
    <body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
    <div style="text-align:center;">
      <p style="font-size:5rem;font-family:'Cormorant Garamond',serif;color:var(--accent);">404</p>
      <p style="color:var(--muted);">Halaman tidak ditemukan.</p>
      <a href="index.php" style="color:var(--accent);">← Kembali</a>
    </div></body></html><?php
    exit;
}

$typeLabel = ['blog'=>'Blog','cerpen'=>'Cerpen','artikel'=>'Artikel'][$post['type']] ?? ucfirst($post['type']);
$pubDate   = $post['published_at'] ? date('d F Y', strtotime($post['published_at'])) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($post['title']) ?> — M. Tansiswo Siagian</title>
  <meta name="description" content="<?= e(mb_strimwidth(strip_tags($post['content']), 0, 160, '…')) ?>">
  <link rel="stylesheet" href="theme.css">
  <style>
    .read-hero {
      background:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(212,168,83,0.1), transparent),
        var(--bg-secondary);
      padding: 6rem 1.5rem 3rem;
      border-bottom: 1px solid var(--border);
    }
    .read-hero-inner { max-width: 720px; margin: 0 auto; }

    .read-body {
      max-width: 720px; margin: 0 auto;
      padding: 3rem 1.5rem 5rem;
    }

    .post-content {
      font-size: 1.05rem;
      line-height: 1.85;
      color: var(--fg);
    }
    .post-content p    { margin-bottom: 1.4rem; }
    .post-content h2   { font-family:'Cormorant Garamond',serif; font-size:1.75rem; margin:2rem 0 0.75rem; color:var(--fg); }
    .post-content h3   { font-family:'Cormorant Garamond',serif; font-size:1.4rem; margin:1.75rem 0 0.6rem; color:var(--fg); }
    .post-content blockquote {
      border-left: 3px solid var(--accent);
      padding: 0.75rem 1.25rem;
      margin: 1.5rem 0;
      background: rgba(212,168,83,0.06);
      border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
      color: var(--muted);
      font-style: italic;
    }
    .post-content strong { color: var(--fg); font-weight: 600; }
    .post-content em { font-style: italic; }
    .post-content a { color: var(--accent); }

    .cover-img {
      width: 100%; max-height: 420px;
      object-fit: cover;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      margin-bottom: 2.5rem;
    }
  </style>
</head>
<body>
<div class="noise-overlay"></div>

<!-- Nav -->
<nav class="site-nav">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">M. TS</a>
    <div class="nav-links">
      <a href="index.php#buku"    class="nav-link">Buku</a>
      <a href="index.php#blog"    class="nav-link">Blog</a>
      <a href="index.php#cerpen"  class="nav-link">Cerpen</a>
      <a href="index.php#artikel" class="nav-link">Artikel</a>
      <?php if (isLoggedIn()): ?>
      <a href="author_posts.php?action=edit&id=<?= $post['id'] ?>" class="btn btn-ghost btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Edit
      </a>
      <?php else: ?>
      <a href="login.php" class="nav-login-btn" title="Login Penulis">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="read-hero">
  <div class="read-hero-inner">
    <div style="margin-bottom:1rem;">
      <span class="badge badge-<?= e($post['type']) ?>" style="font-size:0.75rem;"><?= e($typeLabel) ?></span>
    </div>
    <h1 style="font-size:2.5rem;font-weight:700;line-height:1.2;margin-bottom:1rem;"><?= e($post['title']) ?></h1>
    <?php if ($pubDate): ?>
    <p style="color:var(--muted);font-size:0.88rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <?= e($pubDate) ?>
    </p>
    <?php endif; ?>
  </div>
</div>

<!-- Content -->
<div class="read-body">
  <?php if ($post['cover_image']): ?>
  <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" class="cover-img">
  <?php endif; ?>

  <div class="post-content">
    <?= nl2br(e($post['content'])) ?>
  </div>

  <div class="divider" style="margin-top:3rem;"></div>
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-top:1.5rem;">
    <a href="index.php#<?= e($post['type']) ?>" class="btn btn-ghost btn-sm">← Kembali ke <?= e($typeLabel) ?></a>
    <span class="text-muted text-xs">M. Tansiswo Siagian</span>
  </div>
</div>
</body>
</html>
