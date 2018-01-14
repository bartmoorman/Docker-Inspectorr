<?php
class Complet {
  public $messages = array();
  public $statuses = array(
    'complete' => array('text' => 'Complete', 'class' => 'success', 'hint' => 'Indexing is complete'),
    'pending' => array('text' => 'Pending', 'class' => 'info', 'hint' => 'Indexing has not started'),
    'failed' => array('text' => 'Failed', 'class' => 'warning', 'hint' => 'Indexing failed - possible corrupt media'),
    'corrupt' => array('text' => 'Corrupt', 'class' => 'danger', 'hint' => 'Indexing not possible - corrupt media (or metadata still being read)')
  );

  private $dbConnected = false;
  private $queryCount = 0;
  private $queryTime = 0;

  public function __construct() {
    $pmsDatabaseFile = getenv('PMS_DATABASE_FILE');

    if (file_exists("/data/{$pmsDatabaseFile}")) {
      if (!file_exists("/data/{$pmsDatabaseFile}-wal")) {
        $this->messages['warning'][] = "/data/{$pmsDatabaseFile}-wal doesn't exist. This will likely cause delayed updates.";
      }

      $dbFile = "/data/{$pmsDatabaseFile}";
    } elseif (file_exists("/tmp/{$pmsDatabaseFile}")) {
      if (!file_exists("/tmp/{$pmsDatabaseFile}-wal")) {
        $this->messages['warning'][] = "/tmp/{$pmsDatabaseFile}-wal doesn't exist. This will likely cause delayed updates.";
      }

      $dbFile = "/tmp/{$pmsDatabaseFile}";
    } else {
      $this->messages['danger'][] = "Unable to locate {$pmsDatabaseFile} in /data or /tmp. This is fatal.";
      return;
    }

    $this->db = new SQLite3($dbFile, SQLITE3_OPEN_READONLY);
    $this->db->busyTimeout(500);
    $this->dbConnected = true;
  }

  private function statusFilters($status) {
    switch ($status) {
      case 'complete':
        $filters = <<<EOF
AND `media_parts`.`extra_data` LIKE '%indexes%'
AND `media_parts`.`extra_data` NOT LIKE '%failureBIF%'
AND `media_parts`.`extra_data` NOT LIKE ''
EOF;
        break;
      case 'pending':
        $filters = <<<EOQ
AND `media_parts`.`extra_data` NOT LIKE '%indexes%'
AND `media_parts`.`extra_data` NOT LIKE '%failureBIF%'
AND `media_parts`.`extra_data` NOT LIKE ''
EOQ;
        break;
      case 'failed':
        $filters = <<<EOQ
AND `media_parts`.`extra_data` NOT LIKE '%indexes%'
AND `media_parts`.`extra_data` LIKE '%failureBIF%'
AND `media_parts`.`extra_data` NOT LIKE ''
EOQ;
        break;
      case 'corrupt':
        $filters = <<<EOQ
AND `media_parts`.`extra_data` NOT LIKE '%indexes%'
AND `media_parts`.`extra_data` NOT LIKE '%failureBIF%'
AND `media_parts`.`extra_data` LIKE ''
EOQ;
        break;
      default:
        return;
    }

    return $filters;
  }

  private function runQuery($query) {
    if (!$this->dbConnected) return;

    $start = microtime(true);

    $result = $this->db->query($query);

    $finish = microtime(true);

    $this->queryTime += $finish - $start;
    $this->queryCount++;

    return $result;
  }

