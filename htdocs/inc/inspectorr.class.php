<?php
ini_set('session.save_path', '/config/sessions');
ini_set('session.gc_maxlifetime', 24 * 60 * 60);
ini_set('session.use_strict_mode', true);
ini_set('session.cookie_lifetime', 24 * 60 * 60);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

class Inspectorr {
  private $dbFile = '/config/inspectorr.db';
  private $dbConn;
  private $plexDbConn;
  public $pageLimit = 20;

  public function __construct($requireConfigured = true, $requireValidSession = true, $requireAdmin = true, $requireIndex = false) {
    session_start();

    if (is_writable($this->dbFile)) {
      $this->connectDb($this->dbConn, $this->dbFile);
    } elseif (is_writable(dirname($this->dbFile))) {
      $this->connectDb($this->dbConn, $this->dbFile);
      $this->initDb();
    }

    if ($this->isConfigured()) {
      if ($this->isValidSession()) {
        if (($requireAdmin && !$this->isAdmin()) || $requireIndex) {
          header('Location: index.php');
          exit;
        }
      } elseif ($requireValidSession) {
        header('Location: login.php');
        exit;
      }
    } elseif ($requireConfigured) {
      header('Location: setup.php');
      exit;
    }

    if (is_readable(getenv('PMS_DATABASE'))) {
      $this->connectDb($this->plexDbConn, getenv('PMS_DATABASE'));
    }
  }

  private function connectDb(&$conn, $file) {
    if ($conn = new SQLite3($file)) {
      $conn->busyTimeout(500);
      $conn->exec('PRAGMA journal_mode = WAL');
      return true;
    }
    return false;
  }

