<?php
require_once __DIR__ . '/auth.php';
require_login();

// DEMO: nessun upgrade reale, vai al paywall
header("Location: paywall_premium.php");
exit;
