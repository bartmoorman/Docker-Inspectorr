<?php
class Inspectorr {
  private $dbFile = '/config/inspectorr.db';
  private $dbConn;
  private $plexDbConn;
  public $pageLimit = 20;
  public $tabs = [
    'index-status' => ['text' => 'Index Status', 'icon' => 'check'],
    'audio-quality' => ['text' => 'Audio Quality', 'icon' => 'headphones'],
    'video-quality' => ['text' => 'Video Quality', 'icon' => 'video']
  ];
  public $statuses = [
    'index-status' => [
      'complete' => ['text' => 'Complete', 'class' => 'success', 'hint' => 'Indexing is complete', 'filters' => ["`media_parts`.`extra_data` LIKE '%indexes%'", "`media_parts`.`extra_data` NOT LIKE '%failureBIF%'", "`media_parts`.`extra_data` NOT LIKE ''"]],
      'pending' => ['text' => 'Pending', 'class' => 'info', 'hint' => 'Indexing has not started', 'filters' => ["`media_parts`.`extra_data` NOT LIKE '%indexes%'", "`media_parts`.`extra_data` NOT LIKE '%failureBIF%'", "`media_parts`.`extra_data` NOT LIKE ''"]],
      'failed' => ['text' => 'Failed', 'class' => 'warning', 'hint' => 'Indexing failed - possible corrupt media', 'filters' => ["`media_parts`.`extra_data` NOT LIKE '%indexes%'", "`media_parts`.`extra_data` LIKE '%failureBIF%'", "`media_parts`.`extra_data` NOT LIKE ''"]],
      'corrupt' => ['text' => 'Corrupt', 'class' => 'danger', 'hint' => 'Indexing not possible - corrupt media (or metadata still being updated)', 'filters' => ["`media_parts`.`extra_data` NOT LIKE '%indexes%'", "`media_parts`.`extra_data` NOT LIKE '%failureBIF%'", "`media_parts`.`extra_data` LIKE ''"]]
    ],
    'audio-quality' => [
      'uhd' => ['text' => 'UHD', 'class' => 'success', 'hint' => '7.1 or higher', 'filters' => ["`media_items`.`audio_channels` >= 8"]],
      'hd' => ['text' => 'HD', 'class' => 'info', 'hint' => '5.1 or higher, below 7.1', 'filters' => ["`media_items`.`audio_channels` < 8", "`media_items`.`audio_channels` >= 6"]],
      'sd' => ['text' => 'SD', 'class' => 'warning', 'hint' => 'Stereo or higher, below 5.1', 'filters' => ["`media_items`.`audio_channels` < 6", "`media_items`.`audio_channels` >= 2"]],
      'other' => ['text' => 'Other', 'class' => 'danger', 'hint' => 'below Stereo', 'filters' => ["`media_items`.`audio_channels` < 2"]]
    ],
    'video-quality' => [
      'uhd' => ['text' => 'UHD', 'class' => 'success', 'hint' => '4k or higher', 'filters' => ["`media_items`.`width` >= 2160"]],
      'hd' => ['text' => 'HD', 'class' => 'info', 'hint' => '1080p or higher, below 4k', 'filters' => ["`media_items`.`width` < 2160", "`media_items`.`width` >= 1920"]],
      'sd' => ['text' => 'SD', 'class' => 'warning', 'hint' => '720p or higher, below 1080p', 'filters' => ["`media_items`.`width` < 1920", "`media_items`.`width` >= 1280"]],
      'other' => ['text' => 'Other', 'class' => 'danger', 'hint' => 'below 720p', 'filters' => ["`media_items`.`width` < 1280"]]
    ]
  ];

