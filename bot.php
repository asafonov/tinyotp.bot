<?php

require_once('config.php');
require_once('message.php');
require_once('totp.php');

function doCronLogic ($input) {
  //@TODO implement cron logic here, if needed
  $text = $input['message']['text'];
  $chatId = $input['message']['chat']['id'];

  return [
    'text' => 'text',
    'chat_id' => $chatId
  ];

}

function parseQR ($filename) {
  $command = "zbarimg --raw " . escapeshellarg($filename);
  exec($command, $output, $returnCode);

  if ($returnCode === 0 && ! empty($output)) {
    return $output;
  }

  return null;
}

function doLogic ($input) {
  $text = $input['message']['text'];
  $chatId = $input['message']['chat']['id'];

  if ($text == '/start') {
    return [
      'text' => START_MESSAGE,
      'chat_id' => $chatId
    ];
  }

  if ($text == '/add') {
    return [
      'text' => 'Now please send me the photo of the QR code you want to add',
      'chat_id' => $chatId
    ];    
  }

  if (isMessageWithPhoto($input)) {
    $photoUrl = getPhotoUrl($input);
    $savePath = WORKER_CACHE_PATH . '/' . $chatId;
    mkdir($savePath);
    $savePath .= '/' . basename($photoUrl)
    file_put_contents($savePath, file_get_contents($photoUrl));
    $url = parseQR($savePath);
    $parsed = parse_totp_url($url);
    $otp = generate_totp($parsed['secret']);

    return [
      'text' => 'Your confirmation code is ' . $otp,
      'chat_id' => $chatId
    ];    
  }

  return [
    'text' => 'Sorry, I didn\'t get that',
    'chat_id' => $chatId
  ];
}

function test ($text) {
  $input = ['message' => [
    'text' => $text,
    'chat' => ['id' => 'chat_id']
  ]];
  $reply = doLogic($input);
  print_r($reply);
}
