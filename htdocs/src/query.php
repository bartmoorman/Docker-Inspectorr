<?php
require_once('../inc/auth.class.php');
require_once('../inc/complet.class.php');

$auth = new Auth();

if ($auth->isConfigured() && !$auth->isValidSession()) {
  header('HTTP/1.1 401 Unauthorized');
  exit;
}

$complet = new Complet();

$function = array_key_exists('function', $_REQUEST) ? $_REQUEST['function'] : null;
$tab = array_key_exists('tab', $_REQUEST) ? $_REQUEST['tab'] : 'indexStatus';

switch ($function) {
  case 'getStatuses':
  case 'getLibraries':
    $data = $complet->{$function}($tab);
    break;
  case 'getLibrarySections':
    $data = $complet->{$function}($tab, $_REQUEST['library'], $_REQUEST['status']);
    break;
  case 'getLibrarySectionDetails':
    $data = $complet->{$function}($tab, $_REQUEST['library'], $_REQUEST['status'], $_REQUEST['section']);
    break;
  default:
    $data = array('nope');
}

echo json_encode(array('messages' => $complet->messages, 'data' => $data));
?>