  private function initDb() {
    $query = <<<EOQ
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `password` TEXT NOT NULL,
  `first_name` TEXT NOT NULL,
  `last_name` TEXT,
  `role` TEXT NOT NULL,
  `disabled` INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS `events` (
  `event_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `date` INTEGER DEFAULT (STRFTIME('%s', 'now')),
  `user_id` INTEGER,
  `action` TEXT,
  `message` BLOB,
  `remote_addr` INTEGER
);
EOQ;
    if ($this->dbConn->exec($query)) {
      return true;
    }
    return false;
  }

  public function isConfigured() {
    if ($this->getCount('users')) {
      return true;
    }
    return false;
  }

  public function isValidSession() {
    if (array_key_exists('authenticated', $_SESSION) && $this->isValidUser('user_id', $_SESSION['user_id'])) {
      return true;
    }
    return false;
  }

  public function isAdmin() {
    $user_id = $_SESSION['user_id'];
    $query = <<<EOQ
SELECT COUNT(*)
FROM `users`
WHERE `user_id` = '{$user_id}'
AND `role` = 'admin';
EOQ;
    if ($this->dbConn->querySingle($query)) {
      return true;
    }
    return false;
  }

  public function isValidCredentials($username, $password) {
    $username = $this->dbConn->escapeString($username);
    $query = <<<EOQ
SELECT `password`
FROM `users`
WHERE `username` = '{$username}'
EOQ;
    if (password_verify($password, $this->dbConn->querySingle($query))) {
      return true;
    }
    return false;
  }

  public function isValidUser($type, $value) {
    $type = $this->dbConn->escapeString($type);
    $value = $this->dbConn->escapeString($value);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `users`
WHERE `{$type}` = '{$value}'
AND NOT `disabled`;
EOQ;
    if ($this->dbConn->querySingle($query)) {
      return true;
    }
    return false;
  }

  public function authenticateSession($username, $password) {
    if ($this->isValidCredentials($username, $password)) {
      $username = $this->dbConn->escapeString($username);
      $query = <<<EOQ
SELECT `user_id`
FROM `users`
WHERE `username` = '{$username}';
EOQ;
      if ($user_id = $this->dbConn->querySingle($query)) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user_id;
        return true;
      }
    }
    return false;
  }

  public function deauthenticateSession() {
    if (session_unset() && session_destroy()) {
      return true;
    }
    return false;
  }

  public function createUser($username, $password, $first_name, $last_name = null, $role) {
    $username = $this->dbConn->escapeString($username);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `users`
WHERE `username` = '{$username}';
EOQ;
    if (!$this->dbConn->querySingle($query)) {
      $password = password_hash($password, PASSWORD_DEFAULT);
      $first_name = $this->dbConn->escapeString($first_name);
      $last_name = $this->dbConn->escapeString($last_name);
      $role = $this->dbConn->escapeString($role);
      $query = <<<EOQ
INSERT
INTO `users` (`username`, `password`, `first_name`, `last_name`, `role`)
VALUES ('{$username}', '{$password}', '{$first_name}', '{$last_name}', '{$role}');
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function updateUser($user_id, $username, $password = null, $first_name, $last_name = null, $role) {
    $user_id = $this->dbConn->escapeString($user_id);
    $username = $this->dbConn->escapeString($username);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `users`
WHERE `user_id` != '{$user_id}'
AND `username` = '{$username}';
EOQ;
    if (!$this->dbConn->querySingle($query)) {
      if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $passwordQuery = <<<EOQ
  `password` = '{$password}',
EOQ;
      }
      $first_name = $this->dbConn->escapeString($first_name);
      $last_name = $this->dbConn->escapeString($last_name);
      $role = $this->dbConn->escapeString($role);
      $query = <<<EOQ
UPDATE `users`
SET
  `username` = '{$username}',
{$passwordQuery}
  `first_name` = '{$first_name}',
  `last_name` = '{$last_name}',
  `role` = '{$role}'
WHERE `user_id` = '{$user_id}';
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function modifyUser($action, $user_id) {
    $user_id = $this->dbConn->escapeString($user_id);
    switch ($action) {
      case 'enable':
        $query = <<<EOQ
UPDATE `users`
SET `disabled` = '0'
WHERE `user_id` = '{$user_id}';
EOQ;
        break;
      case 'disable':
        $query = <<<EOQ
UPDATE `users`
SET `disabled` = '1'
WHERE `user_id` = '{$user_id}';
EOQ;
        break;
      case 'delete':
        $query = <<<EOQ
DELETE
FROM `users`
WHERE `user_id` = '{$user_id}';
DELETE
FROM `events`
WHERE `user_id` = '{$user_id}';
EOQ;
        break;
    }
    if ($this->dbConn->exec($query)) {
      return true;
    }
    return false;
  }

  public function getUsers() {
    $query = <<<EOQ
SELECT `user_id`, `username`, `first_name`, `last_name`, `role`, `disabled`
FROM `users`
ORDER BY `last_name`, `first_name`
EOQ;
    if ($users = $this->dbConn->query($query)) {
      $output = array();
      while ($user = $users->fetchArray(SQLITE3_ASSOC)) {
        $output[] = $user;
      }
      return $output;
    }
    return false;
  }

  public function getUserDetails($user_id) {
    $user_id = $this->dbConn->escapeString($user_id);
    $query = <<<EOQ
SELECT `user_id`, `username`, `first_name`, `last_name`, `role`, `disabled`
FROM `users`
WHERE `user_id` = '{$user_id}';
EOQ;
    if ($user = $this->dbConn->querySingle($query, true)) {
      return $user;
    }
    return false;
  }

  public function getCount($type) {
    $type = $this->dbConn->escapeString($type);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `{$type}`;
EOQ;
    if ($count = $this->dbConn->querySingle($query)) {
      return $count;
    }
    return false;
  }

  public function putEvent($action, $message = array()) {
    $user_id = array_key_exists('authenticated', $_SESSION) ? $_SESSION['user_id'] : null;
    $action = $this->dbConn->escapeString($action);
    $message = $this->dbConn->escapeString(json_encode($message));
    $remote_addr = ip2long(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    $query = <<<EOQ
INSERT
INTO `events` (`user_id`, `action`, `message`, `remote_addr`)
VALUES ('{$user_id}', '{$action}', '{$message}', '{$remote_addr}');
EOQ;
    if ($this->dbConn->exec($query)) {
      return true;
    }
    return false;
  }

  public function getEvents($page = 1) {
    $start = ($page - 1) * $this->pageLimit;
    $query = <<<EOQ
SELECT `event_id`, STRFTIME('%s', `date`, 'unixepoch', 'localtime') AS `date`, `user_id`, `first_name`, `last_name`, `action`, `message`, `remote_addr`, `disabled`
FROM `events`
LEFT JOIN `users` USING (`user_id`)
ORDER BY `date` DESC
LIMIT {$start}, {$this->pageLimit};
EOQ;
    if ($events = $this->dbConn->query($query)) {
      $output = array();
      while ($event = $events->fetchArray(SQLITE3_ASSOC)) {
        $output[] = $event;
      }
      return $output;
    }
    return false;
  }

  public function getLibraries() {
    $query = <<<EOQ
SELECT `library_sections`.`id`, `library_sections`.`name`, COUNT(*) AS `count`
FROM `library_sections`
JOIN `metadata_items` ON `metadata_items`.`library_section_id` = `library_sections`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `library_sections`.`section_type` IN (1,2)
GROUP BY `library_sections`.`id`
ORDER BY `library_sections`.`name`;
EOQ;
    if ($libraries = $this->plexDbConn->query($query)) {
      $output = array();
      while ($library = $libraries->fetchArray(SQLITE3_ASSOC)) {
        $output[] = $library;
      }
      return $output;
    }
    return false;
  }
}
?>