  private function fetchLibraries() {
    $pmsExcludeLibraryIDs = implode(', ', explode(',', getenv('PMS_EXCLUDE_LIBRARY_IDS')));
    $pmsExcludeLibraryNames = implode("', '", explode(',', getenv('PMS_EXCLUDE_LIBRARY_NAMES')));

    $query = <<<EOQ
SELECT `library_sections`.`id`, `library_sections`.`name`, COUNT(*) AS `count`
FROM `library_sections`
JOIN `metadata_items` ON `metadata_items`.`library_section_id` = `library_sections`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `library_sections`.`id` NOT IN ({$pmsExcludeLibraryIDs})
AND `library_sections`.`name` NOT IN ('{$pmsExcludeLibraryNames}')
AND `library_sections`.`section_type` IN (1,2)
GROUP BY `library_sections`.`id`
ORDER BY `library_sections`.`name`
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibraryDetails($library, $status) {
    $query = <<<EOQ
SELECT '{$status}' AS `status`, COUNT(*) AS `count`
FROM `metadata_items`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `metadata_items`.`library_section_id` = {$library}
{$this->statusFilters($status)}
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibrarySections($library, $status) {
   $query = <<<EOQ
SELECT `section_locations`.`id`, `section_locations`.`root_path`, COUNT(*) AS `count`
FROM `metadata_items`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
JOIN `section_locations` ON `section_locations`.`id` = `media_items`.`section_location_id`
WHERE `metadata_items`.`library_section_id` = {$library}
{$this->statusFilters($status)}
GROUP BY `section_locations`.`id`
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibrarySectionDetails($library, $section, $status) {
    $query = <<<EOQ
SELECT `library_sections`.`section_type`
FROM `library_sections`
WHERE `library_sections`.`id` = {$library}
EOQ;

    $librarySectionTypes = $this->runQuery($query);

    if ($librarySectionTypes) {
      $librarySectionType = $librarySectionTypes->fetchArray(SQLITE3_ASSOC);
    } else {
      return;
    }

    switch ($librarySectionType['section_type']) {
      case 1:
        $query = <<<EOQ
SELECT '{$librarySectionType['section_type']}' AS `type`, `movie`.`title`, `movie`.`year`
FROM `metadata_items` AS `movie`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `movie`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `movie`.`library_section_id` = {$library}
AND `media_items`.`section_location_id` = {$section}
{$this->statusFilters($status)}
ORDER BY `movie`.`title_sort`
EOQ;
        break;
      case 2:
        $query = <<<EOQ
SELECT '{$librarySectionType['section_type']}' AS `type`, `show`.`title` AS `show_title`, `season`.`index` AS `season`, `episode`.`index` AS `episode`, `episode`.`title` AS `episode_title`
FROM `metadata_items` AS `show`
JOIN `metadata_items` AS `season` ON `season`.`parent_id` = `show`.`id`
JOIN `metadata_items` AS `episode` ON `episode`.`parent_id` = `season`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `episode`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `show`.`library_section_id` = {$library}
AND `media_items`.`section_location_id` = {$section}
{$this->statusFilters($status)}
ORDER BY `show`.`title_sort`, `season`.`index`, `episode`.`index`
EOQ;
        break;
      default:
        return;
    }

    return $this->runQuery($query);
  }

  private function getLibraryDetails($library) {
    foreach (array_keys($this->statuses) as $status) {
      $libraryDetails = $this->fetchLibraryDetails($library, $status);

      if ($libraryDetails) {
        $libraryDetail = $libraryDetails->fetchArray(SQLITE3_ASSOC);
      } else {
        return;
      }

      if ($libraryDetail['count']) {
        $data[] = $libraryDetail;
      }
    }

    return $data;
  }

  public function getLibraries() {
    $libraries = $this->fetchLibraries();

    if ($libraries) {
      while ($library = $libraries->fetchArray(SQLITE3_ASSOC)) {
        $libraryDetails = $this->getLibraryDetails($library['id']);

        foreach ($libraryDetails as $libraryDetail) {
          $library['details'][] = $libraryDetail;
        }

        $data[] = $library;
      }
    } else {
      return;
    }

    return $data;
  }

  public function getLibrarySections($library, $section) {
    $librarySections = $this->fetchLibrarySections($library, $section);

    if ($librarySections) {
      while ($librarySection = $librarySections->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $librarySection;
      }
    } else {
      return;
    }

    return $data;
  }

  public function getLibrarySectionDetails($library, $status, $section) {
    $librarySectionDetails = $this->fetchLibrarySectionDetails($library, $section, $status);

    if ($librarySectionDetails) {
      while ($librarySectionDetail = $librarySectionDetails->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $librarySectionDetail;
      }
    } else {
      return;
    }

    return $data;
  }
}
?>
