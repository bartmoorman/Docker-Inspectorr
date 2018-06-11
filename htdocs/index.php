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
            <li class='nav-item mb-0'>
              <a class='nav-link border-bottom-0 active' data-toggle='tab' href='#index-status' role='tab'><h4 class='mb-0'><span class='fa fa-check'></span> <span>Index Status</span></h4></a>
            </li>
            <li class='nav-item mb-0'>
              <a class='nav-link border-bottom-0' data-toggle='tab' href='#audio-quality' role='tab'><h4 class='mb-0'><span class='fa fa-headphones'></span> <span>Audio Quality</span></h4></a>
            </li>
            <li class='nav-item mb-0'>
              <a class='nav-link border-bottom-0' data-toggle='tab' href='#video-quality' role='tab'><h4 class='mb-0'><span class='fa fa-video'></span> <span>Video Quality</span></h4></a>
            </li>
          </ul>
        </div>
        <div class='card-body'>
          <div class='tab-content'>
            <div class='tab-pane fade active show' id='index-status' role='tabpanel'>
<?php
foreach ($inspectorr->getLibraries() as $library) {
  echo "              <h4>{$library['name']}</h4>" . PHP_EOL;
}
?>
            </div>
            <div class='tab-pane fade' id='audio-quality' role='tabpanel'>
<?php
foreach ($inspectorr->getLibraries() as $library) {
  echo "              <h4>{$library['name']}</h4>" . PHP_EOL;
}
?>
            </div>
            <div class='tab-pane fade' id='video-quality' role='tabpanel'>
<?php
foreach ($inspectorr->getLibraries() as $library) {
  echo "              <h4>{$library['name']}</h4>" . PHP_EOL;
}
?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js' integrity='sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js' integrity='sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl' crossorigin='anonymous'></script>
    <script>
      $(document).ready(function() {
        $('button.id-nav').click(function() {
          location.href=$(this).data('href');
        });
      });
    </script>
  </body>
</html>
