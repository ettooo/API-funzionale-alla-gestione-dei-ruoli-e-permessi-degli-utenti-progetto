<?php
require_once __DIR__ . '/auth.php';

if (current_user()) {
  redirect_dashboard();
} else {
  header("Location: login.php");
  exit;
}
