<?php
require_once('inc/auth.class.php');

$auth = new Auth();

if ($auth->isConfigured()) {
  $auth->deauthenticateSession();
  header('Location: login.php');
  exit;
} else {
  header('Location: ' . dirname($_SERVER['PHP_SELF']));
  exit;
}
?>
