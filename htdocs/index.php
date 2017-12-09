<?php
class IndexStatus {
  private $statuses = array(
    'complete' => array('class' => 'success', 'text' => 'Indexing is complete'),
    'pending' => array('class' => 'info', 'text' => 'Indexing has not started'),
    'failed' => array('class' => 'warning', 'text' => 'Indexing failed (partially corrupt media)'),
    'unknown' => array('class' => 'danger', 'text' => 'Indexing not possible (fully corrupt media)')
  );

  public function __construct($dbFile) {
    $dbDir = dirname($dbFile);

    $dbFileReadable = file_exists($dbFile) ? is_readable($dbFile) : false;
    $dbDirWritable = file_exists($dbDir) ? is_writable($dbDir) : false;
    $dbFileShmWritable = file_exists("{$dbFile}-shm") ? is_writable("{$dbFile}-shm") : $dbDirWritable;
    $dbFileWalWritable = file_exists("{$dbFile}-wal") ? is_writable("{$dbFile}-wal") : $dbDirWritable;

    if ($dbFileReadable && $dbDirWritable && $dbFileShmWritable && $dbFileWalWritable) {
      if (!file_exists("{$dbFile}-shm") || !file_exists("{$dbFile}-wal")) {
        echo "      <div class='alert alert-dismissable alert-info'>" . PHP_EOL;
        echo "        <button type='button' class='close' data-dismiss='alert'>&times;</button>" . PHP_EOL;

        if (!file_exists("{$dbFile}-shm")) {
          echo "        <p><strong>{$dbFile}-shm</strong> doesn't exist and will be created.</p>" . PHP_EOL;
        }

        if (!file_exists("{$dbFile}-wal")) {
          echo "        <p><strong>{$dbFile}-wal</strong> doesn't exist and will be created.</p>" . PHP_EOL;
        }

        echo "      </div>" . PHP_EOL;
      }

      $this->db = new SQLite3($dbFile, SQLITE3_OPEN_READONLY);
      $this->showOverview();
    } else {
      echo "      <div class='alert alert-danger'>" . PHP_EOL;
      echo "        <h4>Something went wrong.</h4>" . PHP_EOL;

      if (!$dbFileReadable) {
        echo "        <p>We can't read <strong>{$dbFile}</strong>!</p>" . PHP_EOL;
      } elseif (!$dbDirWritable) {
        echo "        <p>We can't write to <strong>{$dbDir}</strong>!</p>" . PHP_EOL;
      } elseif (!$dbFileShmWritable) {
        echo "        <p>We can't write to <strong>{$dbFile}-shm</strong>!</p>" . PHP_EOL;
      } elseif (!$dbFileWalWritable) {
        echo "        <p>We can't write to <strong>{$dbFile}-wal</strong>!</p>" . PHP_EOL;
      } else {
        echo "        <p>But we don't know what... Maybe check the logs?</p>" . PHP_EOL;
      }

      echo "      </div>" . PHP_EOL;
    }
  }

