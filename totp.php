<?php

function base32_decode ($b32) {
  $b32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $b32 = strtoupper($b32);
  $bits = '';

  for ($i = 0, $j = strlen($b32); $i < $j; ++$i) {
    $char = $b32[$i];
    $val = strpos($b32chars, $char);

    if ($val === false) {
      continue;
    }

    $bits .= str_pad(decbin($val), 5, 0, STR_PAD_LEFT);
  }

  $output = '';

  for ($i = 0, $j = strlen($bits); $i < $j; $i += 8) {
    $byte = substr($bits, $i, 8);

    if (strlen($byte) === 8) {
      $output .= chr(bindec($byte));
    }
  }

  return $output;
}

function generate_totp ($secret_b32, $time_step = 30, $digits = 6) {
  $time = time();
  $counter = intdiv($time, $time_step);
  $key = base32_decode($secret_b32);
  $hash = hash_hmac('sha1', pack('N*', $counter >> 32, $counter & 0xffffffff), $key, true);
  $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
  $code = (
    (ord($hash[$offset]) & 0x7f) << 24 |
    (ord($hash[$offset + 1]) & 0xff) << 16 |
    (ord($hash[$offset + 2]) & 0xff) << 8 |
    (ord($hash[$offset + 3]) & 0xff)
  ) & 0x7fffffff;
  $code %= pow(10, $digits);
  return str_pad($code, $digits, '0', STR_PAD_LEFT);
}

function test() {
  return generate_totp('JBSWY3DPEHPK3PXP');
}
