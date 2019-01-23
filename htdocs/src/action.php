<?php
require_once('../inc/inspectorr.class.php');
$inspectorr = new Inspectorr(false, false, false, false);

$output = $logFields = ['success' => null, 'message' => null];
$log = [];
$putEvent = true;

switch ($_REQUEST['func']) {
  case 'authenticateSession':
    if (!empty($_REQUEST['username']) && !empty($_REQUEST['password'])) {
      $output['success'] = $inspectorr->authenticateSession($_REQUEST['username'], $_REQUEST['password']);
      $log['username'] = $_REQUEST['username'];
    } else {
      header('HTTP/1.1 400 Bad Request');
      $output['success'] = false;
      $output['message'] = 'Missing arguments';
    }
    break;
  case 'createUser':
    if (!$inspectorr->isConfigured() || ($inspectorr->isValidSession() && $inspectorr->isAdmin())) {
      if (!empty($_REQUEST['username']) && !empty($_REQUEST['password']) && !empty($_REQUEST['first_name']) && !empty($_REQUEST['role'])) {
        $last_name = !empty($_REQUEST['last_name']) ? $_REQUEST['last_name'] : null;
        $output['success'] = $inspectorr->createUser($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['first_name'], $last_name, $_REQUEST['role']);
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'createApp':
    if ($dashboard->isValidSession() && $dashboard->isAdmin()) {
      if (!empty($_REQUEST['name'])) {
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
        $begin = !empty($_REQUEST['begin']) ? $_REQUEST['begin'] : null;
        $end = !empty($_REQUEST['end']) ? $_REQUEST['end'] : null;
        $output['success'] = $dashboard->createApp($_REQUEST['name'], $token, $begin, $end);
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'No name supplied';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'updateUser':
    if ($inspectorr->isValidSession() && $inspectorr->isAdmin()) {
      if (!empty($_REQUEST['user_id']) && !empty($_REQUEST['username']) && !empty($_REQUEST['first_name']) && !empty($_REQUEST['role'])) {
        $password = !empty($_REQUEST['password']) ? $_REQUEST['password'] : null;
        $last_name = !empty($_REQUEST['last_name']) ? $_REQUEST['last_name'] : null;
        $output['success'] = $inspectorr->updateUser($_REQUEST['user_id'], $_REQUEST['username'], $password, $_REQUEST['first_name'], $last_name, $_REQUEST['role']);
        $log['user_id'] = $_REQUEST['user_id'];
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'updateApp':
    if ($dashboard->isValidSession() && $dashboard->isAdmin()) {
      if (!empty($_REQUEST['app_id']) && !empty($_REQUEST['name']) && !empty($_REQUEST['token'])) {
        $begin = !empty($_REQUEST['begin']) ? $_REQUEST['begin'] : null;
        $end = !empty($_REQUEST['end']) ? $_REQUEST['end'] : null;
        $output['success'] = $dashboard->updateApp($_REQUEST['app_id'], $_REQUEST['name'], $_REQUEST['token'], $begin, $end);
        $log['app_id'] = $_REQUEST['app_id'];
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'modifyObject':
    if ($inspectorr->isValidSession() && $inspectorr->isAdmin()) {
      if (!empty($_REQUEST['action']) && !empty($_REQUEST['type']) && !empty($_REQUEST['value'])) {
        $output['success'] = $inspectorr->modifyObject($_REQUEST['action'], $_REQUEST['type'], $_REQUEST['value']);
        $log['action'] = $_REQUEST['action'];
        $log['type'] = $_REQUEST['type'];
        $log['value'] = $_REQUEST['value'];
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'getObjectDetails':
    if ($inspectorr->isValidSession() && $inspectorr->isAdmin()) {
      if (!empty($_REQUEST['type']) && !empty($_REQUEST['value'])) {
        if ($output['data'] = $inspectorr->getObjectDetails($_REQUEST['type'], $_REQUEST['value'])) {
          $output['success'] = true;
          $putEvent = false;
        } else {
          $output['success'] = false;
          $log['type'] = $_REQUEST['type'];
          $log['value'] = $_REQUEST['value'];
        }
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'getLibraryStatusSectionDetails':
    if ($inspectorr->isValidSession()) {
      if (!empty($_REQUEST['tab']) && !empty($_REQUEST['library']) && !empty($_REQUEST['status']) && !empty($_REQUEST['section'])) {
        if ($output['data'] = $inspectorr->getLibraryStatusSectionDetails($_REQUEST['tab'], $_REQUEST['library'], $_REQUEST['status'], $_REQUEST['section'])) {
          $output['success'] = true;
          $putEvent = false;
        } else {
          $output['success'] = false;
          $log['tab'] = $_REQUEST['tab'];
          $log['library'] = $_REQUEST['library'];
          $log['status'] = $_REQUEST['status'];
          $log['section'] = $_REQUEST['section'];
        }
      } else {
        header('HTTP/1.1 400 Bad Request');
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      header('HTTP/1.1 403 Forbidden');
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
}

if ($putEvent) {
  $user_id = array_key_exists('authenticated', $_SESSION) ? $_SESSION['user_id'] : null;
  $inspectorr->putEvent($user_id, $_REQUEST['func'], array_merge(array_intersect_key($output, $logFields), $log));
}

header('Content-Type: application/json');
echo json_encode($output);
?>