  public function __construct($requireConfigured = true, $requireValidSession = true, $requireAdmin = true, $requireIndex = false) {
    session_start([
      'save_path' => '/config/sessions',
      'name' => '_sess_inspectorr',
      'gc_maxlifetime' => 60 * 60 * 24 * 7,
      'cookie_lifetime' => 60 * 60 * 24 * 7,
      'cookie_secure' => true,
      'cookie_httponly' => true,
      'use_strict_mode' => true
    ]);

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
CREATE TABLE IF NOT EXISTS `config` (
  `config_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `key` TEXT NOT NULL UNIQUE,
  `value` TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `password` TEXT NOT NULL,
  `first_name` TEXT NOT NULL,
  `last_name` TEXT,
  `role` TEXT NOT NULL,
  `begin` INTEGER,
  `end` INTEGER,
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
CREATE TABLE IF NOT EXISTS `apps` (
  `app_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `token` TEXT NOT NULL UNIQUE,
  `begin` INTEGER,
  `end` INTEGER,
  `disabled` INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS `calls` (
  `call_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `date` INTEGER DEFAULT (STRFTIME('%s', 'now')),
  `app_id` INTEGER NOT NULL,
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
    if ($this->getObjectCount('users')) {
      return true;
    }
    return false;
  }

  public function isValidSession() {
    if (array_key_exists('authenticated', $_SESSION) && $this->isValidObject('user_id', $_SESSION['user_id'])) {
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

  public function isValidObject($type, $value) {
    $type = $this->dbConn->escapeString($type);
    $value = $this->dbConn->escapeString($value);
    switch ($type) {
      case 'username':
      case 'user_id':
        $table = 'users';
        break;
      case 'token':
      case 'app_id':
        $table = 'apps';
        break;
    }
    $query = <<<EOQ
SELECT COUNT(*)
FROM `{$table}`
WHERE `{$type}` = '{$value}'
AND (`begin` IS NULL OR `begin` < STRFTIME('%s', 'now', 'localtime'))
AND (`end` IS NULL OR `end` > STRFTIME('%s', 'now', 'localtime'))
AND NOT `disabled`;
EOQ;
    if ($this->dbConn->querySingle($query)) {
      return true;
    }
    return false;
  }

  public function resolveObject($type, $value) {
    $type = $this->dbConn->escapeString($type);
    $value = $this->dbConn->escapeString($value);
    switch ($type) {
      case 'token':
        $column = 'app_id';
        $table = 'apps';
        break;
    }
    $query = <<<EOQ
SELECT `{$column}`
FROM `{$table}`
WHERE `{$type}` = '{$value}';
EOQ;
    if ($object_id = $this->dbConn->querySingle($query)) {
      return $object_id;
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

  public function createUser($username, $password, $first_name, $last_name = null, $role, $begin = null, $end = null) {
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
      $begin = $this->dbConn->escapeString($begin);
      $end = $this->dbConn->escapeString($end);
      $query = <<<EOQ
INSERT
INTO `users` (`username`, `password`, `first_name`, `last_name`, `role`, `begin`, `end`)
VALUES ('{$username}', '{$password}', '{$first_name}', '{$last_name}', '{$role}', STRFTIME('%s', '{$begin}'), STRFTIME('%s', '{$end}'));
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function createApp($name, $token = null, $begin = null, $end = null) {
    $token = !$token ? bin2hex(random_bytes(8)) : $this->dbConn->escapeString($token);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `apps`
WHERE `token` = '{$token}';
EOQ;
    if (!$this->dbConn->querySingle($query)) {
      $name = $this->dbConn->escapeString($name);
      $begin = $this->dbConn->escapeString($begin);
      $end = $this->dbConn->escapeString($end);
      $query = <<<EOQ
INSERT
INTO `apps` (`name`, `token`, `begin`, `end`)
VALUES ('{$name}', '{$token}', STRFTIME('%s','{$begin}',) STRFTIME('%s','{$end}'));
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function updateUser($user_id, $username, $password = null, $first_name, $last_name = null, $role, $begin = null, $end = null) {
    $user_id = $this->dbConn->escapeString($user_id);
    $username = $this->dbConn->escapeString($username);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `users`
WHERE `user_id` != '{$user_id}'
AND `username` = '{$username}';
EOQ;
    if (!$this->dbConn->querySingle($query)) {
      $passwordQuery = null;
      if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $passwordQuery = <<<EOQ
  `password` = '{$password}',
EOQ;
      }
      $first_name = $this->dbConn->escapeString($first_name);
      $last_name = $this->dbConn->escapeString($last_name);
      $role = $this->dbConn->escapeString($role);
      $begin = $this->dbConn->escapeString($begin);
      $end = $this->dbConn->escapeString($end);
      $query = <<<EOQ
UPDATE `users`
SET
  `username` = '{$username}',
{$passwordQuery}
  `first_name` = '{$first_name}',
  `last_name` = '{$last_name}',
  `role` = '{$role}',
  `begin` = STRFTIME('%s', '{$begin}'),
  `end` = STRFTIME('%s', '{$end}')
WHERE `user_id` = '{$user_id}';
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function updateApp($app_id, $name, $token, $begin, $end) {
    $app_id = $this->dbConn->escapeString($app_id);
    $token = $this->dbConn->escapeString($token);
    $query = <<<EOQ
SELECT COUNT(*)
FROM `apps`
WHERE `app_id` != '{$app_id}'
AND `token` = '{$token}';
EOQ;
    if (!$this->dbConn->querySingle($query)) {
      $name = $this->dbConn->escapeString($name);
      $begin = $this->dbConn->escapeString($begin);
      $end = $this->dbConn->escapeString($end);
      $query = <<<EOQ
UPDATE `apps`
SET
  `name` = '{$name}',
  `token` = '{$token}',
  `begin` = STRFTIME('%s', '{$begin}'),
  `end` = STRFTIME('%s', '{$end}')
WHERE `app_id` = '{$app_id}';
EOQ;
      if ($this->dbConn->exec($query)) {
        return true;
      }
    }
    return false;
  }

  public function modifyObject($action, $type, $value, $extra_type = null, $extra_value = null) {
    $type = $this->dbConn->escapeString($type);
    $value = $this->dbConn->escapeString($value);
    $extra_type = $this->dbConn->escapeString($extra_type);
    $extra_value = $this->dbConn->escapeString($extra_value);
    switch ($type) {
      case 'username':
      case 'user_id':
        $table = 'users';
        $extra_table = 'events';
        break;
      case 'token':
      case 'app_id':
        $table = 'apps';
        $extra_table = 'calls';
        break;
    }
    switch ($action) {
      case 'enable':
        $query = <<<EOQ
UPDATE `{$table}`
SET `disabled` = '0'
WHERE `{$type}` = '{$value}';
EOQ;
        break;
      case 'disable':
        $query = <<<EOQ
UPDATE `users`
SET `disabled` = '1'
WHERE `{$type}` = '{$value}';
EOQ;
        break;
      case 'delete':
        $query = <<<EOQ
DELETE
FROM `{$table}`
WHERE `{$type}` = '{$value}';
DELETE
FROM `{$extra_table}`
WHERE `{$type}` = '{$value}';
EOQ;
        break;
    }
    if ($this->dbConn->exec($query)) {
      return true;
    }
    return false;
  }

  public function getObjects($type) {
    switch ($type) {
      case 'users':
        $query = <<<EOQ
SELECT `user_id`, `username`, `first_name`, `last_name`, `role`, `begin`, `end`, `disabled`
FROM `users`
ORDER BY `last_name`, `first_name`;
EOQ;
        break;
      case 'apps':
        $query = <<<EOQ
SELECT `app_id`, `name`, `token`, `begin`, `end`, `disabled`
FROM `apps`
ORDER BY `name`;
EOQ;
        break;
    }
    if ($objects = $this->dbConn->query($query)) {
      $output = [];
      while ($object = $objects->fetchArray(SQLITE3_ASSOC)) {
        $output[] = $object;
      }
      return $output;
    }
    return false;
  }

  public function getObjectDetails($type, $value) {
    $value = $this->dbConn->escapeString($value);
    switch ($type) {
      case 'user':
        $query = <<<EOQ
SELECT `user_id`, `username`, `first_name`, `last_name`, `role`, STRFTIME('%Y-%m-%dT%H:%M', `begin`, 'unixepoch') AS `begin`, STRFTIME('%Y-%m-%dT%H:%M', `end`, 'unixepoch') AS `end`, `disabled`
FROM `users`
WHERE `user_id` = '{$value}';
EOQ;
        break;
      case 'app':
        $query = <<<EOQ
SELECT `app_id`, `name`, `token`, STRFTIME('%Y-%m-%dT%H:%M', `begin`, 'unixepoch') AS `begin`, STRFTIME('%Y-%m-%dT%H:%M', `end`, 'unixepoch') AS `end`, `disabled`
FROM `apps`
WHERE `app_id` = '{$value}';
EOQ;
        break;
    }
    if ($object = $this->dbConn->querySingle($query, true)) {
      return $object;
    }
    return false;
  }

  public function getObjectCount($type) {
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

  public function putEvent($user_id, $action, $message = []) {
    $user_id = $this->dbConn->escapeString($user_id);
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

  public function putCall($token, $action, $message = []) {
    $app_id = $this->resolveObject('token', $token);
    $action = $this->dbConn->escapeString($action);
    $message = $this->dbConn->escapeString(json_encode($message));
    $remote_addr = ip2long(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    $query = <<<EOQ
INSERT
INTO `calls` (`app_id`, `action`, `message`, `remote_addr`)
VALUES ('{$app_id}', '{$action}', '{$message}', '{$remote_addr}');
EOQ;
    if ($this->dbConn->exec($query)) {
      return true;
    }
    return false;
  }

  public function getEvents($page = 1) {
    $start = ($page - 1) * $this->pageLimit;
    $query = <<<EOQ
SELECT `event_id`, STRFTIME('%s', `date`, 'unixepoch') AS `date`, `user_id`, `first_name`, `last_name`, `action`, `message`, `remote_addr`, `disabled`
FROM `events`
LEFT JOIN `users` USING (`user_id`)
ORDER BY `date` DESC
LIMIT {$start}, {$this->pageLimit};
EOQ;
    if ($events = $this->dbConn->query($query)) {
      $output = [];
      while ($event = $events->fetchArray(SQLITE3_ASSOC)) {
        $output[] = $event;
      }
      return $output;
    }
    return false;
  }

  private function buildSelectCases($tab) {
    $cases = null;
    foreach ($this->statuses[$tab] as $status => $options) {
      $caseFilter = implode(' AND ', $options['filters']);
      $cases .= <<<EOQ
WHEN {$caseFilter} THEN '{$status}'

EOQ;
    }
    return $cases;
  }

  private function buildOrderCases($tab) {
    $cases = null;
    foreach (array_keys($this->statuses[$tab]) as $order => $status) {
      $cases .= <<<EOQ
WHEN '{$status}' THEN '{$order}'

EOQ;
    }
    return $cases;
  }

  private function buildFilters($tab, $status) {
    $filters = null;
    foreach ($this->statuses[$tab][$status]['filters'] as $filter) {
      $filters .= <<<EOQ
AND {$filter}

EOQ;
    }
    return $filters;
  }

  public function getLibraryIcon($section_type, $language) {
    switch ($section_type) {
      case 1:
        switch ($language) {
          case 'xn':
            return 'video';
            break;
          default:
            return 'film';
        }
        break;
      case 2:
        return 'tv';
        break;
    }
    return false;
  }

  public function getLibraries() {
    $query = <<<EOQ
SELECT `library_sections`.`id`, `library_sections`.`name`, `library_sections`.`section_type`, `library_sections`.`language`, COUNT(*) AS `count`
FROM `library_sections`
JOIN `metadata_items` ON `metadata_items`.`library_section_id` = `library_sections`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `library_sections`.`section_type` IN (1, 2)
GROUP BY `library_sections`.`id`
ORDER BY `library_sections`.`name`;
EOQ;
    if ($libraries = $this->plexDbConn->query($query)) {
      $output = [];
      while ($library = $libraries->fetchArray(SQLITE3_ASSOC)) {
        $output[$library['id']] = $library;
      }
      return $output;
    }
    return false;
  }

  public function getLibraryStatusCounts($tab, $library) {
    $query = <<<EOQ
SELECT CASE
{$this->buildSelectCases($tab)}
END AS `status`, COUNT(*) AS `count`
FROM `metadata_items`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `metadata_items`.`library_section_id` = '{$library}'
GROUP BY `status`
ORDER BY CASE `status`
{$this->buildOrderCases($tab)}
END;
EOQ;
    if ($counts = $this->plexDbConn->query($query)) {
      $output = [];
      while ($count = $counts->fetchArray(SQLITE3_ASSOC)) {
        $output[$count['status']] = $count;
      }
      return $output;
    }
    return false;
  }

  public function getLibraryStatusSectionCounts($tab, $library) {
$query = <<<EOQ
SELECT CASE
{$this->buildSelectCases($tab)}
END AS `status`, `section_locations`.`id`, `section_locations`.`root_path`, COUNT(*) AS `count`
FROM `metadata_items`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
JOIN `section_locations` ON `section_locations`.`id` = `media_items`.`section_location_id`
WHERE `metadata_items`.`library_section_id` = '{$library}'
GROUP BY `status`, `section_locations`.`id`
ORDER BY CASE `status`
{$this->buildOrderCases($tab)}
END, `section_locations`.`id`;
EOQ;
    if ($sections = $this->plexDbConn->query($query)) {
      $output = [];
      while ($section = $sections->fetchArray(SQLITE3_ASSOC)) {
        $output[$section['status']][$section['id']] = $section;
      }
      return $output;
    }
    return false;
  }

  public function getLibraryStatusSectionDetails($tab, $library, $status, $section) {
    $query = <<<EOQ
SELECT `library_sections`.`section_type`
FROM `library_sections`
WHERE `library_sections`.`id` = '{$library}';
EOQ;
    if ($librarySectionType = $this->plexDbConn->querySingle($query)) {
      switch ($librarySectionType) {
        case 1:
          $query = <<<EOQ
SELECT `movie`.`title`, `movie`.`year`
FROM `metadata_items` AS `movie`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `movie`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `movie`.`library_section_id` = {$library}
AND `media_items`.`section_location_id` = {$section}
{$this->buildFilters($tab, $status)}
ORDER BY `movie`.`title_sort`;
EOQ;
          $format = '%s (%u)';
          break;
        case 2:
          $query = <<<EOQ
SELECT `show`.`title` AS `show_title`, `season`.`index` AS `season`, `episode`.`index` AS `episode`, `episode`.`title` AS `episode_title`
FROM `metadata_items` AS `show`
JOIN `metadata_items` AS `season` ON `season`.`parent_id` = `show`.`id`
JOIN `metadata_items` AS `episode` ON `episode`.`parent_id` = `season`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `episode`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `show`.`library_section_id` = {$library}
AND `media_items`.`section_location_id` = {$section}
{$this->buildFilters($tab, $status)}
ORDER BY `show`.`title_sort`, `season`.`index`, `episode`.`index`;
EOQ;
          $format = '%s - s%02ue%02u - %s';
          break;
      }
      if ($details = $this->plexDbConn->query($query)) {
        $output = [];
        while ($detail = $details->fetchArray(SQLITE3_ASSOC)) {
          $output[] = vsprintf($format, $detail);
        }
        return $output;
      }
    }
    return false;
  }
}
?>
