<?php
// ============================================
// dashboard.php
// ============================================
require_once __DIR__ . '/config/auth.php';
startSession();
requireLogin();     // Redirect a login.php se non autenticato

$username = $_SESSION['username'] ?? 'Utente';
$role     = $_SESSION['role']     ?? 'free';
$perms    = $_SESSION['user_permissions'] ?? [];

$roleLabels = [
    'free'    => ['label' => 'Free',          'icon' => '🆓', 'color' => '#6c63ff'],
    'premium' => ['label' => 'Premium',       'icon' => '⭐', 'color' => '#f59e0b'],
    'admin'   => ['label' => 'Amministratore','icon' => '🛡️',  'color' => '#ef4444'],
];
$roleInfo = $roleLabels[$role] ?? $roleLabels['free'];

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — AuthSystem</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117; --surface: #1a1d2e; --card: #20243a; --border: #2e3250;
            --accent: #6c63ff; --text: #e8eaf0; --muted: #8b90a8;
            --success: #4caf83; --error: #ff5370; --radius: 14px;
            --sidebar: 240px;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar); background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            padding: 24px 16px; position: fixed;
            top: 0; left: 0; height: 100vh; z-index: 10;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; }
        .sidebar-logo .icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .sidebar-logo .name { font-size: 18px; font-weight: 700; }
        .sidebar-logo .name span { color: var(--accent); }

        nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 9px;
            color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s; margin-bottom: 4px;
        }
        nav a:hover    { background: rgba(108,99,255,.1); color: var(--text); }
        nav a.active   { background: rgba(108,99,255,.18); color: var(--accent); }
        nav a.disabled { opacity: .35; pointer-events: none; }
        nav a .nav-icon { font-size: 16px; width: 20px; text-align: center; }

        .nav-section { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; padding: 10px 14px 4px; }

        .sidebar-footer { margin-top: auto; }
        .user-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 12px 14px; margin-bottom: 12px;
        }
        .user-card .name { font-size: 14px; font-weight: 600; }
        .user-card .role-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 700; padding: 2px 8px;
            border-radius: 20px; margin-top: 4px;
            background: rgba(108,99,255,.15); color: #a5a1ff;
        }
        .btn-logout {
            width: 100%; padding: 10px; background: rgba(255,83,112,.1);
            border: 1px solid rgba(255,83,112,.25); color: var(--error);
            font-size: 13px; font-weight: 600; border-radius: 8px;
            cursor: pointer; transition: background .15s; text-align: center; text-decoration: none; display: block;
        }
        .btn-logout:hover { background: rgba(255,83,112,.2); }

        /* ===== MAIN ===== */
        .main { margin-left: var(--sidebar); padding: 40px; flex: 1; }

        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-size: 28px; font-weight: 700; }
        .page-header p  { color: var(--muted); margin-top: 6px; }

        .welcome-banner {
            background: linear-gradient(135deg, rgba(108,99,255,.15), rgba(139,92,246,.08));
            border: 1px solid rgba(108,99,255,.3);
            border-radius: var(--radius); padding: 28px 32px; margin-bottom: 32px;
            display: flex; align-items: center; gap: 20px;
        }
        .welcome-avatar {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 28px; flex-shrink: 0;
        }
        .welcome-text h2 { font-size: 22px; font-weight: 700; }
        .welcome-text p  { color: var(--muted); margin-top: 4px; font-size: 14px; }

        /* ===== CARDS GRID ===== */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 22px;
        }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 12px; }
        .stat-card .stat-val  { font-size: 26px; font-weight: 700; }
        .stat-card .stat-lbl  { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* ===== SECTIONS ===== */
        .section { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 26px 28px; margin-bottom: 22px; }
        .section h3 { font-size: 17px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        /* Permission list */
        .perm-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .perm-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .perm-chip.has   { background: rgba(76,175,131,.12); border: 1px solid rgba(76,175,131,.3); color: #4caf83; }
        .perm-chip.lacks { background: rgba(255,83,112,.08); border: 1px solid rgba(255,83,112,.2); color: #ff5370; text-decoration: line-through; opacity: .5; }

        /* Content blocks */
        .content-block {
            border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px; margin-bottom: 12px;
            position: relative; overflow: hidden;
        }
        .content-block.locked { opacity: .45; }
        .content-block .lock-badge {
            position: absolute; top: 12px; right: 14px;
            background: rgba(255,83,112,.15); color: var(--error);
            font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px;
        }
        .content-block h4 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
        .content-block p  { color: var(--muted); font-size: 13px; }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 22px; }
        .alert-error { background: rgba(255,83,112,.12); border: 1px solid rgba(255,83,112,.3); color: var(--error); }

        /* Admin table */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 14px; color: var(--muted); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; border-bottom: 1px solid rgba(46,50,80,.5); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }

        .role-pill {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
        }
        .role-pill.free    { background: rgba(108,99,255,.15); color: #a5a1ff; }
        .role-pill.premium { background: rgba(245,158,11,.15);  color: #fbbf24; }
        .role-pill.admin   { background: rgba(239,68,68,.15);   color: #f87171; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main    { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="icon">🔐</div>
        <div class="name">Auth<span>System</span></div>
    </div>

    <nav>
        <div class="nav-section">Generale</div>
        <a href="dashboard.php" class="active">
            <span class="nav-icon">🏠</span> Dashboard
        </a>

        <?php if (can('view_profile')): ?>
        <a href="profile.php">
            <span class="nav-icon">👤</span> Profilo
        </a>
        <?php endif; ?>

        <div class="nav-section">Contenuti</div>
        <a href="#free-section">
            <span class="nav-icon">📄</span> Contenuti Free
        </a>

        <a href="#premium-section" class="<?= can('view_premium_content') ? '' : 'disabled' ?>">
            <span class="nav-icon">⭐</span> Contenuti Premium
        </a>

        <a href="#download-section" class="<?= can('download_files') ? '' : 'disabled' ?>">
            <span class="nav-icon">⬇️</span> Download
        </a>

        <?php if (can('manage_users')): ?>
        <div class="nav-section">Amministrazione</div>
        <a href="#admin-section">
            <span class="nav-icon">⚙️</span> Gestione Utenti
        </a>
        <a href="#reports-section">
            <span class="nav-icon">📊</span> Report
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="name"><?= htmlspecialchars($username) ?></div>
            <div class="role-badge"><?= $roleInfo['icon'] ?> <?= $roleInfo['label'] ?></div>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Esci</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <?php if ($flashError): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Panoramica del tuo account e delle funzionalità disponibili</p>
    </div>

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div class="welcome-avatar">
            <?= mb_strtoupper(mb_substr($username, 0, 1)) ?>
        </div>
        <div class="welcome-text">
            <h2>Benvenuto, <?= htmlspecialchars($username) ?>! 👋</h2>
            <p>Sei loggato come <strong><?= $roleInfo['icon'] ?> <?= $roleInfo['label'] ?></strong>. Hai accesso a <?= count($perms) ?> funzionalità.</p>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="cards-grid">
        <div class="stat-card">
            <div class="stat-icon">🔑</div>
            <div class="stat-val"><?= count($perms) ?></div>
            <div class="stat-lbl">Permessi attivi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><?= $roleInfo['icon'] ?></div>
            <div class="stat-val"><?= $roleInfo['label'] ?></div>
            <div class="stat-lbl">Ruolo assegnato</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-val"><?= date('d/m') ?></div>
            <div class="stat-lbl">Data di oggi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-val">Attivo</div>
            <div class="stat-lbl">Stato account</div>
        </div>
    </div>

    <!-- PERMESSI -->
    <div class="section">
        <h3>🔐 I tuoi permessi</h3>
        <?php
        $allPerms = [
            'view_dashboard'       => 'Accesso Dashboard',
            'view_profile'         => 'Visualizza Profilo',
            'edit_profile'         => 'Modifica Profilo',
            'view_free_content'    => 'Contenuti Free',
            'view_premium_content' => 'Contenuti Premium',
            'download_files'       => 'Download File',
            'manage_users'         => 'Gestione Utenti',
            'manage_roles'         => 'Gestione Ruoli',
            'view_reports'         => 'Visualizza Report',
            'manage_content'       => 'Gestione Contenuti',
        ];
        ?>
        <div class="perm-list">
            <?php foreach ($allPerms as $key => $label): ?>
                <span class="perm-chip <?= in_array($key, $perms) ? 'has' : 'lacks' ?>">
                    <?= in_array($key, $perms) ? '✓' : '✗' ?> <?= $label ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CONTENUTI FREE -->
    <div class="section" id="free-section">
        <h3>📄 Contenuti Free</h3>
        <?php if (can('view_free_content')): ?>
            <div class="content-block">
                <h4>Introduzione alla piattaforma</h4>
                <p>Benvenuto! Questo è il contenuto di base disponibile per tutti gli utenti registrati. Esplora le funzionalità base del sistema.</p>
            </div>
            <div class="content-block">
                <h4>Guida rapida</h4>
                <p>Scopri come navigare la dashboard e gestire il tuo profilo personale.</p>
            </div>
        <?php else: ?>
            <p style="color:var(--muted)">Contenuto non disponibile.</p>
        <?php endif; ?>
    </div>

    <!-- CONTENUTI PREMIUM -->
    <div class="section" id="premium-section">
        <h3>⭐ Contenuti Premium</h3>
        <?php if (can('view_premium_content')): ?>
            <div class="content-block">
                <h4>Analisi avanzata dei dati</h4>
                <p>Accesso completo ai report dettagliati e agli strumenti di analisi avanzata.</p>
            </div>
            <div class="content-block">
                <h4>Risorse esclusive Premium</h4>
                <p>Template, guide approfondite e materiali disponibili solo per gli abbonati premium.</p>
            </div>
        <?php else: ?>
            <div class="content-block locked">
                <div class="lock-badge">🔒 Solo Premium</div>
                <h4>Analisi avanzata dei dati</h4>
                <p>Aggiorna il tuo account a Premium per sbloccare questo contenuto.</p>
            </div>
            <div class="content-block locked">
                <div class="lock-badge">🔒 Solo Premium</div>
                <h4>Risorse esclusive Premium</h4>
                <p>Template, guide approfondite e materiali riservati agli abbonati.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- DOWNLOAD -->
    <?php if (can('download_files')): ?>
    <div class="section" id="download-section">
        <h3>⬇️ Download disponibili</h3>
        <div class="content-block">
            <h4>📦 Pacchetto risorse 2024</h4>
            <p>Raccolta completa di template e strumenti. <a href="#" style="color:var(--accent)">Scarica →</a></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ADMIN SECTION -->
    <?php if (can('manage_users')): ?>
    <div class="section" id="admin-section">
        <h3>⚙️ Pannello Amministrazione</h3>
        <?php
        try {
            $pdo = getDB();
            $users = $pdo->query("
                SELECT u.id, u.username, u.email, r.name AS role, u.is_active, u.created_at
                FROM users u JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC LIMIT 20
            ")->fetchAll();
        } catch (Exception $e) {
            $users = [];
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Ruolo</th><th>Stato</th><th>Registrato</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="role-pill <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><?= $u['is_active'] ? '🟢 Attivo' : '🔴 Disabilitato' ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" style="color:var(--muted);text-align:center">Nessun utente trovato.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (can('view_reports')): ?>
    <div class="section" id="reports-section">
        <h3>📊 Report & Statistiche</h3>
        <?php
        try {
            $stats = $pdo->query("
                SELECT r.name, COUNT(u.id) AS cnt
                FROM roles r LEFT JOIN users u ON r.id = u.role_id
                GROUP BY r.id, r.name
            ")->fetchAll();
        } catch (Exception $e) { $stats = []; }
        ?>
        <div class="cards-grid" style="margin-bottom:0">
            <?php foreach ($stats as $s): ?>
            <div class="stat-card">
                <div class="stat-icon"><?= $roleLabels[$s['name']]['icon'] ?? '👤' ?></div>
                <div class="stat-val"><?= $s['cnt'] ?></div>
                <div class="stat-lbl">Utenti <?= ucfirst($s['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</main>
</body>
</html>
