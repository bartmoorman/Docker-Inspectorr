<?php
require_once('inc/classes.php');

$obj = new PlexIndexStatus('/data/com.plexapp.plugins.library.db');
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Plex Index Status</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css' integrity='sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb' crossorigin='anonymous'>
    <link rel='stylesheet' href='//bootswatch.com/4/darkly/bootstrap.min.css' integrity='sha384-zgcDn7VSViWnGs0eVfIxnVBXnJ0g8LGs75PvAZBelfRWUl34y/1DAJ7TaUwZ7+LX' crossorigin='anonymous'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.0.2/css/all.css' integrity='sha384-bJB2Wn8ZuuMwYA12t6nmPqVTqT64ruKTAWqdxs/Oal3vexA7RPAo3FtVU5hIil2E' crossorigin='anonymous'>
  </head>
  <body>
    <div class='container'>
      <div class='card border-secondary my-3'>
        <div class='card-header'>
          <h3 class='mb-0'>Plex Index Status <span id='libraries-loading' class='fas fa-sync text-muted float-right'></span></h3>
        </div>
        <div id='libraries' class='card-body'>
        </div>
        <div class='card-footer'>
<?php
foreach ($obj->statuses as $status) {
  echo "          <span class='badge badge-{$status['class']}' style='cursor:help' title='{$status['hint']}'>{$status['text']}</span>" . PHP_EOL;
}
?>
        </div>
      </div>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js' integrity='sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js' integrity='sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ' crossorigin='anonymous'></script>
    <script src='js/main.js'></script>
  </body>
</html>
