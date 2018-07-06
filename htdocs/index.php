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
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css' integrity='sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB' crossorigin='anonymous'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootswatch/4.1.1/darkly/bootstrap.min.css' integrity='sha384-ae362vOLHy2F1EfJtpMbNW0i9pNM1TP2l5O4VGYYiLJKsaejqVWibbP6BSf0UU5i' crossorigin='anonymous'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.1.0/css/all.css' integrity='sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt' crossorigin='anonymous'>
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
          <ul class='nav nav-tabs card-header-tabs float-left'>
<?php
foreach ($inspectorr->tabs as $tab => $details) {
  echo "            <li class='nav-item mb-0'>" . PHP_EOL;
  echo "              <a class='nav-link px-3 border-bottom-0' data-toggle='tab' data-target='#{$tab}' data-child='#{$tab}-legend' id='{$tab}-tab' href='javascript:void(0)'>" . PHP_EOL;
  echo "                <h4 class='mb-0'>" . PHP_EOL;
  echo "                  <span class='fa fa-fw fa-{$details['icon']}'></span>" . PHP_EOL;
  echo "                  <span class='d-none d-md-inline'>{$details['text']}</span>" . PHP_EOL;
  echo "                </h4>" . PHP_EOL;
  echo "              </a>" . PHP_EOL;
  echo "            </li>" . PHP_EOL;
}
?>
          </ul>
          <span class='fa fa-2x fa-spinner float-right' id='loading'></span>
        </div>
        <div class='card-body'>
          <div class='tab-content'>
<?php
foreach ($inspectorr->statuses as $tab => $statuses) {
  echo "            <div class='tab-pane fade' id='{$tab}'>" . PHP_EOL;
  foreach ($inspectorr->getLibraries() as $library) {
    $libraryStatusCounts = $inspectorr->getLibraryStatusCounts($tab, $library['id']);
    $libraryStatusSectionCounts = $inspectorr->getLibraryStatusSectionCounts($tab, $library['id']);
    echo "              <h4>" . PHP_EOL;
    echo "                <span>{$library['name']}</span>" . PHP_EOL;
    foreach($libraryStatusCounts as $libraryStatusCount) {
      $statusCount = number_format($libraryStatusCount['count']);
      echo "                <a class='badge badge-pill badge-{$statuses[$libraryStatusCount['status']]['class']}' data-toggle='collapse' data-target='#{$tab}-{$library['id']}-{$libraryStatusCount['status']}' href='javascript:void(0)'>" . PHP_EOL;
      echo "                  <span>{$statusCount}</span>" . PHP_EOL;
      echo "                  <span class='fa fa-chevron-down'></span>" . PHP_EOL;
      echo "                </a>" . PHP_EOL;
    }
    echo "              </h4>" . PHP_EOL;
    echo "              <div class='progress mb-3'>" . PHP_EOL;
    foreach ($libraryStatusCounts as $libraryStatusCount) {
      $statusPercent = round($libraryStatusCount['count'] * 100 / $library['count'], 2);
      echo "                <div class='progress-bar progress-bar-striped bg-{$statuses[$libraryStatusCount['status']]['class']}' style='width: {$statusPercent}%'></div>" . PHP_EOL;
    }
    echo "              </div>" . PHP_EOL;
    foreach ($libraryStatusSectionCounts as $status => $librarySectionCounts) {
      $statusPercent = round($libraryStatusCounts[$status]['count'] * 100 / $library['count'], 2);
      echo "              <div class='card border-{$statuses[$status]['class']} mb-3 collapse id-status' id='{$tab}-{$library['id']}-{$status}'>" . PHP_EOL;
      echo "                <div class='card-header'>" . PHP_EOL;
      echo "                  <h5 class='text-{$statuses[$status]['class']} mb-0'>" . PHP_EOL;
      echo "                    <span>{$statuses[$status]['text']}</span>" . PHP_EOL;
      echo "                    <span class='badge badge-pill badge-dark'>{$statusPercent}%</span>" . PHP_EOL;
      echo "                  </h5>" . PHP_EOL;
      echo "                </div>" . PHP_EOL;
      foreach ($librarySectionCounts as $librarySectionCount) {
        $sectionCount = number_format($librarySectionCount['count']);
        echo "                <div class='card-header'>" . PHP_EOL;
        echo "                  <span>{$librarySectionCount['root_path']}</span>" . PHP_EOL;
        echo "                  <a class='badge badge-pill badge-dark' data-toggle='collapse' data-target='#{$tab}-{$library['id']}-{$librarySectionCount['status']}-{$librarySectionCount['id']}' href='javascript:void(0)'>" . PHP_EOL;
        echo "                    <span>{$sectionCount}</span>" . PHP_EOL;
        echo "                    <span class='fa fa-chevron-down'></span>" . PHP_EOL;
        echo "                  </a>" . PHP_EOL;
        echo "                </div>" . PHP_EOL;
        echo "                <div class='card-body collapse p-0 id-section' data-tab='{$tab}' data-library='{$library['id']}' data-status='{$librarySectionCount['status']}' data-section='{$librarySectionCount['id']}' id='{$tab}-{$library['id']}-{$librarySectionCount['status']}-{$librarySectionCount['id']}'>" . PHP_EOL;
        echo "                  <div class='p-3'></div>" . PHP_EOL;
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
  echo "            <div class='tab-pane' id='{$tab}-legend'>" . PHP_EOL;
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
    <script src='//code.jquery.com/jquery-3.3.1.min.js' integrity='sha384-tsQFqpEReu7ZLhBV2VZlAu7zcOV+rXbYlF2cqB8txI/8aZajjp4Bqd+V6D5IgvKT' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js' integrity='sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js' integrity='sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T' crossorigin='anonymous'></script>
    <script>
      $(document).ready(function() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
          $($(e.target).data('child')).addClass('active show');
          localStorage.setItem('tab', $(e.target).data('target'));
        });

        $('a[data-toggle="tab"]').on('hidden.bs.tab', function(e) {
          $($(e.target).data('child')).removeClass('active show');
        });

        $('div.id-section').on('show.bs.collapse', function() {
          var parent = $(this).children('div');
          if (!parent.has('p').length) {
            $('#loading').removeClass('fa-exclamation-triangle').addClass('fa-spinner fa-pulse');
            $.get('src/action.php', {"func": "getLibraryStatusSectionDetails", "tab": $(this).data('tab'), "library": $(this).data('library'), "status": $(this).data('status'), "section": $(this).data('section')})
              .done(function(data) {
                if (data.success) {
                  $.each(data.data, function(key, value) {
                    $(`<p class='text-muted mb-0'>${value}</p>`).appendTo(parent);
                  });
                }
                $('#loading').on('animationiteration', function() {
                  $(this).removeClass('fa-pulse');
                });
              })
              .fail(function(jqxhr, textStatus, errorThrown) {
                console.log(`getLibraryStatusSectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
              })
              .always(function() {
                $(this).removeClass('fa-spinner fa-pulse').addClass('fa-exclamation-triangle');
              });
          }
        });

        if (result = localStorage.getItem('tab')) {
          $(`${result}-tab`).tab('show');
        } else {
          $('a[data-toggle="tab"]:first').tab('show');
        }

        $('a.badge').click(function() {
          $(this).children('span.fa').toggleClass('fa-flip-vertical');
        });

        $('button.id-nav').click(function() {
          location.href=$(this).data('href');
        });
      });
    </script>
  </body>
</html>
