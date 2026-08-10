<?php

require_once('config.php');
require_once('message.php');
require_once('totp.php');
require_once('phpqrcode/lib/qrlib.php');

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

function generateQR ($filename, $data) {
  QRcode::png($data, $filename, QR_ECLEVEL_H, 8, 2);
}

function getListKeyboardMarkup ($chatId) {
  $dir = WORKER_CACHE_PATH . '/' . $chatId . '/secrets';
  $keyboard = [];
  $files = scandir($dir);

  for ($i = 0, $j = count($files); $i < $j; ++$i) {
    if ($files[$i] === '.' || $files[$i] === '..') {
      continue;
    }

    $keyboard[] = [['text' => $files[$i], 'callback_data' => $files[$i]]];
  }

  return json_encode(['inline_keyboard' => $keyboard]);
}

function saveLastCommand ($command, $chatId) {
  file_put_contents(WORKER_CACHE_PATH . '/' . $chatId . '/last_command', $command);
}

function getLastCommand ($chatId) {
  return file_get_contents(WORKER_CACHE_PATH . '/' . $chatId . '/last_command');
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

  if ($text == '/list' || $text == '/export' || $text == '/delete') {
    saveLastCommand($text, $chatId);
    $reply_markup = getListKeyboardMarkup($chatId);
    $reply = [
      '/list' => 'Here is the list of your OTP providers',
      '/export' => 'Select the provider to export',
      '/delete' => 'Select the provider to delete. This operation is not revertable.'
    ];

    return [
      'text' => $reply[$text],
      'chat_id' => $chatId,
      'reply_markup' => $reply_markup
    ];
  }


  if ($text && $chatId) {
    $filename = WORKER_CACHE_PATH . '/' . $chatId . '/secrets/' . $text;

    if (file_exists($filename)) {
      $data = json_decode(file_get_contents($filename), true);
      $otp = generate_totp($data['secret']);

      return [
        'text' => 'Your OTP is ' . $otp,
        'chat_id' => $chatId
      ];
    }
  }

  if (isCallbackQuery($input)) {
    $query = getCallbackQueryData($input);
    $filename = WORKER_CACHE_PATH . '/' . $query['chat_id'] . '/secrets/' . $query['data'];
    $data = json_decode(file_get_contents($filename), true);
    $lastCommand = getLastCommand($query['chat_id']);

    if ($lastCommand === '/list') {
      $otp = generate_totp($data['secret']);

      return [
        'text' => 'Your OTP is ' . $otp,
        'chat_id' => $query['chat_id']
      ];
    } else if ($lastCommand === '/export') {
      $url = generate_url($data);
      $qrFilename = WORKER_CACHE_PATH . '/' . $query['chat_id'] . '/qr.png';
      file_exists($qrFilename) && unlink($qrFilename);
      generateQR($qrFilename, $url);

      return [
        'photo' => $qrFilename,
        'caption' => 'Your export for ' . $query['data'] . ' is ready',
        'chat_id' => $query['chat_id']
      ];
    } else if ($lastCommand === '/delete') {
      unlink($filename);

      return [
        'text' => 'Your OTP provider ' . $query['data'] . ' is deleted',
        'chat_id' => $query['chat_id']
      ];
    }
  }

  if (isMessageWithPhoto($input)) {
    $photoUrl = getPhotoUrl($input);
    $saveDir = WORKER_CACHE_PATH . '/' . $chatId;
    mkdir($saveDir);
    $savePath = $saveDir . '/' . basename($photoUrl);
    file_put_contents($savePath, getFileWithRetry($photoUrl));
    $url = parseQR($savePath);
    $parsed = parse_totp_url($url);

    if (isset($parsed['secret']) && $parsed['secret']) {
      $otp = generate_totp($parsed['secret']);
      $secretsDir = $saveDir . '/secrets';
      mkdir($secretsDir);

      $i = '';
      $key = $parsed['provider'];

      while (file_exists("$secretsDir/$key$i")) {
        $i = $i ? $i + 1 : 1;
      }

      file_put_contents($secretsDir . '/' . $key . $i, json_encode($parsed));

      return [
        'text' => 'Your confirmation code is ' . $otp,
        'chat_id' => $chatId
      ];
    }
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
