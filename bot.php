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
    return $output[0];
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

  if ($text == '/list') {
    $dir = WORKER_CACHE_PATH . '/' . $chatId . '/secrets';
    $keyboard = [];
    $files = scandir($dir);

    for ($i = 0, $j = count($files); $i < $j; ++$i) {
      if ($files[$i] === '.' || $files[$i] === '..') {
        continue;
      }

      $keyboard[] = ['text' => $files[$i], 'callback_data' => $files[$i]];
    }

    $reply_markup = json_encode(['inline_keyboard' => [$keyboard]]);

    return [
      'text' => 'Here is the list of you TOTPs',
      'chat_id' => $chatId,
      'reply_markup' => $reply_markup
    ];
  }

  if (isCallbackQuery($input)) {
    $query = getCallbackQueryData($input);
    $filename = WORKER_CACHE_PATH . '/' . $query['chat_id'] . '/secrets/' . $query['data'];
    $data = json_decode(file_get_contents($filename));
    $otp = generate_totp($data['secret']);

    return [
      'text' => 'Your confirmation code is ' . $otp,
      'chat_id' => $query['chat_id']
    ];
  }

  if (isMessageWithPhoto($input)) {
    $photoUrl = getPhotoUrl($input);
    $saveDir = WORKER_CACHE_PATH . '/' . $chatId;
    mkdir($saveDir);
    $savePath = $saveDir . '/' . basename($photoUrl);
    file_put_contents($savePath, getFileWithRetry($photoUrl));
    $url = parseQR($savePath);
    $parsed = parse_totp_url($url);
    $otp = generate_totp($parsed['secret']);
    $secretsDir = $saveDir . '/secrets';
    mkdir($secretsDir);
    $key = "{$parsed['provider']}:{$parsed['username']}";
    file_put_contents($secretsDir . '/' . $key, json_encode($parsed));

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
