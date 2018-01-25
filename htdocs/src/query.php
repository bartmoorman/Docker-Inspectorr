<?php
require_once('../inc/auth.class.php');
require_once('../inc/complet.class.php');

$auth = new Auth();

if ($auth->isConfigured() && !$auth->isValidSession()) {
  header('HTTP/1.1 401 Unauthorized');
  exit;
}

$complet = new Complet();

switch ($_REQUEST['function']) {
  case 'getStatuses':
    $data = $complet->statuses;
    break;
  case 'getLibraries':
    $data = $complet->{$_REQUEST['function']}();
    break;
  case 'getLibrarySections':
    $data = $complet->{$_REQUEST['function']}($_REQUEST['library'], $_REQUEST['status']);
    break;
  case 'getLibrarySectionDetails':
    $data = $complet->{$_REQUEST['function']}($_REQUEST['library'], $_REQUEST['status'], $_REQUEST['section']);
    break;
  default:
    $data = array();
}

echo json_encode(array('messages' => $complet->messages, 'data' => $data));
?>
