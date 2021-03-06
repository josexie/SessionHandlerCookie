<?php

class SessionHandlerCookie implements SessionHandlerInterface {

  private $data      = array();
  private $save_path = null;

  const HASH_LEN    = 128;
  const HASH_ALGO   = 'sha512';
  const HASH_SECRET = "YOUR_SECRET_STRING";

  public function open($save_path, $name) {
    $this->save_path = $save_path;
    return true;
  }

  public function read($id) {

    // Check for the existance of a cookie with the name of the session id
    // Make sure that the cookie is atleast the size of our hash, otherwise it's invalid
    // Return an empty string if it's invalid.
    if (! isset($_COOKIE[$id])) return '';

    // We expect the cookie to be base64 encoded, so let's decode it and make sure
    // that the cookie, at a minimum, is longer than our expact hash length. 
    $raw = base64_decode($_COOKIE[$id]);
    if (strlen($raw) < self::HASH_LEN) return '';

    // The cookie data contains the actual data w/ the hash concatonated to the end,
    // since the hash is a fixed length, we can extract the last HMAC_LENGTH chars
    // to get the hash.
    $hash = substr($raw, strlen($raw)-self::HASH_LEN, self::HASH_LEN);
    $data = substr($raw, 0, -(self::HASH_LEN));

    // Calculate what the hash should be, based on the data. If the data has not been
    // tampered with, $hash and $hash_calculated will be the same
    $hash_calculated = hash_hmac(self::HASH_ALGO, $data, self::HASH_SECRET);

    // If we calculate a different hash, we can't trust the data. Return an empty string.
    if ($hash_calculated !== $hash) return '';

    // Return the data, now that it's been verified.
    return (string)$data;

  }

  public function write($id, $data) {

    // Calculate a hash for the data and append it to the end of the data string
    $hash = hash_hmac(self::HASH_ALGO, $data, self::HASH_SECRET);
    $data .= $hash;

    // Set a cookie with the data
    setcookie($id, base64_encode($data), time()+3600);
  }

  public function destroy($id) {
    setcookie($id, '', time());
  }

  // In the context of cookies, these two methods are unneccessary, but must
  // be implemented as part of the SessionHandlerInterface.
  public function gc($maxlifetime) {}
  public function close() {}

}

$handler = new SessionHandlerCookie;
session_set_save_handler($handler, true);
session_start();