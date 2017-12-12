<?php
class IndexStatus {
  private $limit;
  private $percentPrecision;
  private $showStats;

  private $statuses = array(
    'complete' => array('class' => 'success', 'text' => 'Indexing is complete'),
    'pending' => array('class' => 'info', 'text' => 'Indexing has not started'),
    'failed' => array('class' => 'warning', 'text' => 'Indexing failed (partially corrupt media)'),
    'unknown' => array('class' => 'danger', 'text' => 'Indexing not possible (fully corrupt media)')
  );

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

      $this->limit = array_key_exists('limit', $_REQUEST) ? $_REQUEST['limit'] : 500;
      $this->percentPrecision = array_key_exists('percentPrecision', $_REQUEST) ? $_REQUEST['percentPrecision'] : 2;
      $this->showStats = array_key_exists('showStats', $_REQUEST) ? $_REQUEST['showStats'] : false;

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
AND mp.extra_data LIKE '%indexes%'
AND mp.extra_data NOT LIKE '%failureBIF%'
AND mp.extra_data NOT LIKE ''
EOF;
        break;
      case 'pending':
        $filters = <<<EOQ
AND mp.extra_data NOT LIKE '%indexes%'
AND mp.extra_data NOT LIKE '%failureBIF%'
AND mp.extra_data NOT LIKE ''
EOQ;
        break;
      case 'failed':
        $filters = <<<EOQ
AND mp.extra_data NOT LIKE '%indexes%'
AND mp.extra_data LIKE '%failureBIF%'
AND mp.extra_data NOT LIKE ''
EOQ;
        break;
      case 'unknown':
        $filters = <<<EOQ
AND mp.extra_data NOT LIKE '%indexes%'
AND mp.extra_data NOT LIKE '%failureBIF%'
AND mp.extra_data LIKE ''
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
    $query = <<<EOQ
SELECT lib.id, lib.name, COUNT(*) AS count
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
GROUP BY lib.id
ORDER BY lib.name
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibraryStatusCount($library, $status) {
    $query = <<<EOQ
SELECT COUNT(*) AS count
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
WHERE lib.id = {$library}
{$this->statusFilters($status)}
EOQ;

    return $this->runQuery($query);
  }

  private function fetchLibraryStatusDetails($library, $status) {
    $query = <<<EOQ
SELECT meta.id, meta.title, mi.hints, mp.file
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
WHERE lib.id = {$library}
{$this->statusFilters($status)}
ORDER BY mp.file
EOQ;

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

  private function showOverview() {
    echo "      <div class='card border-secondary mt-3'>" . PHP_EOL;
    echo "        <div class='card-header'>" . PHP_EOL;
    echo "          <span><h3 class='mb-0'>Plex Index Status</h3></span>" . PHP_EOL;
    echo "        </div>" . PHP_EOL;
    echo "        <div class='card-body'>" . PHP_EOL;

    $librarySummaries = $this->fetchLibrarySummaries();

    while ($librarySummary = $librarySummaries->fetchArray(SQLITE3_ASSOC)) {
      $statusCounts = $this->getLibraryStatusCounts($librarySummary['id'], $librarySummary['count']);

      echo "          <span>" . PHP_EOL;
      echo "            <h5>" . PHP_EOL;
      echo "              {$librarySummary['name']}" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        echo "              <span class='badge badge-pill badge-{$this->statuses[$status]['class']}' style='cursor:default' data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}' onclick='void(0)'>{$stats['countFmt']}</span>" . PHP_EOL;
      }

      echo "            </h5>" . PHP_EOL;
      echo "          </span>" . PHP_EOL;
      echo "          <div class='progress mb-3'>" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        echo "            <div class='progress-bar progress-bar-striped bg-{$this->statuses[$status]['class']}' style='width:{$stats['percent']}%'></div>" . PHP_EOL;
      }

      echo "          </div>" . PHP_EOL;

      foreach ($statusCounts as $status => $stats) {
        $statusUpper = ucfirst($status);

        echo "          <div id='{$librarySummary['id']}-{$status}' class='collapse'>" . PHP_EOL;
        echo "            <div class='card border-{$this->statuses[$status]['class']} mb-3'>" . PHP_EOL;
        echo "              <div class='card-header'>" . PHP_EOL;
        echo "                <span><a data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}' class='close' style='cursor:default' onclick='void(0)'>&times;</a></span>" . PHP_EOL;
        echo "                <span>" . PHP_EOL;
        echo "                  <h5 class='text-{$this->statuses[$status]['class']} mb-0'>" . PHP_EOL;
        echo "                    {$statusUpper}" . PHP_EOL;
        echo "                    <span class='badge badge-pill badge-dark'>{$stats['percentFmt']}%</span>" . PHP_EOL;
        echo "                  </h5>" . PHP_EOL;
        echo "                </span>" . PHP_EOL;
        echo "              </div>" . PHP_EOL;
        echo "              <div class='card-body'>" . PHP_EOL;

        if ($this->limit == 0 || $stats['count'] <= $this->limit) {
          $statusDetails = $this->fetchLibraryStatusDetails($librarySummary['id'], $status);

          while ($statusDetail = $statusDetails->fetchArray(SQLITE3_ASSOC)) {
            parse_str($statusDetail['hints'], $hints);

            if (array_key_exists('name', $hints) && array_key_exists('year', $hints)) {
                echo "                <p class='card-text text-muted mb-0'>{$hints['name']} ({$hints['year']})</p>" . PHP_EOL;
            } elseif (array_key_exists('episode', $hints) && array_key_exists('season', $hints) && array_key_exists('show', $hints)) {
                $season = str_pad($hints['season'], 2, 0, STR_PAD_LEFT);
                $episode = str_pad($hints['episode'], 2, 0, STR_PAD_LEFT);

                echo "                <p class='card-text text-muted mb-0'>{$hints['show']} - s{$season}e{$episode} - {$statusDetail['title']}</p>" . PHP_EOL;
            } else {
                echo "                <p class='card-text mb-0'>{$statusDetail['file']}</p>" . PHP_EOL;
            }
          }
        } else {
            echo "                <p class='card-text'>This list exceeds the current limit of <strong>{$this->limit}</strong>!</p>" . PHP_EOL;
            echo "                <p class='card-text'><a href='?limit=0' class='text-danger'>Remove limit</a></p>" . PHP_EOL;
        }

        echo "              </div>" . PHP_EOL;
        echo "            </div>" . PHP_EOL;
        echo "          </div>" . PHP_EOL;
      }
    }

    if ($this->showStats) {
      $queryCountFmt = number_format($this->queryCount);
      $queryTimeFmt = round($this->queryTime * 1000);

      echo "          <span><small>{$queryCountFmt} queries took {$queryTimeFmt}ms</small></span>" . PHP_EOL;
    }

    echo "        </div>" . PHP_EOL;
    echo "        <div class='card-footer'>" . PHP_EOL;

    foreach ($this->statuses as $status => $options) {
      $statusUpper = ucfirst($status);

      echo "          <span class='badge badge-{$options['class']}' style='cursor:help' title='{$options['text']}'>{$statusUpper}</span>" . PHP_EOL;
    }

    if (array_key_exists('limit', $_REQUEST)) {
      echo "          <span class='float-right'><a href='{$_SERVER['PHP_SELF']}' class='text-success'>Reset limit</a></span>" . PHP_EOL;
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
<?php
new IndexStatus('/data/com.plexapp.plugins.library.db');
?>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.slim.min.js' integrity='sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js' integrity='sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js' integrity='sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ' crossorigin='anonymous'></script>
  </body>
</html>
