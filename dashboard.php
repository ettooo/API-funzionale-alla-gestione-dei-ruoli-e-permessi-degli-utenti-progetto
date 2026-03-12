<?php
require_once __DIR__ . '/config/auth.php';
startSession();
requireLogin();

$username = $_SESSION['username'] ?? 'Utente';
$role = $_SESSION['role'] ?? 'free';
$perms = $_SESSION['user_permissions'] ?? [];

$has = static function (string $p) use ($perms): bool {
    return in_array($p, $perms, true);
};

$roleLabels = [
    'free' => ['label' => 'Free', 'tone' => 'free'],
    'premium' => ['label' => 'Premium', 'tone' => 'premium'],
    'admin' => ['label' => 'Admin', 'tone' => 'admin'],
];
$roleInfo = $roleLabels[$role] ?? $roleLabels['free'];

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$pdo = null;
$marketRows = [];
$portfolioRows = [];
$alertsRows = [];
$adminUsers = [];
$roleStats = [];

try {
    $pdo = getDB();

    if ($has('view_market_data')) {
        $marketRows = $pdo->query('SELECT symbol, name, price, change_pct, volume, market_cap, fetched_at FROM market_data ORDER BY symbol LIMIT 10')->fetchAll();
    }

    if ($has('manage_portfolio')) {
        $pfStmt = $pdo->prepare(
            "SELECT p.id AS portfolio_id, p.name AS portfolio_name,
                    pi.id AS item_id, pi.symbol, pi.quantity, pi.purchase_price,
                    (pi.quantity * pi.purchase_price) AS invested
             FROM portfolios p
             LEFT JOIN portfolio_items pi ON pi.portfolio_id = p.id
             WHERE p.user_id = ?
             ORDER BY p.created_at, pi.purchased_at DESC"
        );
        $pfStmt->execute([$_SESSION['user_id']]);
        $portfolioRows = $pfStmt->fetchAll();
    }

    if ($has('set_basic_alerts') || $has('set_advanced_alerts')) {
        $aStmt = $pdo->prepare('SELECT id, symbol, condition_type, threshold, is_active, created_at FROM alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
        $aStmt->execute([$_SESSION['user_id']]);
        $alertsRows = $aStmt->fetchAll();
    }

    if ($has('manage_users')) {
        $adminUsers = $pdo->query(
            "SELECT u.id, u.username, u.email, r.name AS role, u.is_active, u.created_at
             FROM users u
             JOIN roles r ON u.role_id = r.id
             ORDER BY u.created_at DESC
             LIMIT 12"
        )->fetchAll();
    }

    if ($has('view_reports')) {
        $roleStats = $pdo->query(
            "SELECT r.name, COUNT(u.id) AS cnt
             FROM roles r
             LEFT JOIN users u ON r.id = u.role_id
             GROUP BY r.id, r.name
             ORDER BY r.id"
        )->fetchAll();
    }
} catch (Throwable $e) {
    // Le sezioni degradano in modo sicuro in caso di errore DB.
}

$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AuthSystem Pro</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Fraunces:opsz,wght@9..144,600&display=swap');

        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --surface: #fbfdff;
            --text: #11213a;
            --muted: #5f6f88;
            --line: #d9e2ef;
            --brand: #0f6cbd;
            --brand-2: #22a699;
            --warn: #b54708;
            --danger: #b42318;
            --ok: #067647;
            --radius-lg: 18px;
            --radius-md: 12px;
            --shadow: 0 16px 36px rgba(17, 33, 58, 0.10);
            --sidebar-w: 270px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 8% 12%, rgba(34,166,153,0.12), transparent 33%),
                radial-gradient(circle at 92% 90%, rgba(15,108,189,0.14), transparent 30%),
                linear-gradient(155deg, #eef3f9 0%, #f8fbff 100%);
            min-height: 100vh;
        }

        .layout {
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            border-right: 1px solid var(--line);
            background: linear-gradient(180deg, #0f6cbd 0%, #0f5ba0 50%, #0d4f8d 100%);
            color: #f0f7ff;
            padding: 22px 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .brand {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: 1.45rem;
            letter-spacing: .2px;
            padding: 0 10px;
        }

        .nav-title {
            margin: 10px 10px 4px;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(240,247,255,.75);
        }

        .nav a {
            display: block;
            text-decoration: none;
            color: #edf5ff;
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: .92rem;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(255,255,255,.16);
        }

        .user-card {
            margin-top: auto;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 12px;
            padding: 12px;
        }

        .user-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.22);
            font-weight: 800;
        }

        .role-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .role-pill.free { background: #eaf2ff; color: #114b8d; }
        .role-pill.premium { background: #fff4e5; color: #8a4b07; }
        .role-pill.admin { background: #fdecec; color: #912018; }

        .logout {
            margin-top: 8px;
            width: 100%;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            font-size: .88rem;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fff;
            color: #0d4f8d;
        }

        .main {
            padding: 26px;
        }

        .header {
            margin-bottom: 18px;
            animation: rise .35s ease-out;
        }

        .header h1 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: 1.9rem;
            font-weight: 600;
        }

        .header p {
            margin: 8px 0 0;
            color: var(--muted);
        }

        .grid-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 16px;
            animation: rise .35s ease-out;
        }

        .stat-value { font-size: 1.5rem; font-weight: 800; }
        .stat-label { color: var(--muted); font-size: .88rem; }

        .section {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 14px;
            overflow: hidden;
            animation: rise .4s ease-out;
        }

        .section-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .section-head h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }

        .section-body {
            padding: 14px 16px;
        }

        .chip-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            border-radius: 999px;
            padding: 5px 10px;
            font-size: .8rem;
            border: 1px solid;
            font-weight: 600;
        }

        .chip.on { color: var(--ok); border-color: #8de2bb; background: #ecfdf3; }
        .chip.off { color: var(--danger); border-color: #f6b5b0; background: #fff4f3; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
        }

        th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #3c5373;
        }

        tr:last-child td { border-bottom: 0; }

        .up { color: var(--ok); font-weight: 700; }
        .down { color: var(--danger); font-weight: 700; }
        .muted { color: var(--muted); }

        .warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: .9rem;
            margin-bottom: 12px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 11px;
        }

        .kpi strong { display: block; font-size: 1.15rem; margin-top: 4px; }

        @keyframes rise {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1080px) {
            .grid-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .kpi-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 880px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <h2 class="brand">AuthSystem</h2>

            <div class="nav">
                <p class="nav-title">Navigazione</p>
                <a href="dashboard.php" class="active">Dashboard</a>
                <?php if ($has('view_profile')): ?><a href="profile.php">Profilo</a><?php endif; ?>
                <a href="#market">Mercato</a>
                <a href="#portfolio">Portafoglio</a>
                <a href="#alerts">Alert</a>
                <?php if ($has('manage_users')): ?><a href="#admin">Amministrazione</a><?php endif; ?>
            </div>

            <div class="user-card">
                <div class="user-row">
                    <div class="avatar"><?= htmlspecialchars($initial) ?></div>
                    <div>
                        <div><?= htmlspecialchars($username) ?></div>
                        <span class="role-pill <?= htmlspecialchars($roleInfo['tone']) ?>"><?= htmlspecialchars($roleInfo['label']) ?></span>
                    </div>
                </div>
                <a class="logout" href="logout.php">Esci</a>
            </div>
        </aside>

        <main class="main">
            <?php if ($flashError): ?>
                <div class="warning"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <header class="header">
                <h1>Dashboard Operativa</h1>
                <p>Panoramica professionale di accessi, funzionalita e dati operativi.</p>
            </header>

            <section class="grid-stats">
                <article class="card">
                    <div class="stat-value"><?= count($perms) ?></div>
                    <div class="stat-label">Permessi Attivi</div>
                </article>
                <article class="card">
                    <div class="stat-value"><?= htmlspecialchars($roleInfo['label']) ?></div>
                    <div class="stat-label">Ruolo Corrente</div>
                </article>
                <article class="card">
                    <div class="stat-value"><?= date('d/m/Y') ?></div>
                    <div class="stat-label">Data Sessione</div>
                </article>
                <article class="card">
                    <div class="stat-value">Online</div>
                    <div class="stat-label">Stato Account</div>
                </article>
            </section>

            <section class="section">
                <div class="section-head"><h3>Permessi Effettivi</h3></div>
                <div class="section-body">
                    <div class="chip-wrap">
                        <?php
                        $allPerms = [
                            'view_dashboard', 'view_profile', 'edit_profile', 'view_free_content', 'view_premium_content',
                            'download_files', 'manage_users', 'manage_roles', 'view_reports', 'manage_content',
                            'manage_permissions', 'view_market_data', 'view_market_advanced', 'view_ai_analysis',
                            'run_simulation', 'set_basic_alerts', 'set_advanced_alerts', 'manage_portfolio', 'manage_multi_portfolio'
                        ];
                        foreach ($allPerms as $p):
                        ?>
                            <span class="chip <?= $has($p) ? 'on' : 'off' ?>"><?= htmlspecialchars($p) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section id="market" class="section">
                <div class="section-head">
                    <h3>Dati Mercato</h3>
                    <span class="muted">Permesso: view_market_data</span>
                </div>
                <div class="section-body">
                    <?php if ($has('view_market_data')): ?>
                        <?php if (!empty($marketRows)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Symbol</th><th>Nome</th><th>Prezzo</th><th>Var %</th><th>Volume</th><th>Market Cap</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($marketRows as $m): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($m['symbol']) ?></strong></td>
                                            <td><?= htmlspecialchars($m['name']) ?></td>
                                            <td><?= number_format((float)$m['price'], 2, ',', '.') ?></td>
                                            <td class="<?= ((float)$m['change_pct'] >= 0) ? 'up' : 'down' ?>"><?= number_format((float)$m['change_pct'], 2, ',', '.') ?>%</td>
                                            <td><?= number_format((float)$m['volume'], 0, ',', '.') ?></td>
                                            <td><?= number_format((float)$m['market_cap'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="muted">Nessun dato mercato disponibile.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="muted">Accesso negato: manca il permesso `view_market_data`.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="portfolio" class="section">
                <div class="section-head">
                    <h3>Portafoglio Virtuale</h3>
                    <span class="muted">Permesso: manage_portfolio</span>
                </div>
                <div class="section-body">
                    <?php if ($has('manage_portfolio')): ?>
                        <?php
                        $positions = array_values(array_filter($portfolioRows, static fn($r) => !empty($r['item_id'])));
                        $totalInvested = 0.0;
                        foreach ($positions as $p) { $totalInvested += (float)$p['invested']; }
                        ?>
                        <div class="kpi-grid" style="margin-bottom:12px;">
                            <div class="kpi">Posizioni <strong><?= count($positions) ?></strong></div>
                            <div class="kpi">Capitale Investito <strong><?= number_format($totalInvested, 2, ',', '.') ?></strong></div>
                            <div class="kpi">Portafogli <strong><?= count(array_unique(array_column($portfolioRows, 'portfolio_id'))) ?></strong></div>
                        </div>

                        <?php if (!empty($positions)): ?>
                            <table>
                                <thead>
                                    <tr><th>Symbol</th><th>Qta</th><th>Prezzo Acquisto</th><th>Investito</th><th>Portafoglio</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($positions as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['symbol']) ?></strong></td>
                                        <td><?= (int)$row['quantity'] ?></td>
                                        <td><?= number_format((float)$row['purchase_price'], 2, ',', '.') ?></td>
                                        <td><?= number_format((float)$row['invested'], 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($row['portfolio_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="muted">Nessuna posizione in portafoglio.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="muted">Accesso negato: manca il permesso `manage_portfolio`.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="alerts" class="section">
                <div class="section-head">
                    <h3>Alert Prezzo</h3>
                    <span class="muted">Permessi: set_basic_alerts / set_advanced_alerts</span>
                </div>
                <div class="section-body">
                    <?php if ($has('set_basic_alerts') || $has('set_advanced_alerts')): ?>
                        <?php if (!empty($alertsRows)): ?>
                            <table>
                                <thead>
                                    <tr><th>Symbol</th><th>Condizione</th><th>Soglia</th><th>Stato</th><th>Creato Il</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($alertsRows as $a): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($a['symbol']) ?></strong></td>
                                        <td><?= htmlspecialchars($a['condition_type']) ?></td>
                                        <td><?= number_format((float)$a['threshold'], 2, ',', '.') ?></td>
                                        <td><?= ((int)$a['is_active'] === 1) ? 'Attivo' : 'Disattivo' ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="muted">Nessun alert configurato.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="muted">Accesso negato: mancano i permessi sugli alert.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($has('manage_users')): ?>
                <section id="admin" class="section">
                    <div class="section-head"><h3>Amministrazione Utenti</h3></div>
                    <div class="section-body">
                        <?php if (!empty($adminUsers)): ?>
                            <table>
                                <thead>
                                    <tr><th>ID</th><th>Username</th><th>Email</th><th>Ruolo</th><th>Stato</th><th>Registrato</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($adminUsers as $u): ?>
                                    <tr>
                                        <td>#<?= (int)$u['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['role']) ?></td>
                                        <td><?= ((int)$u['is_active'] === 1) ? 'Attivo' : 'Disabilitato' ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="muted">Nessun utente trovato.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($has('view_reports')): ?>
                <section class="section">
                    <div class="section-head"><h3>Report Ruoli</h3></div>
                    <div class="section-body">
                        <div class="kpi-grid">
                            <?php foreach ($roleStats as $s): ?>
                                <div class="kpi">
                                    <?= htmlspecialchars(strtoupper($s['name'])) ?>
                                    <strong><?= (int)$s['cnt'] ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>