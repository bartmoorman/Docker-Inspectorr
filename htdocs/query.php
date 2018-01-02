<?php
require_once('inc/complet.class.php');

$obj = new Complet('/data/com.plexapp.plugins.library.db');

switch ($_REQUEST['function']) {
  case 'getStatuses':
    $data = $obj->statuses;
    break;
  case 'getLibraries':
    $data = $obj->{$_REQUEST['function']}();
    break;
  case 'getLibraryDetails':
    $data = $obj->{$_REQUEST['function']}($_REQUEST['library']);
    break;
  case 'getLibrarySections':
    $data = $obj->{$_REQUEST['function']}($_REQUEST['library'], $_REQUEST['status']);
    break;
  case 'getLibrarySectionDetails':
    $data = $obj->{$_REQUEST['function']}($_REQUEST['library'], $_REQUEST['status'], $_REQUEST['section']);
    break;
  default:
    $data = array();
}

echo json_encode($data);
?>
