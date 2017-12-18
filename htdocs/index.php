<?php
class IndexStatus {
  private $defaultLimit = 500;
  private $defaultPercentPrecision = 2;
  private $defaultShowQueryStats = false;

  private $limit;
  private $percentPrecision;
  private $showQueryStats;

  private $statuses = array(
    'complete' => array('text' => 'Complete', 'class' => 'success', 'hint' => 'Indexing is complete'),
    'pending' => array('text' => 'Pending', 'class' => 'info', 'hint' => 'Indexing has not started'),
    'failed' => array('text' => 'Failed', 'class' => 'warning', 'hint' => 'Indexing failed (partially corrupt media)'),
    'unknown' => array('text' => 'Unknown', 'class' => 'danger', 'hint' => 'Indexing not possible (fully corrupt media)')
  );

  private $queryStringArray;
  private $queryCount;
  private $queryTime;

  public function __construct($dbFile) {
    $dbDir = dirname($dbFile);

    $dbFileReadable = file_exists($dbFile) ? is_readable($dbFile) : false;
    $dbDirWritable = file_exists($dbDir) ? is_writable($dbDir) : false;
    $dbFileShmWritable = file_exists("{$dbFile}-shm") ? is_writable("{$dbFile}-shm") : $dbDirWritable;
    $dbFileWalWritable = file_exists("{$dbFile}-wal") ? is_writable("{$dbFile}-wal") : $dbDirWritable;

    if ($dbFileReadable && $dbDirWritable && $dbFileShmWritable && $dbFileWalWritable) {
      if (!file_exists("{$dbFile}-shm") || !file_exists("{$dbFile}-wal")) {
        echo "      <div class='alert alert-dismissable alert-info mt-3'>" . PHP_EOL;
        echo "        <span><a class='close' style='cursor:default' onclick='void(0)' data-dismiss='alert'>&times;</a></span>" . PHP_EOL;

        if (!file_exists("{$dbFile}-shm")) {
          echo "        <p class='mb-0'><strong>{$dbFile}-shm</strong> doesn't exist and will be created.</p>" . PHP_EOL;
        }

        if (!file_exists("{$dbFile}-wal")) {
          echo "        <p class='mb-0'><strong>{$dbFile}-wal</strong> doesn't exist and will be created.</p>" . PHP_EOL;
        }

        echo "      </div>" . PHP_EOL;
      }

      $this->db = new SQLite3($dbFile, SQLITE3_OPEN_READONLY);

      $this->limit = array_key_exists('limit', $_REQUEST) ? $_REQUEST['limit'] : $this->defaultLimit;
      $this->percentPrecision = array_key_exists('percentPrecision', $_REQUEST) ? $_REQUEST['percentPrecision'] : $this->defaultPercentPrecision;
      $this->showQueryStats = array_key_exists('showQueryStats', $_REQUEST) ? $_REQUEST['showQueryStats'] : $this->defaultShowQueryStats;

      parse_str($_SERVER['QUERY_STRING'], $this->queryStringArray);

      $this->showOverview();
    } else {
      echo "      <div class='alert alert-danger mt-3'>" . PHP_EOL;
      echo "        <span><h4 class='alert-heading'>Something went wrong.</h4></span>" . PHP_EOL;

      if (!$dbFileReadable) {
        echo "        <p class='mb-0'>We can't read <strong>{$dbFile}</strong>!</p>" . PHP_EOL;
      } elseif (!$dbDirWritable) {
        echo "        <p class='mb-0'>We can't write to <strong>{$dbDir}</strong>!</p>" . PHP_EOL;
      } elseif (!$dbFileShmWritable) {
        echo "        <p class='mb-0'>We can't write to <strong>{$dbFile}-shm</strong>!</p>" . PHP_EOL;
      } elseif (!$dbFileWalWritable) {
        echo "        <p class='mb-0'>We can't write to <strong>{$dbFile}-wal</strong>!</p>" . PHP_EOL;
      } else {
        echo "        <p class='mb-0'>But we don't know what... Maybe check the logs?</p>" . PHP_EOL;
      }

      echo "      </div>" . PHP_EOL;
    }
  }

