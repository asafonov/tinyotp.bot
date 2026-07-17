<?php

define('TOKEN', 'TELEGRAM_API_TOKEN');
define('BOT_NAME', 'telegram.bot');
define('PHP_BIN', '/usr/bin/php');
define('WORKER_PATH', '/var/www/html/' . BOT_NAME);
define('WORKER_CACHE_PATH', '/var/cache/' . BOT_NAME);
define('WORKER_LOG_PATH', '/var/log/' . BOT_NAME);
define('MAX_RETRIES', 3);
define('REQUEST_TIMEOUT', 8);
