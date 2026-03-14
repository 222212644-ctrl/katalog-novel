<?php
// ============================================================
// author.php — Halaman Dashboard Penulis
// ============================================================
require_once 'auth.php';
require_once 'config.php';
requireLogin();

// ── Handle logout ──
if (isset($_GET['logout'])) {
    header('Location: logout.php');
    exit;
}

// ── Ambil ringkasan statistik ──
$db = getDB();
$stats = [];
foreach (['blog','cerpen','artikel'] as $t) {
    $stmt = $db->prepare("SELECT
        SUM(status='published') AS published,
        SUM(status='draft')     AS draft
        FROM posts WHERE type = ?");
    $stmt->execute([$t]);
    $stats[$t] = $stmt->fetch();
}
$novelStats = $db->query("SELECT SUM(status='published') AS published, SUM(status='draft') AS draft FROM novels")->fetch();

// ── Ambil 5 post terbaru semua tipe ──
$recent = $db->query("
    SELECT id, type, title, status, updated_at FROM posts
    ORDER BY updated_at DESC LIMIT 5
")->fetchAll();
$recentNovels = $db->query("
    SELECT id, title, status, updated_at FROM novels
    ORDER BY updated_at DESC LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Penulis — M. Tansiswo Siagian</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      transition: border-color 0.25s;
    }
    .stat-card:hover { border-color: var(--accent-dim); }
    .stat-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 0.85rem;
    }
    .stat-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem; font-weight: 700; line-height: 1;
      color: var(--fg);
    }
    .stat-label { font-size: 0.8rem; color: var(--muted); margin-top: 0.2rem; }
    .stat-sub   { font-size: 0.75rem; color: var(--muted); margin-top: 0.35rem; }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 0.75rem;
      margin-bottom: 2rem;
    }
    .quick-btn {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 0.9rem 1.1rem;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--fg); font-family: 'DM Sans', sans-serif;
      font-size: 0.88rem; font-weight: 500;
      cursor: pointer; text-decoration: none;
      transition: all 0.25s;
    }
    .quick-btn:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: var(--accent-glow);
      transform: translateY(-2px);
    }
    .quick-btn .icon-box {
      width: 34px; height: 34px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .section-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.4rem; font-weight: 700;
      margin-bottom: 1rem;
    }
    .empty-state {
      text-align: center; padding: 2.5rem 1rem;
      color: var(--muted); font-size: 0.9rem;
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
      <a href="index.php" class="nav-link">Lihat Situs</a>
      <a href="author.php" class="nav-link active">Dashboard</a>
      <a href="author_posts.php" class="nav-link">Tulisan</a>
      <a href="author_novels.php" class="nav-link">Novel</a>
      <a href="logout.php" class="btn btn-ghost btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar
      </a>
    </div>
  </div>
</nav>

<div class="page-wrapper">
  <!-- Header -->
  <div style="margin-bottom:2rem;">
    <p style="color:var(--accent);font-size:0.8rem;letter-spacing:0.2em;text-transform:uppercase;margin-bottom:0.4rem;">Selamat Datang</p>
    <h1 style="font-size:2.2rem;font-weight:700;">Dashboard Penulis</h1>
    <p class="text-muted text-sm mt-1">Kelola tulisan, cerpen, artikel, dan katalog novel Anda.</p>
  </div>

  <!-- Stats -->
  <div class="dashboard-grid">
    <!-- Blog -->
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(88,166,255,0.12);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#58a6ff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div class="stat-val"><?= (int)($stats['blog']['published'] ?? 0) + (int)($stats['blog']['draft'] ?? 0) ?></div>
      <div class="stat-label">Blog</div>
      <div class="stat-sub"><?= (int)($stats['blog']['published'] ?? 0) ?> tayang · <?= (int)($stats['blog']['draft'] ?? 0) ?> draf</div>
    </div>
    <!-- Cerpen -->
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(188,140,255,0.12);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#bc8cff" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </div>
      <div class="stat-val"><?= (int)($stats['cerpen']['published'] ?? 0) + (int)($stats['cerpen']['draft'] ?? 0) ?></div>
      <div class="stat-label">Cerpen</div>
      <div class="stat-sub"><?= (int)($stats['cerpen']['published'] ?? 0) ?> tayang · <?= (int)($stats['cerpen']['draft'] ?? 0) ?> draf</div>
    </div>
    <!-- Artikel -->
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(63,185,80,0.12);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3fb950" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
      </div>
      <div class="stat-val"><?= (int)($stats['artikel']['published'] ?? 0) + (int)($stats['artikel']['draft'] ?? 0) ?></div>
      <div class="stat-label">Artikel</div>
      <div class="stat-sub"><?= (int)($stats['artikel']['published'] ?? 0) ?> tayang · <?= (int)($stats['artikel']['draft'] ?? 0) ?> draf</div>
    </div>
    <!-- Novel -->
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(212,168,83,0.12);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4a853" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <div class="stat-val"><?= (int)($novelStats['published'] ?? 0) + (int)($novelStats['draft'] ?? 0) ?></div>
      <div class="stat-label">Novel</div>
      <div class="stat-sub"><?= (int)($novelStats['published'] ?? 0) ?> tayang · <?= (int)($novelStats['draft'] ?? 0) ?> draf</div>
    </div>
  </div>

  <!-- Quick Actions -->
  <h2 class="section-title">Buat Konten Baru</h2>
  <div class="quick-actions">
    <a href="author_posts.php?action=new&type=blog" class="quick-btn">
      <div class="icon-box" style="background:rgba(88,166,255,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#58a6ff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      Tulis Blog
    </a>
    <a href="author_posts.php?action=new&type=cerpen" class="quick-btn">
      <div class="icon-box" style="background:rgba(188,140,255,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#bc8cff" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </div>
      Tulis Cerpen
    </a>
    <a href="author_posts.php?action=new&type=artikel" class="quick-btn">
      <div class="icon-box" style="background:rgba(63,185,80,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3fb950" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
      </div>
      Tulis Artikel
    </a>
    <a href="author_novels.php?action=new" class="quick-btn">
      <div class="icon-box" style="background:rgba(212,168,83,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d4a853" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      Tambah Novel
    </a>
  </div>

  <!-- Recent Posts -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:2rem;" class="recent-grid">
    <div>
      <h2 class="section-title">Tulisan Terbaru</h2>
      <div class="card" style="padding:0;">
        <?php if (empty($recent)): ?>
        <div class="empty-state">Belum ada tulisan.</div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Judul</th><th>Tipe</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td>
              <a href="author_posts.php?action=edit&id=<?= $r['id'] ?>" style="color:var(--fg);font-size:0.88rem;">
                <?= e(mb_strimwidth($r['title'], 0, 40, '…')) ?>
              </a>
            </td>
            <td><span class="badge badge-<?= $r['type'] ?>"><?= e($r['type']) ?></span></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= e($r['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div style="margin-top:0.75rem;">
        <a href="author_posts.php" class="btn btn-ghost btn-sm">Semua Tulisan →</a>
      </div>
    </div>

    <div>
      <h2 class="section-title">Novel Terbaru</h2>
      <div class="card" style="padding:0;">
        <?php if (empty($recentNovels)): ?>
        <div class="empty-state">Belum ada novel.</div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Judul</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentNovels as $n): ?>
          <tr>
            <td>
              <a href="author_novels.php?action=edit&id=<?= $n['id'] ?>" style="color:var(--fg);font-size:0.88rem;">
                <?= e(mb_strimwidth($n['title'], 0, 45, '…')) ?>
              </a>
            </td>
            <td><span class="badge badge-<?= $n['status'] ?>"><?= e($n['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div style="margin-top:0.75rem;">
        <a href="author_novels.php" class="btn btn-ghost btn-sm">Semua Novel →</a>
      </div>
    </div>
  </div>
</div>

<style>
@media(max-width:640px){
  .recent-grid { grid-template-columns: 1fr !important; }
}
</style>
</body>
</html>
