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
  //@TODO implement bot logic here
  $text = $input['message']['text'];
  $chatId = $input['message']['chat']['id'];

  return [
    'text' => 'text',
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
