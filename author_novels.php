<?php
// ============================================================
// author_novels.php — Kelola Katalog Novel
// ============================================================
require_once 'auth.php';
require_once 'config.php';
requireLogin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$flash  = '';
$flashType = 'success';

// ============================================================
// POST handlers
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';
    $postId     = (int)($_POST['id'] ?? 0);

    // DELETE
    if ($postAction === 'delete' && $postId > 0) {
        $row = $db->prepare("SELECT cover_image FROM novels WHERE id = ?");
        $row->execute([$postId]);
        $old = $row->fetch();
        if ($old && $old['cover_image'] && strpos($old['cover_image'], '/uploads/') === 0) {
            $lp = __DIR__ . $old['cover_image'];
            if (file_exists($lp)) @unlink($lp);
        }
        $db->prepare("DELETE FROM novels WHERE id = ?")->execute([$postId]);
        $flash = 'Novel berhasil dihapus.';
        $action = 'list';

    // PUBLISH / UNPUBLISH
    } elseif (in_array($postAction, ['publish', 'unpublish']) && $postId > 0) {
        $newStatus = $postAction === 'publish' ? 'published' : 'draft';
        $db->prepare("UPDATE novels SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $postId]);
        $flash = $postAction === 'publish' ? 'Novel berhasil diterbitkan.' : 'Novel dikembalikan ke draf.';
        $action = 'list';

    // SAVE
    } elseif ($postAction === 'save') {
        $title    = trim($_POST['title']    ?? '');
        $synopsis = trim($_POST['synopsis'] ?? '');
        $buyLink  = trim($_POST['buy_link'] ?? '');
        $year     = (int)($_POST['year']    ?? date('Y'));
        $color    = $_POST['color'] ?? '#6B4423';
        $genre    = $_POST['genre'] ?? 'novel';
        $status   = ($_POST['submit_action'] ?? 'draft') === 'publish' ? 'published' : 'draft';

        // Validate color hex
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#6B4423';
        // Validate year
        if ($year < 1900 || $year > (int)date('Y') + 5) $year = (int)date('Y');
        // Validate buy_link
        if ($buyLink && !filter_var($buyLink, FILTER_VALIDATE_URL)) {
            $flash = 'Link pembelian tidak valid.'; $flashType = 'error';
        }

        if ($title === '' || $synopsis === '') {
            $flash = 'Judul dan sinopsis tidak boleh kosong.'; $flashType = 'error';
        }

        if ($flashType !== 'error') {
            $coverImage = null;
            if (!empty($_FILES['cover_image']['name'])) {
                $uploaded = uploadImage($_FILES['cover_image'], 'novel');
                if ($uploaded === false) {
                    $flash = 'Gagal mengunggah gambar. Format JPEG/PNG/WebP, maks 5 MB.';
                    $flashType = 'error';
                } else {
                    $coverImage = $uploaded;
                }
            }
        }

        if ($flashType !== 'error') {
            if ($postId > 0) {
                // UPDATE
                if ($coverImage) {
                    $old = $db->prepare("SELECT cover_image FROM novels WHERE id = ?");
                    $old->execute([$postId]);
                    $oldRow = $old->fetch();
                    if ($oldRow && $oldRow['cover_image'] && strpos($oldRow['cover_image'], '/uploads/') === 0) {
                        $lp = __DIR__ . $oldRow['cover_image'];
                        if (file_exists($lp)) @unlink($lp);
                    }
                    $stmt = $db->prepare("UPDATE novels SET title=?,synopsis=?,cover_image=?,buy_link=?,year=?,color=?,genre=?,status=?,updated_at=NOW() WHERE id=?");
                    $stmt->execute([$title,$synopsis,$coverImage,$buyLink,$year,$color,$genre,$status,$postId]);
                } else {
                    $stmt = $db->prepare("UPDATE novels SET title=?,synopsis=?,buy_link=?,year=?,color=?,genre=?,status=?,updated_at=NOW() WHERE id=?");
                    $stmt->execute([$title,$synopsis,$buyLink,$year,$color,$genre,$status,$postId]);
                }
                $flash = 'Novel berhasil diperbarui.';
            } else {
                // INSERT
                $stmt = $db->prepare("INSERT INTO novels (title,synopsis,cover_image,buy_link,year,color,genre,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
                $stmt->execute([$title,$synopsis,$coverImage,$buyLink,$year,$color,$genre,$status]);
                $postId = (int)$db->lastInsertId();
                $flash = $status === 'published' ? 'Novel berhasil diterbitkan!' : 'Draf novel disimpan.';
            }
            $action = 'edit';
            $id = $postId;
        }
    }
}

// ============================================================
// Fetch data
// ============================================================
$editNovel = null;
if (($action === 'edit') && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt->execute([$id]);
    $editNovel = $stmt->fetch();
}

$novels = [];
if ($action === 'list') {
    $novels = $db->query("SELECT * FROM novels ORDER BY year DESC, updated_at DESC")->fetchAll();
}

$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Novel — M. Tansiswo Siagian</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    .novel-editor-wrap {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 1.5rem;
      align-items: start;
    }
    .cover-swatch {
      width: 100%; height: 160px; border-radius: var(--radius);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 0.95rem; color: rgba(255,255,255,0.6);
      position: relative; overflow: hidden;
      margin-bottom: 0.75rem;
      transition: background 0.3s;
    }
    .cover-swatch img { width:100%;height:100%;object-fit:cover;position:absolute;inset:0; }

    @media(max-width:768px){
      .novel-editor-wrap { grid-template-columns: 1fr; }
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
      <a href="author.php" class="nav-link">Dashboard</a>
      <a href="author_posts.php" class="nav-link">Tulisan</a>
      <a href="author_novels.php" class="nav-link active">Novel</a>
      <a href="logout.php" class="btn btn-ghost btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar
      </a>
    </div>
  </div>
</nav>

<div class="page-wrapper">

  <?php if ($flash): ?>
  <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:1.5rem;">
    <?= e($flash) ?>
  </div>
  <?php endif; ?>

  <?php if ($action === 'list'): ?>
  <!-- ============ LIST ============ -->
  <div class="flex items-center justify-between mb-4 wrap gap-3">
    <div>
      <h1 style="font-size:2rem;font-weight:700;">Katalog Novel</h1>
      <p class="text-muted text-sm">Kelola novel dan katalog buku Anda.</p>
    </div>
    <a href="?action=new" class="btn btn-primary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Novel
    </a>
  </div>

  <div class="card" style="padding:0;">
    <?php if (empty($novels)): ?>
    <div style="padding:3rem;text-align:center;color:var(--muted);">Belum ada novel. <a href="?action=new">Tambah sekarang →</a></div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Judul</th>
          <th>Tahun</th>
          <th>Status</th>
          <th>Diperbarui</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($novels as $n): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <?php if ($n['cover_image']): ?>
            <img src="<?= e($n['cover_image']) ?>" alt="" style="width:36px;height:48px;object-fit:cover;border-radius:4px;flex-shrink:0;">
            <?php else: ?>
            <div style="width:36px;height:48px;background:<?= e($n['color']) ?>;border-radius:4px;flex-shrink:0;"></div>
            <?php endif; ?>
            <a href="?action=edit&id=<?= $n['id'] ?>" style="color:var(--fg);font-size:0.88rem;font-weight:500;"><?= e($n['title']) ?></a>
          </div>
        </td>
        <td class="text-muted"><?= e((string)$n['year']) ?></td>
        <td><span class="badge badge-<?= $n['status'] ?>"><?= e($n['status'] === 'published' ? 'Tayang' : 'Draf') ?></span></td>
        <td class="text-muted text-xs"><?= date('d M Y', strtotime($n['updated_at'])) ?></td>
        <td>
          <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
            <a href="?action=edit&id=<?= $n['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin?')">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="id"     value="<?= $n['id'] ?>">
              <input type="hidden" name="action" value="<?= $n['status'] === 'published' ? 'unpublish' : 'publish' ?>">
              <button class="btn btn-sm <?= $n['status'] === 'published' ? 'btn-ghost' : 'btn-success' ?>">
                <?= $n['status'] === 'published' ? 'Tarik' : 'Terbitkan' ?>
              </button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus novel ini permanen?')">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="id"     value="<?= $n['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- ============ EDITOR ============ -->
  <?php
    $isNew = ($action === 'new');
    $curTitle    = $editNovel['title']       ?? '';
    $curSynopsis = $editNovel['synopsis']    ?? '';
    $curBuyLink  = $editNovel['buy_link']    ?? '';
    $curYear     = $editNovel['year']        ?? (int)date('Y');
    $curColor    = $editNovel['color']       ?? '#6B4423';
    $curGenre    = $editNovel['genre']       ?? 'novel';
    $curStatus   = $editNovel['status']      ?? 'draft';
    $curCover    = $editNovel['cover_image'] ?? '';
    $editorId    = $editNovel['id']          ?? 0;
  ?>

  <div class="flex items-center justify-between mb-4 wrap gap-3">
    <div>
      <a href="author_novels.php" class="text-muted text-sm">← Kembali ke daftar novel</a>
      <h1 style="font-size:2rem;font-weight:700;margin-top:0.4rem;">
        <?= $isNew ? 'Tambah Novel Baru' : 'Edit Novel' ?>
      </h1>
    </div>
    <?php if (!$isNew): ?>
    <span class="badge badge-<?= $curStatus ?>"><?= $curStatus === 'published' ? 'Tayang' : 'Draf' ?></span>
    <?php endif; ?>
  </div>

  <form method="POST" enctype="multipart/form-data" id="novelForm">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action"     value="save">
    <input type="hidden" name="id"         value="<?= $editorId ?>">

    <div class="novel-editor-wrap">
      <!-- Main -->
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <div class="form-group">
          <label class="form-label">Judul Novel</label>
          <input type="text" name="title" class="form-control" required
                 placeholder="Judul novel…"
                 value="<?= e($curTitle) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Sinopsis / Deskripsi</label>
          <textarea name="synopsis" class="form-control" style="min-height:300px;" required
                    placeholder="Tulis sinopsis novel Anda di sini…"><?= e($curSynopsis) ?></textarea>
          <div class="word-count" id="synWordCount">0 kata</div>
        </div>
        <div class="form-group">
          <label class="form-label">Link Pembelian (Tokopedia / Shopee / dll.)</label>
          <input type="url" name="buy_link" class="form-control"
                 placeholder="https://tokopedia.com/…"
                 value="<?= e($curBuyLink) ?>">
        </div>
      </div>

      <!-- Sidebar -->
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <!-- Actions -->
        <div class="card">
          <p style="font-size:0.82rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:1rem;">Simpan / Terbitkan</p>
          <div style="display:flex;flex-direction:column;gap:0.6rem;">
            <button type="submit" name="submit_action" value="draft" class="btn btn-ghost" style="justify-content:center;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Simpan Draf
            </button>
            <button type="submit" name="submit_action" value="publish" class="btn btn-primary" style="justify-content:center;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              <?= $curStatus === 'published' ? 'Perbarui' : 'Terbitkan' ?>
            </button>
          </div>
        </div>

        <!-- Cover Image -->
        <div class="card">
          <p class="form-label" style="margin-bottom:0.75rem;">Cover Novel</p>
          <div class="cover-swatch" id="coverSwatch"
               style="background: linear-gradient(150deg, <?= e($curColor) ?> 0%, <?= e($curColor) ?>88 100%);">
            <?php if ($curCover): ?>
            <img src="<?= e($curCover) ?>" alt="Cover" id="coverPreviewImg">
            <?php else: ?>
            <img src="" alt="" id="coverPreviewImg" style="display:none;">
            <?php endif; ?>
            <span id="swatchLabel" style="position:relative;z-index:1;<?= $curCover ? 'display:none' : '' ?>"><?= e($curTitle ?: 'Preview Cover') ?></span>
          </div>
          <input type="file" name="cover_image" id="coverInput" accept="image/jpeg,image/png,image/webp"
                 class="form-control" style="padding:0.4rem;">
          <p class="text-muted text-xs mt-1">JPEG / PNG / WebP — maks 5 MB</p>
        </div>

        <!-- Meta -->
        <div class="card">
          <div style="display:flex;flex-direction:column;gap:0.85rem;">
            <div class="form-group">
              <label class="form-label">Tahun Terbit</label>
              <input type="number" name="year" class="form-control"
                     min="1900" max="<?= (int)date('Y') + 5 ?>"
                     value="<?= e((string)$curYear) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Warna Tema Cover</label>
              <div style="display:flex;gap:0.5rem;align-items:center;">
                <input type="color" name="color" id="colorPicker" value="<?= e($curColor) ?>"
                       style="width:44px;height:36px;border:1px solid var(--border);border-radius:6px;background:transparent;cursor:pointer;padding:2px;">
                <input type="text" id="colorText" value="<?= e($curColor) ?>"
                       class="form-control" style="font-family:monospace;font-size:0.85rem;"
                       maxlength="7" placeholder="#6B4423">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Genre</label>
              <input type="text" name="genre" class="form-control"
                     placeholder="novel, roman, sastra…"
                     value="<?= e($curGenre) ?>">
            </div>
          </div>
        </div>

        <?php if ($editNovel): ?>
        <?php if ($curStatus === 'published'): ?>
        <a href="index.php#buku" target="_blank"
           class="btn btn-ghost" style="justify-content:center;width:100%;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Lihat di Situs
        </a>
        <?php endif; ?>
        <div class="card">
          <p class="form-label">Informasi</p>
          <p class="text-muted text-xs mt-2">Dibuat: <?= date('d M Y H:i', strtotime($editNovel['created_at'])) ?></p>
          <p class="text-muted text-xs mt-1">Diperbarui: <?= date('d M Y H:i', strtotime($editNovel['updated_at'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </form>
  <?php endif; ?>

</div>

<script>
// Cover image preview
(function() {
  const input    = document.getElementById('coverInput');
  const imgEl    = document.getElementById('coverPreviewImg');
  const label    = document.getElementById('swatchLabel');
  if (!input || !imgEl) return;
  input.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      imgEl.src = e.target.result;
      imgEl.style.display = 'block';
      if (label) label.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
})();

// Color sync
(function() {
  const picker  = document.getElementById('colorPicker');
  const textEl  = document.getElementById('colorText');
  const swatch  = document.getElementById('coverSwatch');
  if (!picker || !textEl || !swatch) return;

  function applyColor(hex) {
    swatch.style.background = `linear-gradient(150deg, ${hex} 0%, ${hex}88 100%)`;
  }
  picker.addEventListener('input', function() {
    textEl.value = this.value;
    applyColor(this.value);
  });
  textEl.addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
      picker.value = this.value;
      applyColor(this.value);
    }
  });
})();

// Synopsis word count
(function() {
  const ta = document.querySelector('textarea[name="synopsis"]');
  const wc = document.getElementById('synWordCount');
  if (!ta || !wc) return;
  function update() {
    wc.textContent = ta.value.trim().split(/\s+/).filter(Boolean).length.toLocaleString('id') + ' kata';
  }
  ta.addEventListener('input', update);
  update();
})();
</script>

<style>
.word-count { font-size: 0.75rem; color: var(--muted); text-align: right; margin-top: 0.25rem; }
</style>
</body>
</html>
