<?php
require_once('inc/auth.class.php');

$auth = new Auth();

if ($auth->isConfigured()) {
  header('Location: login.php');
  exit;
} elseif ($auth->isConfigurable() && !empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['verify_password'])) {
  if ($auth->createUser($_POST['username'], $_POST['password'], $_POST['verify_password'])) {
    $auth->authenticateSession($_POST['username']);
    header('Location: ' . dirname($_SERVER['PHP_SELF']));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Log in</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css' integrity='sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm' crossorigin='anonymous'>
    <link rel='stylesheet' href='//bootswatch.com/4/darkly/bootstrap.min.css'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.0.12/css/all.css' integrity='sha384-G0fIWCsCzJIMAVNQPfjH08cyYaUtMwjJwqiRKxxE/rx96Uroj1BtIQ6MLJuheaO9' crossorigin='anonymous'>
    <link rel='stylesheet' href='css/login.css'>
  </head>
  <body>
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
            <div class='input-group-prepend'>
              <span class='input-group-text bg-secondary border border-dark fa fa-user'></span>
            </div>
            <input type='text' class='form-control bg-secondary border border-dark text-white' placeholder='Username' name='username' required autofocus>
          </div>
        </div>
        <div class='form-group'>
          <div class='input-group input-group-lg'>
            <div class='input-group-prepend'>
              <span class='input-group-text bg-secondary border border-dark fa fa-key'></span>
            </div>
            <input type='password' class='form-control bg-secondary border border-dark text-white' placeholder='Password' name='password' required>
          </div>
        </div>
        <div class='form-group'>
          <div class='input-group input-group-lg'>
            <div class='input-group-prepend'>
              <span class='input-group-text bg-secondary border border-dark fa fa-key'></span>
            </div>
            <input type='password' class='form-control bg-secondary border border-dark text-white' placeholder='Verify Password' name='verify_password' required>
          </div>
        </div>
<?php
if ($auth->isConfigurable()) {
  echo "          <button class='btn btn-lg btn-dark border border-dark btn-block' type='submit'>Enable Authentication</button>" . PHP_EOL;
} else {
  echo "          <button class='btn btn-lg btn-dark border border-dark btn-block' type='submit' disabled>Cannot Enable Authentication</button>" . PHP_EOL;
}
?>
      </form>
    </div>
    <script src='//code.jquery.com/jquery-3.2.1.min.js' integrity='sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js' integrity='sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js' integrity='sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl' crossorigin='anonymous'></script>
  </body>
</html>