  private function getStatusFilters($status) {
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

  private function fetchLibrarySummary() {
    $query = <<<EOQ
SELECT lib.id, lib.name, COUNT(*) AS count
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
GROUP BY lib.id
ORDER BY lib.name
EOQ;

    return $this->db->query($query);
  }

  private function fetchLibraryStatusCount($library, $status) {
    $query = <<<EOQ
SELECT COUNT(*) AS count
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
WHERE lib.id = {$library}
{$this->getStatusFilters($status)}
EOQ;

    return $this->db->query($query);
  }

  private function fetchLibraryStatusDetails($library, $status) {
    $query = <<<EOQ
SELECT meta.id, meta.title, mi.hints, mp.file
FROM library_sections AS lib
JOIN metadata_items AS meta ON meta.library_section_id = lib.id
JOIN media_items AS mi ON mi.metadata_item_id = meta.id
JOIN media_parts AS mp ON mp.media_item_id = mi.id
WHERE lib.id = {$library}
{$this->getStatusFilters($status)}
ORDER BY mp.file
EOQ;

    return $this->db->query($query);
  }

  private function showOverview() {
    $limit = array_key_exists('limit', $_REQUEST) ? $_REQUEST['limit'] : 250;

    echo "      <div class='panel panel-default'>" . PHP_EOL;
    echo "        <div class='panel-body'>" . PHP_EOL;

    $librarySummaries = $this->fetchLibrarySummary();

    while ($librarySummary = $librarySummaries->fetchArray(SQLITE3_ASSOC)) {
      $librarySummaryCountFmt = number_format($librarySummary['count']);

      echo "          <h4>{$librarySummary['name']} <span class='badge'>{$librarySummaryCountFmt}</span></h4>" . PHP_EOL;
      echo "          <div class='progress progress-striped'>" . PHP_EOL;

      foreach ($this->statuses as $status => $options) {
        $statusCount = $this->fetchLibraryStatusCount($librarySummary['id'], $status)->fetchArray(SQLITE3_ASSOC);
        $libraryStatusCounts[$status] = $statusCount['count'];

        if ($statusCount['count'] > 0) {
          $statusPercent = round($statusCount['count'] / $librarySummary['count'] * 100);

          echo "            <div data-toggle='collapse' data-target='#{$librarySummary['id']}-{$status}' class='progress-bar progress-bar-{$options['class']}' style='width:{$statusPercent}%;cursor:pointer'>{$statusPercent}%</div>" . PHP_EOL;
        }
      }

      echo "          </div>" . PHP_EOL;

      foreach ($libraryStatusCounts as $status => $count) {
        if ($count > 0) {
          $statusUpper = ucfirst($status);
          $countFmt = number_format($count);

          echo "          <div id='{$librarySummary['id']}-{$status}' class='panel panel-{$this->statuses[$status]['class']} collapse'>" . PHP_EOL;
          echo "            <div class='panel-heading'>" . PHP_EOL;
          echo "              <h4>{$statusUpper} <span class='badge'>{$countFmt}</span></h4>" . PHP_EOL;
          echo "            </div>" . PHP_EOL;
          echo "            <div class='panel-body'>" . PHP_EOL;

          if ($limit == 0 || $count < $limit) {
            $statusDetails = $this->fetchLibraryStatusDetails($librarySummary['id'], $status);

            while ($statusDetail = $statusDetails->fetchArray(SQLITE3_ASSOC)) {
              parse_str($statusDetail['hints'], $hints);

              if (array_key_exists('name', $hints) && array_key_exists('year', $hints)) {
                  echo "              <p class='text-muted'>{$hints['name']} ({$hints['year']})</p>" . PHP_EOL;
              } elseif (array_key_exists('episode', $hints) && array_key_exists('season', $hints) && array_key_exists('show', $hints)) {
                  $season = str_pad($hints['season'], 2, 0, STR_PAD_LEFT);
                  $episode = str_pad($hints['episode'], 2, 0, STR_PAD_LEFT);
                  echo "              <p class='text-muted'>{$hints['show']} - s{$season}e{$episode} - {$statusDetail['title']}</p>" . PHP_EOL;
              } else {
                  echo "              <p>{$statusDetail['file']}</p>" . PHP_EOL;
              }
            }
          } else {
              echo "              <p>This list is too big! We saved your browser. You're welcome.</p>" . PHP_EOL;
              echo "              <p><a href='?limit=0'>Remove limit</a></p>" . PHP_EOL;
          }

          echo "            </div>" . PHP_EOL;
          echo "          </div>" . PHP_EOL;
        }
      }
    }

    echo "        </div>" . PHP_EOL;
    echo "        <div class='panel-footer'>" . PHP_EOL;

    foreach ($this->statuses as $status => $options) {
      $statusUpper = ucfirst($status);

      echo "          <span class='label label-{$options['class']}' title='{$options['text']}'>{$statusUpper}</span>" . PHP_EOL;
    }

    if (array_key_exists('limit', $_REQUEST)) {
      echo "          <span class='pull-right'><a href='{$_SERVER['PHP_SELF']}'>Reset limit</a></span>" . PHP_EOL;
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
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' integrity='sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u' crossorigin='anonymous'>
    <link rel='stylesheet' href='//bootswatch.com/3/darkly/bootstrap.min.css'>
<!--
    <link rel='stylesheet' href='//bootswatch.com/3/cyborg/bootstrap.min.css'>
-->
    <style>
      html {zoom:125%}
    </style>
  </head>
  <body>
    <div class='container'>
      <h1>Plex Index Status</h1>
<?php
new IndexStatus('/data/com.plexapp.plugins.library.db');
?>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.slim.min.js' integrity='sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js' integrity='sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js' integrity='sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa' crossorigin='anonymous'></script>
  </body>
</html>
