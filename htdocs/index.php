<?php
require_once('inc/inspectorr.class.php');
$inspectorr = new Inspectorr(true, true, false, false);
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Inspectorr - Index</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css' integrity='sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm' crossorigin='anonymous'>
    <link rel='stylesheet' href='//bootswatch.com/4/darkly/bootstrap.min.css'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.0.12/css/all.css' integrity='sha384-G0fIWCsCzJIMAVNQPfjH08cyYaUtMwjJwqiRKxxE/rx96Uroj1BtIQ6MLJuheaO9' crossorigin='anonymous'>
  </head>
  <body>
<?php
if ($inspectorr->isAdmin()) {
  $homeLoc = dirname($_SERVER['PHP_SELF']);
  echo "    <nav class='navbar'>" . PHP_EOL;
  echo "      <button class='btn btn-sm btn-outline-success id-nav' data-href='{$homeLoc}'>Home</button>" . PHP_EOL;
  echo "      <button class='btn btn-sm btn-outline-info ml-auto mr-2 id-nav' data-href='users.php'>Users</button>" . PHP_EOL;
  echo "      <button class='btn btn-sm btn-outline-info id-nav' data-href='events.php'>Events</button>" . PHP_EOL;
  echo "    </nav>" . PHP_EOL;
}
?>
    <div class='container'>
      <div class='card'>
        <div class='card-header border-bottom-0'>
          <ul class='nav nav-tabs card-header-tabs' role='tablist'>
<?php
foreach ($inspectorr->tabs as $tab => $details) {
  echo "            <li class='nav-item mb-0'>" . PHP_EOL;
  echo "              <a class='nav-link px-3 border-bottom-0' id='{$tab}-header' data-toggle='tab' data-tab='{$tab}' href='#{$tab}-body' role='tab'>" . PHP_EOL;
  echo "                <h4 class='mb-0'>" . PHP_EOL;
  echo "                  <span class='fa fa-{$details['icon']}'></span>" . PHP_EOL;
  echo "                  <span class='d-none d-md-inline'>{$details['text']}</span>" . PHP_EOL;
  echo "                </h4>" . PHP_EOL;
  echo "              </a>" . PHP_EOL;
  echo "            </li>" . PHP_EOL;
}
?>
          </ul>
        </div>
        <div class='card-body'>
          <div class='tab-content'>
<?php
foreach ($inspectorr->statuses as $tab => $statuses) {
  echo "            <div class='tab-pane fade' id='{$tab}-body' role='tabpanel'>" . PHP_EOL;
  foreach ($inspectorr->getLibraries() as $library) {
    $libraryStatusCounts = $inspectorr->getLibraryStatusCounts($tab, $library);
    echo "              <h4>" . PHP_EOL;
    echo "                <span>{$library['name']}</span>" . PHP_EOL;
    foreach($libraryStatusCounts as $libraryStatusCount) {
      $statusCount = number_format($libraryStatusCount['count']);
      echo "                <span class='badge badge-pill badge-{$statuses[$libraryStatusCount['status']]['class']}'>" . PHP_EOL;
      echo "                  <span>{$statusCount}</span>" . PHP_EOL;
      echo "                  <span class='fa fa-chevron-down'></span>" . PHP_EOL;
      echo "                </span>" . PHP_EOL;
    }
    echo "              </h4>" . PHP_EOL;
    echo "              <div class='progress mb-3'>" . PHP_EOL;
    foreach ($libraryStatusCounts as $libraryStatusCount) {
      $statusPercent = $libraryStatusCount['count'] * 100 / $library['count'];
      echo "                <div class='progress-bar progress-bar-striped bg-{$statuses[$libraryStatusCount['status']]['class']}' style='width: {$statusPercent}%'></div>" . PHP_EOL;
    }
    echo "              </div>" . PHP_EOL;
    foreach ($libraryStatusCounts as $libraryStatusCount) {
      $statusPercent = round($libraryStatusCount['count'] * 100 / $library['count'], 2);
      echo "              <div class='card border-{$statuses[$libraryStatusCount['status']]['class']} d-none mb-3'>" . PHP_EOL;
      echo "                <div class='card-header'>" . PHP_EOL;
      echo "                  <h5 class='text-{$statuses[$libraryStatusCount['status']]['class']} mb-0'>" . PHP_EOL;
      echo "                    <span>{$statuses[$libraryStatusCount['status']]['text']}</span>" . PHP_EOL;
      echo "                    <span class='badge badge-pill badge-dark'>{$statusPercent}%</span>" . PHP_EOL;
      echo "                  </h5>" . PHP_EOL;
      echo "                </div>" . PHP_EOL;
      foreach ($inspectorr->getLibrarySectionCounts($tab, $library, $libraryStatusCount['status']) as $librarySectionCount) {
        $sectionCount = number_format($librarySectionCount['count']);
        echo "                <div class='card-header'>" . PHP_EOL;
        echo "                  <span>{$librarySectionCount['root_path']}</span>" . PHP_EOL;
        echo "                  <span class='badge badge-pill badge-dark'>" . PHP_EOL;
        echo "                    <span>{$sectionCount}</span>" . PHP_EOL;
        echo "                    <span class='fa fa-chevron-down'></span>" . PHP_EOL;
        echo "                  </span>" . PHP_EOL;
        echo "                </div>" . PHP_EOL;
        echo "                <div class='card-body d-none'>" . PHP_EOL;
        echo "                </div>" . PHP_EOL;
      }
      echo "              </div>" . PHP_EOL;
    }
  }
  echo "            </div>" . PHP_EOL;
}
?>
          </div>
        </div>
        <div class='card-footer'>
          <div class='tab-content'>
<?php
foreach ($inspectorr->statuses as $tab => $statuses) {
  echo "            <div class='tab-pane' id='{$tab}-footer' role='tabpanel'>" . PHP_EOL;
  foreach ($statuses as $status) {
    echo "              <span class='badge badge-{$status['class']}' title='{$status['hint']}'>{$status['text']}</span>" . PHP_EOL;
  }
  echo "            </div>" . PHP_EOL;
}
?>
          </div>
        </div>
      </div>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js' integrity='sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js' integrity='sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl' crossorigin='anonymous'></script>
    <script>
      $(document).ready(function() {
        var cookies = ['tab'];
        var areas = ['header', 'body', 'footer'];
        var selections = {};

        $.each(document.cookie.split(';'), function() {
          var [key, value] = $.trim(this).split('=');
          if (cookies.includes(key)) {
            selections[key] = value;
          }
        });

        if (selections.hasOwnProperty('tab')) {
          $.each(areas, function (index, area) {
            $(`#${selections['tab']}-${area}`).addClass('active show');
          });
        } else {
          $('div.card-header ul.nav.nav-tabs li.nav-item:first-child a.nav-link').addClass('active show');
          $('div.card-body div.tab-content div.tab-pane:first-child ').addClass('active show');
          $('div.card-footer div.tab-content div.tab-pane:first-child').addClass('active show');
        }

        $('a[data-toggle="tab"]').on('hidden.bs.tab', function(e) {
          $(`#${$(e.target).data('tab')}-footer`).removeClass('active show');
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
          $(`#${$(e.target).data('tab')}-footer`).addClass('active show');
          document.cookie = `tab=${$(e.target).data('tab')}`;
        });

        $('button.id-nav').click(function() {
          location.href=$(this).data('href');
        });
      });
    </script>
  </body>
</html>
