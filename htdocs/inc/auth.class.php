<?php
class Auth {
  private $authFile = '/config/credentials.json';
  private $credentials = array();
  public $errors = array();
  public $warnings = array();
  public $messages = array();

  public function __construct() {
    session_start();

    if (file_exists($this->authFile)) {
      $this->credentials = json_decode(file_get_contents($this->authFile), true);
    }
  }

  public function isConfigured() {
    if (!empty($this->credentials)) {
      return true;
    }

    return false;
  }

  public function isConfigurable() {
    if (is_writable(dirname($this->authFile))) {
      return true;
    } else {
      $this->errors[] = dirname($this->authFile) . ' is not writable!';
    }

    return false;
  }

  public function authenticateSession($username) {
    $_SESSION['authenticated'] = true;
    $_SESSION['username'] = $username;
  }

  public function deauthenticateSession() {
    if (session_destroy()) {
      return true;
    }

    return false;
  }

  public function isValidCredentials($username, $password) {
    if (array_key_exists($username, $this->credentials) && password_verify($password, $this->credentials[$username])) {
      return true;
    } else {
      $this->errors[] = 'Invalid username and/or password!';
    }

    return false;
  }

  public function isValidSession() {
    if (array_key_exists('authenticated', $_SESSION) && array_key_exists($_SESSION['username'], $this->credentials)) {
      return true;
    }

    return false;
  }

  public function createUser($username, $password, $verify_password) {
    if ($password == $verify_password) {
      $this->credentials[$username] = password_hash($password, PASSWORD_DEFAULT);
      file_put_contents($this->authFile, json_encode($this->credentials));

      return true;
    } else {
      $this->errors[] = 'Passwords do not match!';
    }

    return false;
  }

  public function removeUser($username) {
    unset($this->credentials[$username]);
    file_put_contents($this->authFile, json_encode($credentials));
  }
}
