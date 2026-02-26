<?php
// profile.php
require_once __DIR__ . '/config/auth.php';
startSession();
requireLogin();
requirePermission('view_profile');

$username = $_SESSION['username'];
$email    = $_SESSION['email'];
$role     = $_SESSION['role'];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && can('edit_profile')) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Richiesta non valida.';
    } else {
        $newUsername = trim($_POST['new_username'] ?? '');
        if (strlen($newUsername) >= 3 && strlen($newUsername) <= 50) {
            $pdo = getDB();
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$newUsername, $_SESSION['user_id']]);
            if ($check->fetch()) {
                $error = 'Username già in uso.';
            } else {
                $upd = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $upd->execute([$newUsername, $_SESSION['user_id']]);
                $_SESSION['username'] = $newUsername;
                $username = $newUsername;
                $success = 'Profilo aggiornato con successo!';
            }
        } else {
            $error = 'Username non valido (3-50 caratteri).';
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo — AuthSystem</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#0f1117;--surface:#1a1d2e;--card:#20243a;--border:#2e3250;--accent:#6c63ff;--text:#e8eaf0;--muted:#8b90a8;--success:#4caf83;--error:#ff5370;--radius:14px; }
        body { background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px; }
        .card { background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:40px;width:100%;max-width:460px; }
        h1 { font-size:24px;font-weight:700;margin-bottom:6px; }
        p.sub { color:var(--muted);font-size:14px;margin-bottom:28px; }
        .form-group { margin-bottom:16px; }
        label { display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px; }
        input[type=text],input[type=email] { width:100%;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:9px;color:var(--text);font-size:15px;outline:none;transition:border-color .2s; }
        input:focus { border-color:var(--accent); }
        input[readonly] { opacity:.5;cursor:not-allowed; }
        .btn { width:100%;padding:13px;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;font-size:15px;font-weight:600;border:none;border-radius:9px;cursor:pointer;margin-top:6px; }
        .alert { padding:12px 16px;border-radius:9px;font-size:14px;margin-bottom:20px; }
        .alert-error   { background:rgba(255,83,112,.12);border:1px solid rgba(255,83,112,.3);color:var(--error); }
        .alert-success { background:rgba(76,175,131,.12);border:1px solid rgba(76,175,131,.3);color:var(--success); }
        .back { display:inline-block;margin-bottom:20px;color:var(--accent);text-decoration:none;font-size:14px; }
    </style>
</head>
<body>
<div class="card">
    <a href="dashboard.php" class="back">← Torna alla Dashboard</a>
    <h1>👤 Il tuo profilo</h1>
    <p class="sub">Gestisci le informazioni del tuo account</p>

    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($email) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Ruolo</label>
            <input type="text" value="<?= htmlspecialchars(ucfirst($role)) ?>" readonly>
        </div>

        <?php if (can('edit_profile')): ?>
        <div class="form-group">
            <label for="new_username">Username</label>
            <input type="text" id="new_username" name="new_username" value="<?= htmlspecialchars($username) ?>" required>
        </div>
        <button type="submit" class="btn">Salva modifiche</button>
        <?php else: ?>
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
