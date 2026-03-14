<?php
// ============================================================
// author_posts.php — Kelola Blog, Cerpen, Artikel
// ============================================================
require_once 'auth.php';
require_once 'config.php';
requireLogin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$type   = $_GET['type']   ?? 'blog';
$id     = (int)($_GET['id'] ?? 0);
$flash  = '';
$flashType = 'success';

// ── Allowed types ──
$allowedTypes = ['blog', 'cerpen', 'artikel'];
if (!in_array($type, $allowedTypes, true)) $type = 'blog';

// ============================================================
// POST handlers
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postType   = $_POST['type']   ?? 'blog';
    $postAction = $_POST['action'] ?? '';
    $postId     = (int)($_POST['id'] ?? 0);

    if (!in_array($postType, $allowedTypes, true)) $postType = 'blog';

    // DELETE
    if ($postAction === 'delete' && $postId > 0) {
        // Hapus gambar cover jika ada
        $row = $db->prepare("SELECT cover_image FROM posts WHERE id = ?");
        $row->execute([$postId]);
        $oldRow = $row->fetch();
        if ($oldRow && $oldRow['cover_image']) {
            $localPath = __DIR__ . $oldRow['cover_image'];
            if (file_exists($localPath)) @unlink($localPath);
        }
        $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
        $flash = 'Tulisan berhasil dihapus.';
        $action = 'list';

    // PUBLISH / UNPUBLISH
    } elseif (in_array($postAction, ['publish', 'unpublish']) && $postId > 0) {
        $newStatus = $postAction === 'publish' ? 'published' : 'draft';
        $pubDate   = $postAction === 'publish' ? date('Y-m-d H:i:s') : null;
        $stmt = $db->prepare("UPDATE posts SET status = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $pubDate, $postId]);
        $flash = $postAction === 'publish' ? 'Tulisan berhasil diterbitkan.' : 'Tulisan dikembalikan ke draf.';
        $action = 'list';

    // SAVE (create / update)
    } elseif ($postAction === 'save') {
        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');
        $status  = ($_POST['submit_action'] ?? 'draft') === 'publish' ? 'published' : 'draft';

        if ($title === '' || $content === '') {
            $flash = 'Judul dan konten tidak boleh kosong.';
            $flashType = 'error';
        } else {
            // Upload cover image jika ada
            $coverImage = null;
            if (!empty($_FILES['cover_image']['name'])) {
                $uploaded = uploadImage($_FILES['cover_image'], 'post');
                if ($uploaded === false) {
                    $flash = 'Gagal mengunggah gambar. Pastikan format JPEG/PNG/WebP, maks 5 MB.';
                    $flashType = 'error';
                } else {
                    $coverImage = $uploaded;
                }
            }

            if ($flashType !== 'error') {
                $pubDate = $status === 'published' ? date('Y-m-d H:i:s') : null;

                if ($postId > 0) {
                    // UPDATE
                    if ($coverImage) {
                        // Hapus gambar lama
                        $old = $db->prepare("SELECT cover_image FROM posts WHERE id = ?");
                        $old->execute([$postId]);
                        $oldRow = $old->fetch();
                        if ($oldRow && $oldRow['cover_image']) {
                            $lp = __DIR__ . $oldRow['cover_image'];
                            if (file_exists($lp)) @unlink($lp);
                        }
                        $stmt = $db->prepare("UPDATE posts SET type=?,title=?,content=?,cover_image=?,status=?,published_at=?,updated_at=NOW() WHERE id=?");
                        $stmt->execute([$postType,$title,$content,$coverImage,$status,$pubDate,$postId]);
                    } else {
                        $stmt = $db->prepare("UPDATE posts SET type=?,title=?,content=?,status=?,published_at=?,updated_at=NOW() WHERE id=?");
                        $stmt->execute([$postType,$title,$content,$status,$pubDate,$postId]);
                    }
                    $flash = 'Tulisan berhasil diperbarui.';
                } else {
                    // INSERT
                    $slug = generateSlug($title);
                    // Pastikan slug unik
                    $exists = $db->prepare("SELECT id FROM posts WHERE slug = ?");
                    $exists->execute([$slug]);
                    if ($exists->fetch()) $slug = $slug . '-' . time();

                    $stmt = $db->prepare("INSERT INTO posts (type,title,content,cover_image,slug,status,published_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
                    $stmt->execute([$postType,$title,$content,$coverImage,$slug,$status,$pubDate]);
                    $postId = (int)$db->lastInsertId();
                    $flash = $status === 'published' ? 'Tulisan berhasil diterbitkan!' : 'Draf berhasil disimpan.';
                }
                $action = 'edit';
                $id = $postId;
            }
        }
    }
}

// ============================================================
// Fetch data untuk tampilan
// ============================================================
$editPost = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $editPost = $stmt->fetch();
    if ($editPost) $type = $editPost['type'];
}

