<?php
require_once __DIR__ . '/auth.php';
require_login();
require_permission('ADMIN_PANEL');
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard ADMIN</title></head>
<body>
<h1>Dashboard ADMIN</h1>
<p>Admin: <?= htmlspecialchars(current_user()['username']) ?></p>

<ul>
  <li>Pannello admin: <?= can('ADMIN_PANEL') ? 'OK' : 'NO' ?></li>
  <li>Gestione utenti: <?= can('USER_MANAGE') ? 'OK' : 'NO' ?></li>
  <li>Gestione API: <?= can('API_MANAGE') ? 'OK' : 'NO' ?></li>
</ul>

<p><a href="logout.php">Logout</a></p>
</body>
</html>
