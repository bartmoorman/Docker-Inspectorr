<?php
require_once('../inc/inspectorr.class.php');
$inspectorr = new Inspectorr(false, false, false, false);

$output = $logFields = array('success' => null, 'message' => null);
$log = array();
$putEvent = true;

switch ($_REQUEST['func']) {
  case 'authenticateSession':
    if (!empty($_REQUEST['username']) && !empty($_REQUEST['password'])) {
      $output['success'] = $inspectorr->authenticateSession($_REQUEST['username'], $_REQUEST['password']);
      $log['username'] = $_REQUEST['username'];
      usleep(rand(1000000, 1250000));
    } else {
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
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
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
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'modifyUser':
    if ($inspectorr->isValidSession() && $inspectorr->isAdmin()) {
      if (!empty($_REQUEST['action']) && !empty($_REQUEST['user_id'])) {
        $output['success'] = $inspectorr->modifyUser($_REQUEST['action'], $_REQUEST['user_id']);
        $log['action'] = $_REQUEST['action'];
        $log['user_id'] = $_REQUEST['user_id'];
      } else {
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
  case 'getUserDetails':
    if ($inspectorr->isValidSession() && $inspectorr->isAdmin()) {
      if (!empty($_REQUEST['user_id'])) {
        if ($output['data'] = $inspectorr->getUserDetails($_REQUEST['user_id'])) {
          $output['success'] = true;
          $putEvent = false;
        } else {
          $output['success'] = false;
          $log['user_id'] = $_REQUEST['user_id'];
        }
      } else {
        $output['success'] = false;
        $output['message'] = 'No user id supplied';
      }
    } else {
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
        $output['success'] = false;
        $output['message'] = 'Missing arguments';
      }
    } else {
      $output['success'] = false;
      $output['message'] = 'Unauthorized';
    }
    break;
}

if ($putEvent) {
  $inspectorr->putEvent($_REQUEST['func'], array_merge(array_intersect_key($output, $logFields), $log));
}

header('Content-Type: application/json');
echo json_encode($output);
?>