  private function statusFilters($status) {
    switch($status) {
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
      case 'unknown':
        $filters = <<<EOQ
AND `media_parts`.`extra_data` NOT LIKE '%indexes%'
AND `media_parts`.`extra_data` NOT LIKE '%failureBIF%'
AND `media_parts`.`extra_data` LIKE ''
EOQ;
        break;
      default:
        return false;
    }

    return $filters;
  }

  private function runQuery($query) {
    $start = microtime(true);

    $result = $this->db->query($query);

    $finish = microtime(true);

    $this->queryTime += $finish - $start;
    $this->queryCount++;

    return $result;
  }

  private function fetchLibrarySummaries() {
    $excludeIDs = implode(',', explode(',', getenv('EXCLUDE_LIBRARY_IDS')));
    $excludeNames = implode("','", explode(',', getenv('EXCLUDE_LIBRARY_NAMES')));

    $query = <<<EOQ
SELECT `library_sections`.`id`, `library_sections`.`name`, `library_sections`.`section_type`, COUNT(*) AS `count`
FROM `library_sections`
JOIN `metadata_items` ON `metadata_items`.`library_section_id` = `library_sections`.`id`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `library_sections`.`id` NOT IN ({$excludeIDs})
AND `library_sections`.`name` NOT IN ('{$excludeNames}')
AND `library_sections`.`section_type` IN (1,2)
GROUP BY `library_sections`.`id`
ORDER BY `library_sections`.`name`
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibraryStatusCount($library, $status) {
    $query = <<<EOQ
SELECT COUNT(*) AS `count`
FROM `metadata_items`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `metadata_items`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `metadata_items`.`library_section_id` = {$library}
{$this->statusFilters($status)}
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibrarySectionLocations($library, $status) {
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

  private function fetchLibraryStatusDetails($type, $library, $section, $status) {
    switch ($type) {
      case 1:
        $query = <<<EOQ
SELECT `movie`.`title`, `movie`.`year`
FROM `metadata_items` AS `movie`
JOIN `media_items` ON `media_items`.`metadata_item_id` = `movie`.`id`
JOIN `media_parts` ON `media_parts`.`media_item_id` = `media_items`.`id`
WHERE `movie`.`library_section_id` = {$library}
AND `media_items`.`section_location_id` = {$section}
{$this->statusFilters($status)}
ORDER BY `movie`.`title_sort`;
EOQ;
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
{$this->statusFilters($status)}
ORDER BY `show`.`title_sort`, `season`.`index`, `episode`.`index`;
EOQ;
        break;
    }

    return $this->runQuery($query);
  }

  private function getLibraryStatusCounts($library, $count) {
    foreach (array_keys($this->statuses) as $status) {
      $statusCount = $this->fetchLibraryStatusCount($library, $status)->fetchArray(SQLITE3_ASSOC);

      if ($statusCount['count'] > 0) {
        $statusCounts[$status]['count'] = $statusCount['count'];
        $statusCounts[$status]['countFmt'] = number_format($statusCount['count']);
        $statusCounts[$status]['percent'] = round($statusCount['count'] / $count * 100);
        $statusCounts[$status]['percentFmt'] = round($statusCount['count'] / $count * 100, $this->percentPrecision);
      }
    }

    return $statusCounts;
  }

  private function getLibrarySectionLocations($library, $status, $count) {
    $librarySectionLocations = $this->fetchLibrarySectionLocations($library, $status);

    while ($librarySectionLocation = $librarySectionLocations->fetchArray(SQLITE3_ASSOC)) {
      $libraryLocations[$librarySectionLocation['id']]['root_path'] = $librarySectionLocation['root_path'];
      $libraryLocations[$librarySectionLocation['id']]['count'] = $librarySectionLocation['count'];
      $libraryLocations[$librarySectionLocation['id']]['countFmt'] = number_format($librarySectionLocation['count']);
      $libraryLocations[$librarySectionLocation['id']]['percent'] = round($librarySectionLocation['count'] / $count * 100);
      $libraryLocations[$librarySectionLocation['id']]['percentFmt'] = round($librarySectionLocation['count'] / $count * 100, $this->percentPrecision);
    }

    return $libraryLocations;
  }

  private function buildQueryString($queryData) {
    if (count($queryData)) {
      $queryString = '?' . http_build_query($queryData);
    } else {
      $queryString = '.';
    }

    return $queryString;
  }

  private function showOverview() {
    $removeLimitQueryString = $this->buildQueryString(array_merge($this->queryStringArray, array('limit' => 0)));
    $resetLimitQueryString = $this->buildQueryString(array_diff_key($this->queryStringArray, array('limit' => $this->defaultLimit)));

    echo "      <div class='card border-secondary mt-3'>" . PHP_EOL;
    echo "        <div class='card-header'>" . PHP_EOL;
    echo "          <span><h3 class='mb-0'>Plex Index Status</h3></span>" . PHP_EOL;
    echo "        </div>" . PHP_EOL;
    echo "        <div class='card-body'>" . PHP_EOL;

    $librarySummaries = $this->fetchLibrarySummaries();

    while ($librarySummary = $librarySummaries->fetchArray(SQLITE3_ASSOC)) {
      $statusCounts = $this->getLibraryStatusCounts($librarySummary['id'], $librarySummary['count']);

      echo "          <span>" . PHP_EOL;
      echo "            <h4>" . PHP_EOL;
      echo "              {$librarySummary['id']} : {$librarySummary['name']}" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        echo "              <span class='badge badge-pill badge-{$this->statuses[$status]['class']}' style='cursor:default' data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}' onclick='void(0)'>{$stats['countFmt']}</span>" . PHP_EOL;
      }

      echo "            </h4>" . PHP_EOL;
      echo "          </span>" . PHP_EOL;
      echo "          <div class='progress mb-3'>" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        echo "            <div class='progress-bar progress-bar-striped bg-{$this->statuses[$status]['class']}' style='width:{$stats['percent']}%'></div>" . PHP_EOL;
      }

      echo "          </div>" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        echo "          <div id='{$librarySummary['id']}-{$status}' class='collapse'>" . PHP_EOL;
        echo "            <div class='card border-{$this->statuses[$status]['class']} mb-3'>" . PHP_EOL;
        echo "              <div class='card-header'>" . PHP_EOL;
        echo "                <span><a data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}' class='close' style='cursor:default' onclick='void(0)'>&times;</a></span>" . PHP_EOL;
        echo "                <span>" . PHP_EOL;
        echo "                  <h5 class='text-{$this->statuses[$status]['class']} mb-0'>" . PHP_EOL;
        echo "                    {$this->statuses[$status]['text']}" . PHP_EOL;
        echo "                    <span class='badge badge-pill badge-dark' style='cursor:default'>{$stats['percentFmt']}%</span>" . PHP_EOL;
        echo "                  </h5>" . PHP_EOL;
        echo "                </span>" . PHP_EOL;
        echo "              </div>" . PHP_EOL;

        $sectionLocations = $this->getLibrarySectionLocations($librarySummary['id'], $status, $stats['count']);
        $sectionCount = count($sectionLocations);

        foreach ($sectionLocations as $sectionID => $sectionDetails) {
          if ($sectionCount > 1) {
            echo "              <div class='card-header'>" . PHP_EOL;
            echo "                <span><a data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}-{$sectionID}' class='close' style='cursor:default' onclick='void(0)'>&plus;</a></span>" . PHP_EOL;
            echo "                <span>" . PHP_EOL;
            echo "                  {$sectionDetails['root_path']}" . PHP_EOL;
            echo "                  <span class='badge badge-pill badge-dark' style='cursor:default' data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}-{$sectionID}' onclick='void(0)'>{$sectionDetails['countFmt']}</span>" . PHP_EOL;
            echo "                </span>" . PHP_EOL;
            echo "              </div>" . PHP_EOL;
            echo "              <div id='{$librarySummary['id']}-{$status}-{$sectionID}' class='collapse'>" . PHP_EOL;
          } else {
            echo "              <div id='{$librarySummary['id']}-{$status}-{$sectionID}'>" . PHP_EOL;
          }

          echo "                <div class='card-body'>" . PHP_EOL;

          if ($this->limit == 0 || $sectionDetails['count'] <= $this->limit) {
            $statusDetails = $this->fetchLibraryStatusDetails($librarySummary['section_type'], $librarySummary['id'], $sectionID, $status);

            while ($statusDetail = $statusDetails->fetchArray(SQLITE3_ASSOC)) {
              if ($librarySummary['section_type'] == 1) {
                  echo "                  <p class='card-text text-muted mb-0'>{$statusDetail['title']} ({$statusDetail['year']})</p>" . PHP_EOL;
              } elseif ($librarySummary['section_type'] == 2) {
                  $season = str_pad($statusDetail['season'], 2, 0, STR_PAD_LEFT);
                  $episode = str_pad($statusDetail['episode'], 2, 0, STR_PAD_LEFT);

                  echo "                  <p class='card-text text-muted mb-0'>{$statusDetail['show_title']} - s{$season}e{$episode} - {$statusDetail['episode_title']}</p>" . PHP_EOL;
              }
            }
          } else {
              echo "                  <p class='card-text'>This list exceeds the current limit of <strong>{$this->limit}</strong>!</p>" . PHP_EOL;
              echo "                  <p class='card-text'><a href='{$removeLimitQueryString}' class='text-danger'>Remove limit</a></p>" . PHP_EOL;
          }

          echo "                </div>" . PHP_EOL;
          echo "              </div>" . PHP_EOL;
        }

        echo "            </div>" . PHP_EOL;
        echo "          </div>" . PHP_EOL;
      }
    }

    if ($this->showQueryStats) {
      $queryCountFmt = number_format($this->queryCount);
      $queryTimeFmt = round($this->queryTime * 1000);

      echo "          <span><small>{$queryCountFmt} queries took {$queryTimeFmt}ms</small></span>" . PHP_EOL;
    }

    echo "        </div>" . PHP_EOL;
    echo "        <div class='card-footer'>" . PHP_EOL;

    foreach ($this->statuses as $status) {
      echo "          <span class='badge badge-{$status['class']}' style='cursor:help' title='{$status['hint']}'>{$status['text']}</span>" . PHP_EOL;
    }

    if (array_key_exists('limit', $_REQUEST)) {
      echo "          <span class='float-right'><a href='{$resetLimitQueryString}' class='text-success'>Reset limit</a></span>" . PHP_EOL;
    }

    echo "        </div>" . PHP_EOL;

    echo "      </div>" . PHP_EOL;
  }
}
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Plex Index Status</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css' integrity='sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb' crossorigin='anonymous'>
<?php
$theme = array_key_exists('theme', $_REQUEST) ? $_REQUEST['theme'] : 'darkly';

