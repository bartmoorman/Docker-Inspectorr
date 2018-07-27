<?php
require_once('inc/inspectorr.class.php');
$inspectorr = new Inspectorr(true, true, false, false);

if ($inspectorr->deauthenticateSession()) {
  header('Location: login.php');
} else {
  header('Location: index.php');
}
?>
