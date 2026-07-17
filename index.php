<?php

require_once('config.php');

try {
  $input = file_get_contents('php://input');
  file_put_contents(WORKER_CACHE_PATH . "/last_input", $input);
  $data = json_decode($input, true);
  $chatId = isset($data['message']['chat']['id']) ? $data['message']['chat']['id'] : null;
  $hasMessage = isset($data['message']['text']) || isset($data['message']['photo']);

  if ($hasMessage && $chatId) {
    $jobId = uniqid();
    file_put_contents(WORKER_CACHE_PATH . "/$jobId", $input);
    $workerCommand = PHP_BIN . ' ' . WORKER_PATH . '/worker.php ' . $jobId . ' > /dev/null 2>&1 &';
    exec($workerCommand);
  }
} catch (Exception $e) {
  die();
}

die();