echo "    <link rel='stylesheet' href='//bootswatch.com/4/{$theme}/bootstrap.min.css'>" . PHP_EOL;
?>
  </head>
  <body>
    <div class='container'>
      <nav class='navbar navnar-dark bg-primary'>
        <form>
          <select class='btn btn-dark btn-sm' name='theme' onchange='this.form.submit()'>
<?php
$themes = array(
  'cerulean' => 'Cerulean',
  'cosmo' => 'Cosmo',
  'cyborg' => 'Cyborg',
  'darkly' => 'Darkly (default)',
  'flatly' => 'Flatly',
  'journal' => 'Journal',
  'litera' => 'Litera',
  'lumen' => 'Lumen',
  'lux' => 'Lux',
  'materia' => 'Materia',
  'minty' => 'Minty',
  'pulse' => 'Pulse',
  'sandstone' => 'Sandstone',
  'simplex' => 'Simplex',
  'sketchy' => 'Sketchy',
  'slate' => 'Slate',
  'solar' => 'Solar',
  'spacelab' => 'Spacelab',
  'superhero' => 'Superhero',
  'united' => 'United',
  'yeti' => 'Yeti'
);

foreach ($themes as $theme => $name) {
  if ((array_key_exists('theme', $_REQUEST) && $theme == $_REQUEST['theme']) || (!array_key_exists('theme', $_REQUEST) && $theme == 'darkly')) {
    echo "            <option value='{$theme}' selected='selected'>{$name}</option>" . PHP_EOL;
  } else {
    echo "            <option value='{$theme}'>{$name}</option>" . PHP_EOL;
  }
}
?>
          </select>
        </form>
      </nav>
    </div>
    <div class='container'>
<?php
new IndexStatus('/data/com.plexapp.plugins.library.db');
?>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js' integrity='sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js' integrity='sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ' crossorigin='anonymous'></script>
    <script>
    $(document).ready(function() {
      $(".alert-dismissable").delay(7500).fadeTo('slow', 0).slideUp('slow');
    });
    </script>
  </body>
</html>
