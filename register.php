<?php
// ============================================
// register.php
// ============================================
require_once __DIR__ . '/config/auth.php';
startSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Richiesta non valida. Riprova.';
    } else {
        $result = registerUser(
            $_POST['username'] ?? '',
            $_POST['email']    ?? '',
            $_POST['password'] ?? ''
        );
        if ($result['success']) {
            $_SESSION['flash_success'] = 'Registrazione completata! Ora puoi accedere.';
            header('Location: login.php');
            exit;
        }
        $error = $result['message'];
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione — AuthSystem</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117; --surface: #1a1d2e; --card: #20243a; --border: #2e3250;
            --accent: #6c63ff; --accent2: #ff6584; --text: #e8eaf0; --muted: #8b90a8;
            --success: #4caf83; --error: #ff5370; --radius: 14px;
        }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--text); padding: 20px;
            background-image:
                radial-gradient(ellipse at 80% 50%, rgba(108,99,255,.12) 0%, transparent 60%),
                radial-gradient(ellipse at 20% 80%, rgba(255,101,132,.08) 0%, transparent 50%);
        }
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 44px 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 24px 60px rgba(0,0,0,.5);
        }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 32px; }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .logo-text { font-size: 22px; font-weight: 700; }
        .logo-text span { color: var(--accent); }
        h1 { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 28px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 7px; text-transform: uppercase; letter-spacing: .5px; }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%; padding: 12px 16px; background: var(--surface);
            border: 1px solid var(--border); border-radius: 9px;
            color: var(--text); font-size: 15px; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(108,99,255,.2); }
        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, var(--accent2), #e91e8c);
            color: #fff; font-size: 15px; font-weight: 600;
            border: none; border-radius: 9px; cursor: pointer;
            transition: opacity .2s, transform .1s; margin-top: 6px;
        }
        .btn:hover { opacity: .92; }
        .btn:active { transform: scale(.98); }
        .alert { padding: 12px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(255,83,112,.12); border: 1px solid rgba(255,83,112,.3); color: var(--error); }
        .switch { text-align: center; margin-top: 22px; font-size: 14px; color: var(--muted); }
        .switch a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .switch a:hover { text-decoration: underline; }

        /* Badge ruolo gratuito */
        .role-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(108,99,255,.12); border: 1px solid rgba(108,99,255,.3);
            color: #a5a1ff; padding: 5px 12px; border-radius: 20px;
            font-size: 13px; font-weight: 600; margin-bottom: 24px;
        }

        /* Password strength */
        .pwd-hints { font-size: 12px; color: var(--muted); margin-top: 6px; line-height: 1.6; }
        .pwd-hints span { display: inline-block; margin-right: 8px; }
        .pwd-hints .ok   { color: var(--success); }
        .pwd-hints .fail { color: var(--error); }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">🔐</div>
        <div class="logo-text">Auth<span>System</span></div>
    </div>

    <h1>Crea un account</h1>
    <p class="subtitle">Inizia gratuitamente, senza carta di credito</p>

    <div class="role-badge">🆓 Ruolo: Utente Free</div>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="regForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   placeholder="mario_rossi" minlength="3" maxlength="50"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="mario@esempio.it"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Min. 8 caratteri" required
                   oninput="checkPassword(this.value)">
            <div class="pwd-hints" id="pwdHints">
                <span id="h-len">○ 8+ caratteri</span>
                <span id="h-upper">○ Maiuscola</span>
                <span id="h-num">○ Numero</span>
            </div>
        </div>

        <button type="submit" class="btn">Registrati →</button>
    </form>

    <div class="switch">
        Hai già un account? <a href="login.php">Accedi</a>
    </div>
</div>

<script>
function checkPassword(val) {
    const len   = document.getElementById('h-len');
    const upper = document.getElementById('h-upper');
    const num   = document.getElementById('h-num');

    function set(el, ok, label) {
        el.textContent = (ok ? '✓ ' : '○ ') + label;
        el.className   = ok ? 'ok' : 'fail';
    }
    set(len,   val.length >= 8,      '8+ caratteri');
    set(upper, /[A-Z]/.test(val),    'Maiuscola');
    set(num,   /[0-9]/.test(val),    'Numero');
}
</script>
</body>
</html>
