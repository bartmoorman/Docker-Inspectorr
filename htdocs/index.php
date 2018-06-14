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
          <ul class='nav nav-tabs card-header-tabs float-left'>
<?php
$i = 0;
foreach ($inspectorr->tabs as $tab => $details) {
  $class = $i++ == 0 ? 'nav-link active' : 'nav-link';
  echo "            <li class='nav-item mb-0'>" . PHP_EOL;
  echo "              <a class='{$class} px-3 border-bottom-0' data-toggle='tab' data-target='#{$tab}' data-child='#{$tab}-legend' href='javascript:void(0)'>" . PHP_EOL;
  echo "                <h4 class='mb-0'>" . PHP_EOL;
  echo "                  <span class='fa fa-fw fa-{$details['icon']}'></span>" . PHP_EOL;
  echo "                  <span class='d-none d-md-inline'>{$details['text']}</span>" . PHP_EOL;
  echo "                </h4>" . PHP_EOL;
  echo "              </a>" . PHP_EOL;
  echo "            </li>" . PHP_EOL;
}
?>
          </ul>
          <span class='fa fa-2x fa-circle-notch float-right' id='loading'></span>
        </div>
        <div class='card-body'>
          <div class='tab-content'>
<?php
$i = 0;
foreach ($inspectorr->statuses as $tab => $statuses) {
  $class = $i++ == 0 ? 'tab-pane fade active show' : 'tab-pane fade';
  echo "            <div class='{$class}' id='{$tab}'>" . PHP_EOL;
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
      echo "              <div class='card border-{$statuses[$status]['class']} mb-3 collapse' id='{$tab}-{$library['id']}-{$status}'>" . PHP_EOL;
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
        echo "                <div class='card-body collapse p-0' data-tab='{$tab}' data-library='{$library['id']}' data-status='{$librarySectionCount['status']}' data-section='{$librarySectionCount['id']}' id='{$tab}-{$library['id']}-{$librarySectionCount['status']}-{$librarySectionCount['id']}'>" . PHP_EOL;
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
$i = 0;
foreach ($inspectorr->statuses as $tab => $statuses) {
  $class = $i++ == 0 ? 'tab-pane active' : 'tab-pane';
  echo "            <div class='{$class}' id='{$tab}-legend'>" . PHP_EOL;
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
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
          $($(e.target).data('child')).addClass('active');
        });

        $('a[data-toggle="tab"]').on('hidden.bs.tab', function(e) {
          $($(e.target).data('child')).removeClass('active');
        });

        $('a.badge').click(function() {
          $(this).children('span.fa').toggleClass('fa-flip-vertical');
        });

        $('div.card-body.collapse').on('show.bs.collapse', function() {
          var parent = $(this).children('div');
          if (!parent.has('p').length) {
            $('#loading').removeClass('fa-exclamation-triangle').addClass('fa-circle-notch fa-spin');
            $.get('src/action.php', {"func": "getLibraryStatusSectionDetails", "tab": $(this).data('tab'), "library": $(this).data('library'), "status": $(this).data('status'), "section": $(this).data('section')})
              .done(function(data) {
                if (data.success) {
                  $.each(data.data, function(key, value) {
                    $(`<p class='text-muted mb-0'>${value}</p>`).appendTo(parent);
                  });
                }
                $('#loading').on('animationiteration', function() {
                  $(this).removeClass('fa-spin');
                });
              })
              .fail(function(jqxhr, textStatus, errorThrown) {
                console.log(`getLibraryStatusSectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
              })
              .always(function() {
                $(this).removeClass('fa-circle-notch fa-spin').addClass('fa-exclamation-triangle');
              });
          }
        });

        $('button.id-nav').click(function() {
          location.href=$(this).data('href');
        });
      });
    </script>
  </body>
</html>
