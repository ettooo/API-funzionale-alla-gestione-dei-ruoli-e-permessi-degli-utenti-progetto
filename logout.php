<?php
// logout.php
require_once __DIR__ . '/config/auth.php';
startSession();
logoutUser();
header('Location: login.php');
exit;
