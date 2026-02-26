<?php
// ============================================
// login.php
// ============================================
require_once __DIR__ . '/config/auth.php';
startSession();

// Se già loggato, redirect dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protezione CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Richiesta non valida. Riprova.';
    } else {
        $result = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
}

// Genera CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AuthSystem</title>
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0f1117;
            --surface:   #1a1d2e;
            --card:      #20243a;
            --border:    #2e3250;
            --accent:    #6c63ff;
            --accent2:   #ff6584;
            --text:      #e8eaf0;
            --muted:     #8b90a8;
            --success:   #4caf83;
            --error:     #ff5370;
            --radius:    14px;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--text);
            padding: 20px;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(108,99,255,.12) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255,101,132,.08) 0%, transparent 50%);
        }

        /* ===== CARD ===== */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 24px 60px rgba(0,0,0,.5);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .logo-text { font-size: 22px; font-weight: 700; }
        .logo-text span { color: var(--accent); }

        h1 { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 28px; }

        /* ===== FORM ===== */
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 7px; text-transform: uppercase; letter-spacing: .5px; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            font-size: 15px;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(108,99,255,.2);
        }

        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            margin-top: 6px;
        }
        .btn:hover  { opacity: .92; }
        .btn:active { transform: scale(.98); }

        /* ===== ALERT ===== */
        .alert {
            padding: 12px 16px;
            border-radius: 9px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-error   { background: rgba(255,83,112,.12); border: 1px solid rgba(255,83,112,.3); color: var(--error); }
        .alert-success { background: rgba(76,175,131,.12); border: 1px solid rgba(76,175,131,.3); color: var(--success); }

        /* ===== SWITCH LINK ===== */
        .switch { text-align: center; margin-top: 22px; font-size: 14px; color: var(--muted); }
        .switch a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .switch a:hover { text-decoration: underline; }

        /* ===== DEMO HINT ===== */
        .demo-hint {
            margin-top: 24px;
            padding: 14px;
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: 9px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
        }
        .demo-hint strong { color: var(--text); }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">🔐</div>
        <div class="logo-text">Auth<span>System</span></div>
    </div>

    <h1>Bentornato</h1>
    <p class="subtitle">Accedi al tuo account per continuare</p>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="tuaemail@esempio.it"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn">Accedi →</button>
    </form>

    <div class="switch">
        Non hai un account? <a href="register.php">Registrati gratis</a>
    </div>

    <div class="demo-hint">
        <strong>🧪 Account demo admin:</strong><br>
        Email: <code>admin@example.com</code><br>
        Password: <code>Admin@1234</code>
    </div>
</div>
</body>
</html>