// List data
$filterType = $_GET['filter'] ?? 'all';
if ($action === 'list') {
    if ($filterType !== 'all' && in_array($filterType, $allowedTypes, true)) {
        $listStmt = $db->prepare("SELECT * FROM posts WHERE type = ? ORDER BY updated_at DESC");
        $listStmt->execute([$filterType]);
    } else {
        $listStmt = $db->query("SELECT * FROM posts ORDER BY updated_at DESC");
    }
    $posts = $listStmt->fetchAll();
}

// CSRF
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Tulisan — M. Tansiswo Siagian</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    .editor-wrap {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 1.5rem;
      align-items: start;
    }
    .editor-sidebar { display: flex; flex-direction: column; gap: 1rem; }
    .editor-actions {
      display: flex; gap: 0.75rem; flex-wrap: wrap;
      padding: 1rem; background: var(--card);
      border: 1px solid var(--border); border-radius: var(--radius);
    }
    .content-textarea { min-height: 450px; font-family: 'DM Sans', sans-serif; line-height: 1.8; }
    .word-count { font-size: 0.75rem; color: var(--muted); text-align: right; margin-top: 0.25rem; }

    .post-row { display: flex; flex-direction: column; gap: 0.2rem; }
    .post-title-link {
      font-size: 0.9rem; color: var(--fg); font-weight: 500;
      text-decoration: none;
    }
    .post-title-link:hover { color: var(--accent); }
    .post-meta { font-size: 0.75rem; color: var(--muted); }

    .action-row { display: flex; gap: 0.4rem; flex-wrap: wrap; }

    @media(max-width:768px){
      .editor-wrap { grid-template-columns: 1fr; }
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
      <a href="author_posts.php" class="nav-link active">Tulisan</a>
      <a href="author_novels.php" class="nav-link">Novel</a>
      <a href="logout.php" class="btn btn-ghost btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar
      </a>
    </div>
  </div>
</nav>

<div class="page-wrapper">

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:1.5rem;">
    <?= e($flash) ?>
  </div>
  <?php endif; ?>

  <?php if ($action === 'list'): ?>
  <!-- ============ LIST VIEW ============ -->
  <div class="flex items-center justify-between mb-4 wrap gap-3">
    <div>
      <h1 style="font-size:2rem;font-weight:700;">Kelola Tulisan</h1>
      <p class="text-muted text-sm">Blog, cerpen, dan artikel Anda.</p>
    </div>
    <div class="flex gap-2 wrap">
      <a href="?action=new&type=blog"    class="btn btn-ghost btn-sm">+ Blog</a>
      <a href="?action=new&type=cerpen"  class="btn btn-ghost btn-sm">+ Cerpen</a>
      <a href="?action=new&type=artikel" class="btn btn-ghost btn-sm">+ Artikel</a>
    </div>
  </div>

  <!-- Filter tabs -->
  <div class="tabs">
    <?php foreach ([['all','Semua'],['blog','Blog'],['cerpen','Cerpen'],['artikel','Artikel']] as [$f,$l]): ?>
    <a href="?filter=<?= $f ?>" class="tab-btn <?= $filterType === $f ? 'active' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card" style="padding:0;">
    <?php if (empty($posts)): ?>
    <div style="padding:3rem;text-align:center;color:var(--muted);">Belum ada tulisan. <a href="?action=new&type=blog">Buat sekarang →</a></div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Judul</th>
          <th>Tipe</th>
          <th>Status</th>
          <th>Diperbarui</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td>
          <div class="post-row">
            <a href="?action=edit&id=<?= $p['id'] ?>" class="post-title-link"><?= e($p['title']) ?></a>
            <span class="post-meta">/<?= e($p['slug']) ?></span>
          </div>
        </td>
        <td><span class="badge badge-<?= $p['type'] ?>"><?= e($p['type']) ?></span></td>
        <td><span class="badge badge-<?= $p['status'] ?>"><?= e($p['status'] === 'published' ? 'Tayang' : 'Draf') ?></span></td>
        <td class="text-muted text-xs"><?= date('d M Y', strtotime($p['updated_at'])) ?></td>
        <td>
          <div class="action-row">
            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin <?= $p['status'] === 'published' ? 'tarik dari tayang' : 'terbitkan' ?>?')">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="id"     value="<?= $p['id'] ?>">
              <input type="hidden" name="action" value="<?= $p['status'] === 'published' ? 'unpublish' : 'publish' ?>">
              <button type="submit" class="btn btn-sm <?= $p['status'] === 'published' ? 'btn-ghost' : 'btn-success' ?>">
                <?= $p['status'] === 'published' ? 'Tarik' : 'Terbitkan' ?>
              </button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus tulisan ini permanen?')">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="id"     value="<?= $p['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
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
  <!-- ============ EDITOR (new / edit) ============ -->
  <?php
    $isNew   = ($action === 'new');
    $curType = $editPost['type'] ?? $type;
    $curTitle   = $editPost['title']   ?? '';
    $curContent = $editPost['content'] ?? '';
    $curStatus  = $editPost['status']  ?? 'draft';
    $curCover   = $editPost['cover_image'] ?? '';
    $editorId   = $editPost['id'] ?? 0;

    $typeLabels = ['blog'=>'Blog','cerpen'=>'Cerpen','artikel'=>'Artikel'];
  ?>

  <div class="flex items-center justify-between mb-4 wrap gap-3">
    <div>
      <a href="author_posts.php" class="text-muted text-sm">← Kembali ke daftar tulisan</a>
      <h1 style="font-size:2rem;font-weight:700;margin-top:0.4rem;">
        <?= $isNew ? 'Buat ' . ($typeLabels[$curType] ?? 'Tulisan') . ' Baru' : 'Edit Tulisan' ?>
      </h1>
    </div>
    <?php if (!$isNew): ?>
    <span class="badge badge-<?= $curStatus ?>"><?= $curStatus === 'published' ? 'Tayang' : 'Draf' ?></span>
    <?php endif; ?>
  </div>

  <form method="POST" enctype="multipart/form-data" id="postForm">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action"     value="save">
    <input type="hidden" name="id"         value="<?= $editorId ?>">

    <div class="editor-wrap">
      <!-- Main editor -->
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <div class="form-group">
          <label class="form-label">Judul</label>
          <input type="text" name="title" class="form-control" style="font-size:1.1rem;"
                 placeholder="Judul tulisan Anda…" required
                 value="<?= e($curTitle) ?>">
        </div>
        <div class="form-group">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem;">
            <label class="form-label" style="margin-bottom:0;">Konten</label>
            <div style="display:flex;align-items:center;gap:0.6rem;">
              <span id="autosaveIndicator" style="font-size:0.72rem;transition:color 0.3s;"></span>
              <span class="text-xs text-muted" style="font-size:0.72rem;">Ctrl+S = simpan draf</span>
              <button type="button" id="expandEditor" title="Layar penuh"
                      style="background:none;border:1px solid var(--border);border-radius:6px;padding:4px 7px;color:var(--muted);cursor:pointer;display:flex;align-items:center;transition:all 0.2s;"
                      onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                      onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
              </button>
            </div>
          </div>
          <textarea name="content" id="contentArea" class="form-control content-textarea"
                    placeholder="Mulai menulis… (Ctrl+S untuk simpan draf)" required><?= e($curContent) ?></textarea>
          <div class="word-count" id="wordCount">0 kata</div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="editor-sidebar">
        <!-- Aksi -->
        <div class="card">
          <p style="font-size:0.82rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:1rem;">Simpan / Terbitkan</p>
          <div style="display:flex;flex-direction:column;gap:0.6rem;">
            <button type="submit" name="submit_action" value="draft" class="btn btn-ghost" style="justify-content:center;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Simpan Draf
            </button>
            <button type="submit" name="submit_action" value="publish" class="btn btn-primary" style="justify-content:center;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              <?= $curStatus === 'published' ? 'Perbarui & Terbitkan' : 'Terbitkan' ?>
            </button>
          </div>
        </div>

        <!-- Tipe -->
        <div class="card">
          <div class="form-group">
            <label class="form-label">Tipe Tulisan</label>
            <select name="type" class="form-control">
              <?php foreach ($typeLabels as $tv => $tl): ?>
              <option value="<?= $tv ?>" <?= $curType === $tv ? 'selected' : '' ?>><?= $tl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Cover image -->
        <div class="card">
          <div class="form-group">
            <label class="form-label">Gambar Cover</label>
            <?php if ($curCover): ?>
            <img src="<?= e($curCover) ?>" alt="Cover saat ini" class="img-preview visible" style="margin-bottom:0.75rem;" id="currentCover">
            <p class="text-muted text-xs mb-4">Unggah gambar baru untuk mengganti.</p>
            <?php endif; ?>
            <input type="file" name="cover_image" id="coverInput" accept="image/jpeg,image/png,image/webp"
                   class="form-control" style="padding:0.4rem;">
            <img src="" alt="Preview" class="img-preview mt-2" id="coverPreview">
            <p class="text-muted text-xs mt-1">JPEG / PNG / WebP — maks 5 MB</p>
          </div>
        </div>

        <!-- Preview & Info -->
        <?php if ($editPost): ?>
        <?php if ($curStatus === 'published'): ?>
        <a href="baca.php?slug=<?= urlencode($editPost['slug']) ?>" target="_blank"
           class="btn btn-ghost" style="justify-content:center;width:100%;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Lihat di Situs
        </a>
        <?php endif; ?>
        <div class="card">
          <p class="form-label">Informasi</p>
          <p class="text-muted text-xs mt-2">Dibuat: <?= date('d M Y H:i', strtotime($editPost['created_at'])) ?></p>
          <p class="text-muted text-xs mt-1">Diperbarui: <?= date('d M Y H:i', strtotime($editPost['updated_at'])) ?></p>
          <?php if ($editPost['published_at']): ?>
          <p class="text-muted text-xs mt-1">Diterbitkan: <?= date('d M Y H:i', strtotime($editPost['published_at'])) ?></p>
          <?php endif; ?>
          <div class="divider" style="margin:0.75rem 0;"></div>
          <p class="text-muted text-xs">Slug: <code style="font-size:0.7rem;background:var(--bg);padding:1px 5px;border-radius:3px;"><?= e($editPost['slug']) ?></code></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </form>
  <?php endif; ?>

</div>

<script>
// ── Word count ──
(function() {
  const ta = document.getElementById('contentArea');
  const wc = document.getElementById('wordCount');
  if (!ta || !wc) return;
  function update() {
    const words = ta.value.trim().split(/\s+/).filter(Boolean).length;
    wc.textContent = words.toLocaleString('id') + ' kata';
  }
  ta.addEventListener('input', update);
  update();
})();

// ── Cover image preview ──
(function() {
  const input   = document.getElementById('coverInput');
  const preview = document.getElementById('coverPreview');
  if (!input || !preview) return;
  input.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.classList.add('visible');
      const cur = document.getElementById('currentCover');
      if (cur) cur.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
})();

// ── Autosave draft to localStorage (cadangan lokal) ──
(function() {
  const form      = document.getElementById('postForm');
  const titleEl   = form ? form.querySelector('[name="title"]') : null;
  const contentEl = document.getElementById('contentArea');
  const indicator = document.getElementById('autosaveIndicator');
  if (!form || !titleEl || !contentEl) return;

  const postId   = (form.querySelector('[name="id"]') || {}).value || 'new';
  const storeKey = 'draft_post_' + postId;
  let saveTimer  = null;
  let isDirty    = false;

  function markDirty() {
    isDirty = true;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(localSave, 2500);
  }

  function localSave() {
    try {
      localStorage.setItem(storeKey, JSON.stringify({
        title:   titleEl.value,
        content: contentEl.value,
        saved:   new Date().toLocaleTimeString('id')
      }));
      isDirty = false;
      if (indicator) {
        indicator.textContent = '✓ Tersimpan lokal';
        indicator.style.color = 'var(--success)';
        setTimeout(() => { indicator.textContent = ''; }, 3000);
      }
    } catch(e) {}
  }

  // Tawarkan pemulihan draf lokal untuk post baru
  if (postId === 'new') {
    try {
      const saved = JSON.parse(localStorage.getItem(storeKey) || 'null');
      if (saved && titleEl.value === '' && contentEl.value === '' && (saved.title || saved.content)) {
        if (confirm('Ditemukan draf lokal yang belum tersimpan ke server.\nJudul: "' + (saved.title || '(tanpa judul)') + '"\nPulihkan?')) {
          titleEl.value   = saved.title   || '';
          contentEl.value = saved.content || '';
          contentEl.dispatchEvent(new Event('input'));
        }
      }
    } catch(e) {}
  }

  titleEl.addEventListener('input',   markDirty);
  contentEl.addEventListener('input', markDirty);

  // Hapus draf lokal saat form dikirim ke server
  form.addEventListener('submit', () => {
    try { localStorage.removeItem(storeKey); } catch(e) {}
  });

  // Peringatan navigasi keluar jika ada perubahan belum disimpan
  window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
  });

  // Ctrl/Cmd+S → simpan draf
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      const draftBtn = form.querySelector('[name="submit_action"][value="draft"]');
      if (draftBtn) draftBtn.click();
    }
  });
})();

// ── Tombol layar penuh editor ──
(function() {
  const btn = document.getElementById('expandEditor');
  const ta  = document.getElementById('contentArea');
  if (!btn || !ta) return;
  let expanded = false;
  const iconExpand   = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>`;
  const iconCompress = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>`;
  btn.addEventListener('click', () => {
    expanded = !expanded;
    if (expanded) {
      ta.style.cssText = 'position:fixed;inset:0;z-index:900;border-radius:0;min-height:100vh;resize:none;padding:2.5rem max(3rem,10vw);font-size:1rem;line-height:1.9;';
      btn.innerHTML = iconCompress;
      btn.title = 'Keluar dari layar penuh (Esc)';
    } else {
      ta.style.cssText = '';
      btn.innerHTML = iconExpand;
      btn.title = 'Layar penuh';
    }
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && expanded) btn.click();
  });
})();
</script>
</body>
</html>
