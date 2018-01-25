<?php
require_once('inc/auth.class.php');

$auth = new Auth();

if ($auth->isConfigured()) {
  if ($auth->isValidSession()) {
    header('Location: /');
    exit;
  } elseif (!empty($_POST['username']) && !empty($_POST['password']) && $auth->isValidCredentials($_POST['username'], $_POST['password'])) {
    $auth->authenticateSession($_POST['username']);
    header('Location: /');
    exit;
  }
} else {
  header('Location: /auth.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Log in</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css' integrity='sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb' crossorigin='anonymous'>
    <link rel='stylesheet' href='//bootswatch.com/4/darkly/bootstrap.min.css'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.0.2/css/all.css' integrity='sha384-bJB2Wn8ZuuMwYA12t6nmPqVTqT64ruKTAWqdxs/Oal3vexA7RPAo3FtVU5hIil2E' crossorigin='anonymous'>
    <link rel='stylesheet' href='css/login.css'>
  </head>
  <body>
    <div id='particles'></div>
    <div class='container'>
      <form class='login' method='post'>
<?php
if (!empty($auth->errors)) {
  echo "        <div class='form-group alert alert-danger'>" . PHP_EOL;
  foreach ($auth->errors as $error) {
    echo "          <p class='mb-0'>${error}</p>" . PHP_EOL;
  }
  echo "        </div>" . PHP_EOL;
} elseif (!empty($auth->warnings)) {
  echo "        <div class='form-group alert alert-warning'>" . PHP_EOL;
  foreach ($auth->warnings as $warning) {
    echo "          <p class='mb-0'>${warning}</p>" . PHP_EOL;
  }
  echo "        </div>" . PHP_EOL;
} elseif (!empty($auth->messages)) {
  echo "        <div class='form-group alert alert-info'>" . PHP_EOL;
  foreach ($auth->messages as $message) {
    echo "          <p class='mb-0'>${message}</p>" . PHP_EOL;
  }
  echo "        </div>" . PHP_EOL;
}
?>
        <div class='form-group'>
          <div class='input-group input-group-lg'>
            <span class='input-group-addon bg-secondary border border-dark fa fa-user'></span>
            <input type='text' class='form-control bg-secondary border border-dark text-white' placeholder='Username' name='username' required autofocus>
          </div>
        </div>
        <div class='form-group'>
          <div class='input-group input-group-lg'>
            <span class='input-group-addon bg-secondary border border-dark fa fa-key'></span>
            <input type='password' class='form-control bg-secondary border border-dark text-white' placeholder='Password' name='password' required>
          </div>
        </div>
        <button class='btn btn-lg btn-dark border border-dark btn-block' type='submit'>Log in</button>
      </form>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js' integrity='sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js' integrity='sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ' crossorigin='anonymous'></script>
    <script src='js/particles.js'></script>
  </body>
</html>
