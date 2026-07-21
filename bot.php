<?php

require_once('config.php');
require_once('message.php');

function doCronLogic ($input) {
  //@TODO implement cron logic here, if needed
  $text = $input['message']['text'];
  $chatId = $input['message']['chat']['id'];

  return [
    'text' => 'text',
    'chat_id' => $chatId
  ];

}

function doLogic ($input) {
  $text = $input['message']['text'];
  $chatId = $input['message']['chat']['id'];

  if ($text == '/start') {
    return [[
      'text' => START_MESSAGE,
      'chat_id' => $chatId
    ], null];
  }

  if ($text == '/add') {
    return [[
      'text' => 'Now please send me the photo of the QR code you want to add',
      'chat_id' => $chatId
    ], null];    
  }

  if (isMessageWithPhoto($input)) {
    $photoUrl = getPhotoUrl($input);
    $savePath = WORKER_CACHE_PATH . '/' . $chatId;
    mkdir($savePath);
    file_put_contents($savePath . '/' . basename($photoUrl), file_get_contents($photoUrl));

    return [[
      'text' => 'Got the photo',
      'chat_id' => $chatId
    ], null];    
  }

  return [
    'text' => $text,
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
