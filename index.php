<?php
// index.php — Halaman utama publik
require_once 'config.php';
require_once 'auth.php';

// Ambil data novel & posts yang published
$db = getDB();
$novels = $db->query("
    SELECT id, title, synopsis, cover_image, buy_link, year, color, genre
    FROM novels WHERE status='published'
    ORDER BY year DESC, id DESC
")->fetchAll();

$blogs   = $db->query("SELECT id,title,cover_image,slug,published_at,SUBSTRING(content,1,220) AS excerpt FROM posts WHERE type='blog'    AND status='published' ORDER BY published_at DESC LIMIT 6")->fetchAll();
$cerpens = $db->query("SELECT id,title,cover_image,slug,published_at,SUBSTRING(content,1,220) AS excerpt FROM posts WHERE type='cerpen'  AND status='published' ORDER BY published_at DESC LIMIT 6")->fetchAll();
$artikels= $db->query("SELECT id,title,cover_image,slug,published_at,SUBSTRING(content,1,220) AS excerpt FROM posts WHERE type='artikel' AND status='published' ORDER BY published_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Katalog Buku M. Tansiswo Siagian</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0d1117;
      --bg-secondary: #161b22;
      --fg: #e6edf3;
      --muted: #8b949e;
      --accent: #d4a853;
      --accent-dim: #a88a3d;
      --card: #1c2128;
      --border: #30363d;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background-color: var(--bg);
      color: var(--fg);
      line-height: 1.6;
      overflow-x: hidden;
    }

    .font-display { font-family: 'Cormorant Garamond', serif; }

    .noise-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      pointer-events: none; z-index: 1000; opacity: 0.03;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
    }

    .nav-link {
      position: relative; color: var(--muted); transition: color 0.3s ease;
    }
    .nav-link::after {
      content: ''; position: absolute; bottom: -4px; left: 0;
      width: 0; height: 2px; background: var(--accent); transition: width 0.3s ease;
    }
    .nav-link:hover, .nav-link.active { color: var(--fg); }
    .nav-link:hover::after, .nav-link.active::after { width: 100%; }

    /* Login icon button */
    .nav-login-btn {
      display: inline-flex; align-items: center; justify-content: center;
      width: 36px; height: 36px; border-radius: 50%;
      border: 1px solid var(--border); color: var(--muted);
      transition: all 0.25s; background: transparent;
      text-decoration: none;
    }
    .nav-login-btn:hover {
      border-color: var(--accent); color: var(--accent);
      background: rgba(212,168,83,0.1);
    }

    .particle { position: absolute; border-radius: 50%; pointer-events: none; }

    .hero-gradient {
      background:
        radial-gradient(ellipse 80% 50% at 50% -20%, rgba(212, 168, 83, 0.15), transparent),
        radial-gradient(ellipse 60% 40% at 80% 60%, rgba(212, 168, 83, 0.08), transparent),
        linear-gradient(180deg, var(--bg) 0%, var(--bg-secondary) 100%);
    }

    /* ===== BOOK CARD ===== */
    .book-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 0.75rem;
      overflow: hidden;
      transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1),
                  box-shadow 0.4s ease;
      display: flex;
      flex-direction: column;
    }
    .book-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 24px 48px -12px rgba(0,0,0,0.5), 0 0 0 1px var(--accent-dim);
    }

    .book-cover {
      position: relative;
      height: 224px;
      flex-shrink: 0;
      overflow: hidden;
    }
    .book-cover::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 50%);
      pointer-events: none; z-index: 2;
    }
    .book-spine {
      position: absolute; left: 0; top: 0; width: 14px; height: 100%;
      background: linear-gradient(90deg, rgba(0,0,0,0.35), rgba(0,0,0,0.15), transparent);
      z-index: 3;
    }
    .book-cover-img {
      width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .book-cover-fallback {
      position: absolute; inset: 0;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 0.75rem; padding: 1.5rem; text-align: center; z-index: 1;
    }
    .book-year-badge {
      position: absolute; bottom: 12px; right: 12px;
      background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
      padding: 4px 10px; border-radius: 6px; font-size: 0.75rem;
      color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.1); z-index: 4;
    }
    .book-genre-badge {
      position: absolute; top: 12px; left: 18px;
      background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
      padding: 4px 10px; border-radius: 9999px; font-size: 0.7rem;
      color: rgba(255,255,255,0.75); border: 1px solid rgba(255,255,255,0.1);
      text-transform: capitalize; z-index: 4;
    }

    .card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .expand-btn {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--accent);
      padding: 0.5rem 1rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-family: 'DM Sans', sans-serif;
    }
    .expand-btn:hover {
      border-color: var(--accent);
      background: rgba(212, 168, 83, 0.1);
    }

    .buy-btn {
      background: var(--accent); color: var(--bg);
      padding: 0.5rem 1.25rem; border-radius: 9999px;
      font-size: 0.75rem; font-weight: 600; cursor: pointer;
      border: none; transition: all 0.3s ease;
      display: inline-flex; align-items: center; gap: 0.4rem;
      text-decoration: none; font-family: 'DM Sans', sans-serif;
    }
    .buy-btn:hover { background: var(--accent-dim); transform: translateY(-2px); }
    .buy-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

    /* ===== MODAL ===== */
    #modalOverlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.75);
      backdrop-filter: blur(5px);
      z-index: 900;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    #modalOverlay.active { display: flex; }
    #modalCard {
      background: var(--card);
      border: 1px solid var(--accent);
      border-radius: 1rem;
      box-shadow: 0 40px 100px -20px rgba(0,0,0,0.8), 0 0 0 1px var(--accent);
      display: flex; flex-direction: row;
      width: 100%; max-width: 800px; max-height: 90vh;
      overflow: hidden; position: relative;
      animation: modalIn 0.35s cubic-bezier(0.23, 1, 0.32, 1);
    }
    @keyframes modalIn {
      from { opacity: 0; transform: scale(0.93) translateY(20px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    #modalCover { width: 260px; min-width: 260px; position: relative; overflow: hidden; flex-shrink: 0; }
    #modalCover .book-spine { height: 100%; }
    #modalBody { flex: 1; padding: 2rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
    #modalClose {
      position: absolute; top: 12px; right: 12px;
      width: 36px; height: 36px; border-radius: 50%;
      background: rgba(0,0,0,0.5); border: 1px solid var(--border);
      color: var(--muted); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.25s ease; z-index: 10;
      font-family: 'DM Sans', sans-serif;
    }
    #modalClose:hover { background: var(--accent); color: var(--bg); border-color: var(--accent); }
    @media (max-width: 640px) {
      #modalCard { flex-direction: column; max-height: 92vh; }
      #modalCover { width: 100%; min-width: unset; height: 180px; }
      #modalBody { padding: 1.25rem; }
    }

    /* ===== POST CARDS ===== */
    .post-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 0.75rem;
      overflow: hidden;
      transition: transform 0.35s cubic-bezier(0.23,1,0.32,1), box-shadow 0.35s ease;
      display: flex; flex-direction: column;
      text-decoration: none; color: inherit;
    }
    .post-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px -12px rgba(0,0,0,0.5), 0 0 0 1px var(--accent-dim);
    }
    .post-cover {
      height: 180px; overflow: hidden; flex-shrink: 0;
      background: linear-gradient(135deg, var(--bg-secondary), var(--card));
      position: relative;
    }
    .post-cover img { width:100%;height:100%;object-fit:cover;display:block; }
    .post-cover-placeholder {
      width:100%;height:100%;
      display:flex;align-items:center;justify-content:center;
      color: var(--border);
    }
    .post-card-body { padding:1.25rem;display:flex;flex-direction:column;flex:1; }
    .post-date { font-size:0.75rem;color:var(--muted);margin-bottom:0.5rem; }
    .post-title {
      font-family:'Cormorant Garamond',serif;
      font-size:1.25rem;font-weight:600;line-height:1.3;
      margin-bottom:0.6rem;color:var(--fg);
    }
    .post-excerpt { font-size:0.85rem;color:var(--muted);line-height:1.6;flex:1; }
    .post-read-more {
      margin-top:1rem;padding-top:0.75rem;border-top:1px solid var(--border);
      font-size:0.78rem;color:var(--accent);
      display:inline-flex;align-items:center;gap:0.3rem;
    }

    /* Section headings */
    .section-accent-label {
      color: var(--accent); letter-spacing: 0.3em; text-transform: uppercase;
      font-size: 0.8rem; margin-bottom: 0.5rem; display: block;
    }

    /* Filter button */
    .filter-btn {
      background: transparent; border: 1px solid var(--border);
      color: var(--muted); padding: 0.5rem 1.25rem;
      border-radius: 9999px; transition: all 0.3s ease; cursor: pointer;
      font-family: 'DM Sans', sans-serif;
    }
    .filter-btn:hover { border-color: var(--accent-dim); color: var(--fg); }
    .filter-btn.active { background: var(--accent); border-color: var(--accent); color: var(--bg); }

    /* Timeline */
    .timeline-item { position: relative; }
    .timeline-item::before {
      content: ''; position: absolute; left: 0; top: 8px;
      width: 12px; height: 12px; border-radius: 50%;
      background: var(--accent); box-shadow: 0 0 20px var(--accent);
    }
    .timeline-item::after {
      content: ''; position: absolute; left: 5px; top: 24px;
      width: 2px; height: calc(100% - 8px); background: var(--border);
    }
    .timeline-item:last-child::after { display: none; }

    .social-icon {
      display: flex; align-items: center; justify-content: center;
      width: 48px; height: 48px; border-radius: 50%;
      background: var(--card); border: 1px solid var(--border);
      color: var(--muted); transition: all 0.3s ease;
    }
    .social-icon:hover {
      background: var(--accent); border-color: var(--accent);
      color: var(--bg); transform: translateY(-4px);
    }

    .reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    .hamburger { display: flex; flex-direction: column; gap: 5px; cursor: pointer; padding: 8px; background: none; border: none; }
    .hamburger span { display: block; width: 24px; height: 2px; background: var(--fg); transition: all 0.3s ease; }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

    .mobile-menu {
      position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
      background: var(--bg); z-index: 40;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 2rem; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .mobile-menu.active { opacity: 1; visibility: visible; }
    .mobile-menu a { font-size: 2rem; font-family: 'Cormorant Garamond', serif; color: var(--fg); text-decoration: none; transition: color 0.3s ease; }
    .mobile-menu a:hover { color: var(--accent); }

    .quote-mark { font-size: 8rem; line-height: 1; color: var(--accent); opacity: 0.2; font-family: 'Cormorant Garamond', serif; }

    .stagger-item { opacity: 0; transform: translateY(40px); }
    .stagger-item.animate { animation: staggerIn 0.6s ease forwards; }
    @keyframes staggerIn { to { opacity: 1; transform: translateY(0); } }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }
    .scroll-indicator { animation: bounce 2s infinite; }
    @keyframes bounce {
      0%,20%,50%,80%,100% { transform: translateY(0); }
      40% { transform: translateY(-8px); }
      60% { transform: translateY(-4px); }
    }

    a:focus-visible, button:focus-visible {
      outline: 2px solid var(--accent); outline-offset: 4px;
    }

    .empty-posts {
      text-align:center;padding:4rem 1rem;color:var(--muted);
      border:1px dashed var(--border);border-radius:0.75rem;
    }
  </style>
