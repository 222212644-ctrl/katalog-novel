<?php
// ============================================================
// login.php — Autentikasi penulis (kredensial di backend saja)
// Tidak menyimpan akun di database.
// ============================================================
// Deteksi HTTPS yang benar untuk LiteSpeed / hosting dengan proxy
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Sudah login → redirect ke dashboard
if (!empty($_SESSION['loggedin'])) {
    header('Location: author.php');
    exit;
}

// ── Kredensial penulis (tersimpan di backend, TIDAK di database) ──
// Username dan bcrypt hash password langsung di sini.
// Untuk mengganti password, generate hash baru via SSH hosting:
//   php -r "echo password_hash('PASSWORD_BARU', PASSWORD_BCRYPT, ['cost'=>12]);"
// Lalu ganti nilai AUTHOR_PASS_HASH di bawah.
const AUTHOR_USERNAME  = 'tansiswo@siagian';
const AUTHOR_PASS_HASH = '$2y$12$LL239/tn/O8lUm3di04sROJelGxHWYeCexm.dln6FNKjZ3fRKe5j.';

// ── Rate limiting via session ──
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_lockout']  = 0;
}

$error       = '';
$isLocked    = false;
$lockSeconds = 0;

if ((int)$_SESSION['login_lockout'] > time()) {
    $isLocked    = true;
    $lockSeconds = (int)$_SESSION['login_lockout'] - time();
}

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $csrfInput = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfInput)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        unset($_SESSION['csrf_token']);

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']     ?? '';

        // Timing-safe: selalu jalankan password_verify agar response time konsisten
        $userOk = hash_equals(AUTHOR_USERNAME, $username);
        $passOk = password_verify($password, AUTHOR_PASS_HASH);
        if (!$userOk) password_verify('dummy_constant_time', AUTHOR_PASS_HASH);

        if ($userOk && $passOk) {
            session_regenerate_id(true);
            $_SESSION['loggedin']       = true;
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_lockout']  = 0;
            header('Location: author.php');
            exit;
        } else {
            $_SESSION['login_attempts'] = (int)$_SESSION['login_attempts'] + 1;
            if ((int)$_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lockout'] = time() + 300;
                $isLocked    = true;
                $lockSeconds = 300;
                $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 5 menit.';
            } else {
                $remaining = 5 - (int)$_SESSION['login_attempts'];
                $error = "Username atau password salah. Sisa percobaan: {$remaining}.";
            }
        }
    }
}

// ── CSRF token ──
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Penulis — M. Tansiswo Siagian</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh;
      background:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(212,168,83,0.12), transparent),
        radial-gradient(ellipse 50% 40% at 90% 80%, rgba(212,168,83,0.06), transparent),
        var(--bg);
    }
    .login-wrap { width: 100%; max-width: 420px; padding: 1.5rem; }

    .login-logo { text-align: center; margin-bottom: 2.5rem; }
    .login-logo a {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem; font-weight: 700;
      color: var(--accent); text-decoration: none;
      display: inline-block; letter-spacing: 0.02em;
    }
    .login-logo p {
      color: var(--muted); font-size: 0.82rem;
      margin-top: 0.4rem; letter-spacing: 0.08em; text-transform: uppercase;
    }

    .login-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2.25rem 2rem 2rem;
      box-shadow: 0 24px 48px -12px rgba(0,0,0,0.4);
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .login-card:focus-within {
      border-color: rgba(212,168,83,0.35);
      box-shadow: 0 24px 48px -12px rgba(0,0,0,0.4), 0 0 0 1px rgba(212,168,83,0.15);
    }
    .login-card h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.75rem; font-weight: 700; margin-bottom: 0.35rem;
    }
    .login-card .subtitle { color: var(--muted); font-size: 0.85rem; margin-bottom: 1.75rem; }

    .form-grid { display: flex; flex-direction: column; gap: 1.1rem; }

    .input-wrap { position: relative; }
    .input-wrap .ico {
      position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
      color: var(--muted); pointer-events: none; display: flex;
    }
    .input-wrap .form-control { padding-left: 2.6rem; }
    .input-wrap .toggle-pw {
      position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: var(--muted); cursor: pointer;
      padding: 0; display: flex; align-items: center; transition: color 0.2s;
    }
    .input-wrap .toggle-pw:hover { color: var(--fg); }
    .input-wrap input[type="password"],
    .input-wrap input[type="text"]   { padding-right: 2.8rem; }

    .lock-banner {
      background: rgba(248,81,73,0.08); border: 1px solid rgba(248,81,73,0.25);
      border-radius: var(--radius-sm); padding: 0.75rem 1rem;
      color: var(--error); font-size: 0.85rem; text-align: center;
      margin-bottom: 1rem;
    }
    #countdown { font-weight: 700; font-variant-numeric: tabular-nums; }

    .login-footer {
      margin-top: 1.5rem; text-align: center;
      font-size: 0.82rem; color: var(--muted);
    }
    .login-footer a { color: var(--accent); text-decoration: none; }
    .login-footer a:hover { color: var(--accent-dim); }
  </style>
</head>
<body>
<div class="noise-overlay"></div>

<div class="login-wrap">
  <div class="login-logo">
    <a href="index.php">M. TS</a>
    <p>Area Penulis</p>
  </div>

  <div class="login-card">
    <h2>Selamat Datang</h2>
    <p class="subtitle">Masuk untuk mengelola konten Anda.</p>

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:1.25rem;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
    <div class="lock-banner">
      🔒 Akun dikunci sementara. Coba lagi dalam
      <span id="countdown"><?= (int)$lockSeconds ?></span> detik.
    </div>
    <?php endif; ?>

    <form method="POST" action="" class="form-grid"
          <?= $isLocked ? 'style="opacity:.45;pointer-events:none;"' : '' ?>>
      <input type="hidden" name="csrf_token"
             value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <span class="ico">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input type="text" id="username" name="username" class="form-control"
                 placeholder="username@domain" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="ico">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••••" required autocomplete="current-password">
          <button type="button" class="toggle-pw" id="togglePw" aria-label="Tampilkan password">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg"
              style="width:100%;justify-content:center;margin-top:0.5rem;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Masuk ke Dashboard
      </button>
    </form>
  </div>

  <div class="login-footer">
    <a href="index.php">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
           style="vertical-align:middle;margin-right:3px;">
        <path d="M19 12H5M12 5l-7 7 7 7"/>
      </svg>
      Kembali ke halaman utama
    </a>
  </div>
</div>

<?php if ($isLocked): ?>
<script>
(function() {
  let s = <?= (int)$lockSeconds ?>;
  const el = document.getElementById('countdown');
  if (!el) return;
  const iv = setInterval(() => {
    s--;
    if (s <= 0) { clearInterval(iv); location.reload(); }
    else el.textContent = s;
  }, 1000);
})();
</script>
<?php endif; ?>

<script>
(function() {
  const btn   = document.getElementById('togglePw');
  const input = document.getElementById('password');
  if (!btn || !input) return;
  const iconShow = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
  const iconHide = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
  btn.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type    = show ? 'text' : 'password';
    btn.innerHTML = show ? iconHide : iconShow;
    btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });
})();
</script>
</body>
</html>