</head>
<body>
  <div class="noise-overlay"></div>

  <!-- MODAL -->
  <div id="modalOverlay">
    <div id="modalCard">
      <button id="modalClose" aria-label="Tutup">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
      <div id="modalCover">
        <div class="book-spine"></div>
        <div id="modalCoverInner" style="width:100%;height:100%;"></div>
        <span id="modalYearBadge" class="book-year-badge"></span>
        <span id="modalGenreBadge" class="book-genre-badge"></span>
      </div>
      <div id="modalBody">
        <div>
          <p id="modalGenreLabel" style="font-size:0.7rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--accent);margin-bottom:0.5rem;"></p>
          <h2 id="modalTitle" class="font-display" style="font-size:1.75rem;font-weight:700;line-height:1.2;margin-bottom:1rem;"></h2>
        </div>
        <p id="modalSynopsis" style="color:var(--muted);font-size:0.9rem;line-height:1.75;flex:1;"></p>
        <div style="padding-top:1rem;border-top:1px solid var(--border);display:flex;gap:0.75rem;flex-wrap:wrap;">
          <a id="modalBuyBtn" class="buy-btn" target="_blank" rel="noopener noreferrer">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            Beli Buku
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="fixed top-0 left-0 w-full z-50 transition-all duration-300" id="navbar">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="#beranda" class="font-display text-2xl font-bold text-[var(--accent)]">M. TS</a>
      <div class="hidden md:flex items-center gap-8">
        <a href="#beranda"  class="nav-link active">Beranda</a>
        <a href="#buku"     class="nav-link">Buku</a>
        <a href="#blog"     class="nav-link">Blog</a>
        <a href="#cerpen"   class="nav-link">Cerpen</a>
        <a href="#artikel"  class="nav-link">Artikel</a>
        <a href="#tentang"  class="nav-link">Tentang</a>
        <!-- Login / Dashboard icon -->
        <?php if (isLoggedIn()): ?>
        <a href="author.php" class="nav-login-btn" title="Dashboard Penulis" aria-label="Dashboard Penulis" style="width:auto;padding:0 0.8rem;gap:0.35rem;font-size:0.8rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <?php else: ?>
        <a href="login.php" class="nav-login-btn" title="Login Penulis" aria-label="Login Penulis">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
        <?php endif; ?>
      </div>
      <button class="hamburger md:hidden" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <div class="mobile-menu" id="mobileMenu">
    <a href="#beranda">Beranda</a>
    <a href="#buku">Buku</a>
    <a href="#blog">Blog</a>
    <a href="#cerpen">Cerpen</a>
    <a href="#artikel">Artikel</a>
    <a href="#tentang">Tentang</a>
    <?php if (isLoggedIn()): ?>
    <a href="author.php" style="font-size:1rem;color:var(--accent);">Dashboard</a>
    <?php else: ?>
    <a href="login.php" style="font-size:1rem;color:var(--accent);">Login Penulis</a>
    <?php endif; ?>
  </div>

  <!-- Hero Section -->
  <section id="beranda" class="min-h-screen hero-gradient relative flex items-center overflow-hidden">
    <div id="particles" class="absolute inset-0 pointer-events-none"></div>
    <div class="absolute top-20 right-10 w-64 h-64 rounded-full bg-[var(--accent)] opacity-5 blur-3xl"></div>
    <div class="absolute bottom-20 left-10 w-96 h-96 rounded-full bg-[var(--accent)] opacity-5 blur-3xl"></div>
    <div class="max-w-6xl mx-auto px-6 py-32 relative z-10">
      <div class="max-w-3xl">
        <p class="text-[var(--accent)] tracking-[0.3em] uppercase text-sm mb-4 reveal">Novelis Batak Toba</p>
        <h1 class="font-display text-5xl md:text-7xl lg:text-8xl font-bold leading-tight mb-6 reveal" style="transition-delay:0.1s;">
          M. Tansiswo<br>Siagian
        </h1>
        <a href="#buku" class="inline-flex items-center gap-3 bg-[var(--accent)] text-[var(--bg)] px-8 py-4 font-semibold rounded-lg transition-all duration-300 hover:bg-[var(--accent-dim)] hover:gap-5 reveal" style="transition-delay:0.3s;">
          Lihat Karya
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <div class="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 w-80 text-right">
        <span class="quote-mark">"</span>
      </div>
    </div>
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-[var(--muted)]">
      <span class="text-sm tracking-wider">Scroll</span>
      <div class="w-px h-12 bg-gradient-to-b from-[var(--accent)] to-transparent scroll-indicator"></div>
    </div>
  </section>

  <!-- ===== BUKU / NOVEL SECTION ===== -->
  <section id="buku" class="py-24 md:py-32 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-[var(--bg-secondary)] to-[var(--bg)]"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
      <div class="text-center mb-16">
        <span class="section-accent-label reveal">Koleksi Karya</span>
        <h2 class="font-display text-4xl md:text-5xl font-bold reveal" style="transition-delay:0.1s;">Katalog Novel</h2>
      </div>

      <div class="flex flex-wrap justify-center gap-3 mb-12 reveal" style="transition-delay:0.2s;">
        <button class="filter-btn active" data-filter="all">Semua</button>
        <button class="filter-btn" data-filter="novel">Novel</button>
      </div>

      <?php if (empty($novels)): ?>
      <div class="empty-posts"><p>Belum ada novel yang diterbitkan.</p></div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="booksGrid">
        <?php foreach ($novels as $idx => $book): ?>
        <?php
          $color    = htmlspecialchars($book['color'] ?? '#6B4423', ENT_QUOTES, 'UTF-8');
          $colorDim = $color; // simplified
        ?>
        <article class="book-card stagger-item" style="animation-delay:<?= $idx * 0.1 ?>s;">
          <div class="book-cover" style="background: linear-gradient(150deg, <?= $color ?> 0%, <?= $color ?>88 60%, <?= $color ?>55 100%);">
            <div class="book-spine"></div>
            <?php if ($book['cover_image']): ?>
            <img src="<?= e($book['cover_image']) ?>" alt="Sampul <?= e($book['title']) ?>" class="book-cover-img" onerror="this.style.display='none';">
            <?php endif; ?>
            <div class="book-cover-fallback" <?= $book['cover_image'] ? 'style="display:none;"' : '' ?>>
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1.5">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              </svg>
              <span class="font-display" style="font-size:0.95rem;color:white;text-shadow:0 2px 8px rgba(0,0,0,0.5);line-height:1.3;"><?= e($book['title']) ?></span>
              <span style="color:rgba(255,255,255,0.5);font-size:0.7rem;">M. Tansiswo Siagian</span>
            </div>
            <span class="book-year-badge"><?= e((string)$book['year']) ?></span>
            <span class="book-genre-badge"><?= e($book['genre']) ?></span>
          </div>
          <div class="card-body">
            <h3 class="font-display text-xl font-semibold mb-3 leading-tight"><?= e($book['title']) ?></h3>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;padding-top:1rem;border-top:1px solid var(--border);margin-top:auto;">
              <button class="expand-btn" onclick='openModal(<?= htmlspecialchars(json_encode([
                "id"=>$book["id"],"title"=>$book["title"],"synopsis"=>$book["synopsis"],
                "cover"=>$book["cover_image"],"year"=>$book["year"],"genre"=>$book["genre"],
                "color"=>$book["color"],"buyLink"=>$book["buy_link"]
              ]), ENT_QUOTES) ?>)'>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Baca Selengkapnya
              </button>
              <?php if ($book['buy_link']): ?>
              <a href="<?= e($book['buy_link']) ?>" class="buy-btn" target="_blank" rel="noopener noreferrer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Beli Buku
              </a>
              <?php else: ?>
              <span class="buy-btn disabled">Segera Tersedia</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== BLOG SECTION ===== -->
  <section id="blog" class="py-24 md:py-32 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-[var(--bg)] to-[var(--bg-secondary)]"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
      <div class="text-center mb-14">
        <span class="section-accent-label reveal">Tulisan</span>
        <h2 class="font-display text-4xl md:text-5xl font-bold reveal" style="transition-delay:0.1s;">Blog</h2>
      </div>
      <?php if (empty($blogs)): ?>
      <div class="empty-posts reveal"><p>Belum ada posting blog.</p></div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($blogs as $i => $p): ?>
        <a href="baca.php?slug=<?= urlencode($p['slug']) ?>" class="post-card stagger-item" style="animation-delay:<?= $i*0.08 ?>s;">
          <div class="post-cover">
            <?php if ($p['cover_image']): ?>
            <img src="<?= e($p['cover_image']) ?>" alt="<?= e($p['title']) ?>">
            <?php else: ?>
            <div class="post-cover-placeholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <?php endif; ?>
          </div>
          <div class="post-card-body">
            <?php if ($p['published_at']): ?><p class="post-date"><?= date('d F Y', strtotime($p['published_at'])) ?></p><?php endif; ?>
            <h3 class="post-title"><?= e($p['title']) ?></h3>
            <p class="post-excerpt"><?= e(mb_strimwidth(strip_tags($p['excerpt']), 0, 150, '…')) ?></p>
            <span class="post-read-more">Baca selengkapnya <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== CERPEN SECTION ===== -->
  <section id="cerpen" class="py-24 md:py-32 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-[var(--bg-secondary)] to-[var(--bg)]"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
      <div class="text-center mb-14">
        <span class="section-accent-label reveal">Karya Fiksi</span>
        <h2 class="font-display text-4xl md:text-5xl font-bold reveal" style="transition-delay:0.1s;">Cerpen</h2>
      </div>
      <?php if (empty($cerpens)): ?>
      <div class="empty-posts reveal"><p>Belum ada cerpen yang diterbitkan.</p></div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($cerpens as $i => $p): ?>
        <a href="baca.php?slug=<?= urlencode($p['slug']) ?>" class="post-card stagger-item" style="animation-delay:<?= $i*0.08 ?>s;">
          <div class="post-cover" style="background:linear-gradient(135deg,#1a1040,#0d1117);">
            <?php if ($p['cover_image']): ?>
            <img src="<?= e($p['cover_image']) ?>" alt="<?= e($p['title']) ?>">
            <?php else: ?>
            <div class="post-cover-placeholder" style="color:#bc8cff;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <?php endif; ?>
          </div>
          <div class="post-card-body">
            <?php if ($p['published_at']): ?><p class="post-date"><?= date('d F Y', strtotime($p['published_at'])) ?></p><?php endif; ?>
            <h3 class="post-title"><?= e($p['title']) ?></h3>
            <p class="post-excerpt"><?= e(mb_strimwidth(strip_tags($p['excerpt']), 0, 150, '…')) ?></p>
            <span class="post-read-more">Baca selengkapnya <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== ARTIKEL SECTION ===== -->
  <section id="artikel" class="py-24 md:py-32 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-[var(--bg)] to-[var(--bg-secondary)]"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
      <div class="text-center mb-14">
        <span class="section-accent-label reveal">Opini & Gagasan</span>
        <h2 class="font-display text-4xl md:text-5xl font-bold reveal" style="transition-delay:0.1s;">Artikel</h2>
      </div>
      <?php if (empty($artikels)): ?>
      <div class="empty-posts reveal"><p>Belum ada artikel yang diterbitkan.</p></div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($artikels as $i => $p): ?>
        <a href="baca.php?slug=<?= urlencode($p['slug']) ?>" class="post-card stagger-item" style="animation-delay:<?= $i*0.08 ?>s;">
          <div class="post-cover" style="background:linear-gradient(135deg,#0d200f,#0d1117);">
            <?php if ($p['cover_image']): ?>
            <img src="<?= e($p['cover_image']) ?>" alt="<?= e($p['title']) ?>">
            <?php else: ?>
            <div class="post-cover-placeholder" style="color:#3fb950;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
            </div>
            <?php endif; ?>
          </div>
          <div class="post-card-body">
            <?php if ($p['published_at']): ?><p class="post-date"><?= date('d F Y', strtotime($p['published_at'])) ?></p><?php endif; ?>
            <h3 class="post-title"><?= e($p['title']) ?></h3>
            <p class="post-excerpt"><?= e(mb_strimwidth(strip_tags($p['excerpt']), 0, 150, '…')) ?></p>
            <span class="post-read-more">Baca selengkapnya <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== ABOUT SECTION ===== -->
  <section id="tentang" class="py-24 md:py-32 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-[var(--bg)] via-[var(--bg-secondary)] to-[var(--bg)]"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full bg-[var(--accent)] opacity-5 blur-3xl"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
      <div class="grid lg:grid-cols-2 gap-16 items-start">
        <div class="reveal">
          <div class="relative inline-block">
            <div class="w-64 h-64 md:w-80 md:h-80 rounded-2xl bg-gradient-to-br from-[var(--card)] to-[var(--bg-secondary)] border border-[var(--border)] flex items-center justify-center relative overflow-hidden">
              <img src="Profil.png" alt="M. Tansiswo Siagian" style="width:100%;height:100%;object-fit:cover;display:block;"/>
              <div class="absolute inset-4 border border-[var(--accent)] opacity-30 rounded-xl"></div>
            </div>
            <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-[var(--accent)] opacity-20 blur-2xl rounded-full"></div>
          </div>
          <div class="flex gap-4 mt-8">
            <a href="https://www.instagram.com/m.tansiswo/" class="social-icon" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="https://www.tiktok.com/@tansiswo3" class="social-icon" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3v12a4 4 0 1 1-4-4"/><path d="M9 3c1.5 2.5 3.5 4 6 4"/></svg>
            </a>
            <a href="https://www.facebook.com/mtansiswo.siagianii" class="social-icon" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
          </div>
        </div>
        <div>
          <span class="section-accent-label reveal">Tentang Saya</span>
          <h2 class="font-display text-4xl md:text-5xl font-bold mb-6 reveal" style="transition-delay:0.1s;">Perjalanan Saya</h2>
          <div class="space-y-4 text-[var(--muted)] mb-12 reveal" style="transition-delay:0.2s;">
            <p>M. Tansiswo Siagian adalah penulis Indonesia yang telah menghasilkan berbagai karya sastra yang bermakna. Melalui tulisannya, ia mengajak pembaca untuk merenungkan nilai-nilai kehidupan, budaya, dan spiritualitas.</p>
            <p>Karya-karyanya dikenal karena kedalaman tema dan kemampuannya menyentuh hati pembaca. Setiap buku yang ditulisnya merupakan perpaduan antara pengalaman pribadi, observasi sosial, dan imajinasi yang kaya.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="py-8 border-t border-[var(--border)]">
    <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
      <p class="text-[var(--muted)] text-sm">2026 M. Tansiswo Siagian. Semua hak dilindungi.</p>
      <p class="text-[var(--muted)] text-sm">Dibuat dengan dedikasi untuk para pembaca</p>
    </div>
  </footer>

  <script>
    // ===== MODAL =====
    const modalOverlay = document.getElementById('modalOverlay');
    const modalClose   = document.getElementById('modalClose');

    function adjustColor(hex, amount) {
      const num = parseInt(hex.replace('#',''), 16);
      const r = Math.max(0,Math.min(255,(num>>16)+amount));
      const g = Math.max(0,Math.min(255,((num>>8)&0xFF)+amount));
      const b = Math.max(0,Math.min(255,(num&0xFF)+amount));
      return '#'+((r<<16)|(g<<8)|b).toString(16).padStart(6,'0');
    }

    function openModal(book) {
      const color = book.color || '#6B4423';
      const cover = document.getElementById('modalCover');
      cover.style.background = `linear-gradient(150deg, ${color} 0%, ${adjustColor(color,-40)} 60%, ${adjustColor(color,-60)} 100%)`;

      const ci = document.getElementById('modalCoverInner');
      if (book.cover) {
        ci.innerHTML = `<img src="${book.cover}" alt="Sampul ${book.title}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display='none';">`;
      } else {
        ci.innerHTML = `<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.75rem;padding:1.5rem;text-align:center;">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <span style="font-family:'Cormorant Garamond',serif;font-size:1rem;color:white;">${book.title}</span>
        </div>`;
      }
      document.getElementById('modalYearBadge').textContent  = book.year;
      document.getElementById('modalGenreBadge').textContent = book.genre;
      document.getElementById('modalGenreLabel').textContent = book.genre;
      document.getElementById('modalTitle').textContent      = book.title;
      document.getElementById('modalSynopsis').textContent   = book.synopsis;

      const btn = document.getElementById('modalBuyBtn');
      if (book.buyLink) {
        btn.href = book.buyLink;
        btn.classList.remove('disabled');
        btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Beli Buku`;
      } else {
        btn.removeAttribute('href'); btn.classList.add('disabled'); btn.textContent='Segera Tersedia';
      }

      modalOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modalOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });
    modalClose.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // ===== FILTER BUKU =====
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    // ===== PARTICLES =====
    (function() {
      const c = document.getElementById('particles');
      if (!c) return;
      for (let i=0; i<30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const s = Math.random()*4+2;
        p.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}%;top:${Math.random()*100}%;background:var(--accent);opacity:${Math.random()*0.3+0.1};animation:float ${Math.random()*20+15}s ease-in-out ${Math.random()*10}s infinite;`;
        c.appendChild(p);
      }
    })();

    // ===== NAV SCROLL =====
    (function() {
      const nb = document.getElementById('navbar');
      window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
          nb.style.background = 'rgba(13,17,23,0.95)';
          nb.style.backdropFilter = 'blur(10px)';
          nb.style.borderBottom = '1px solid var(--border)';
        } else {
          nb.style.background='transparent'; nb.style.backdropFilter='none'; nb.style.borderBottom='none';
        }
      });
      const sections = document.querySelectorAll('section');
      const links    = document.querySelectorAll('.nav-link');
      window.addEventListener('scroll', () => {
        let cur = '';
        sections.forEach(s => { if (window.scrollY >= s.offsetTop-100) cur = s.id; });
        links.forEach(l => { l.classList.remove('active'); if (l.getAttribute('href')==='#'+cur) l.classList.add('active'); });
      });
    })();

    // ===== MOBILE MENU =====
    (function() {
      const h = document.getElementById('hamburger');
      const m = document.getElementById('mobileMenu');
      h.addEventListener('click', () => {
        h.classList.toggle('active'); m.classList.toggle('active');
        document.body.style.overflow = m.classList.contains('active') ? 'hidden' : '';
      });
      m.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        h.classList.remove('active'); m.classList.remove('active'); document.body.style.overflow='';
      }));
    })();

    // ===== SCROLL REVEAL =====
    (function() {
      const obs = new IntersectionObserver(es => {
        es.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
      }, {threshold:0.1});
      document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

      // Stagger items
      setTimeout(() => {
        document.querySelectorAll('.stagger-item').forEach(el => el.classList.add('animate'));
      }, 100);
    })();
  </script>
</body>
</html>
