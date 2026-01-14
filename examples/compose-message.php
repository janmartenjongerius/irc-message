#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function JanMarten\IRC\Message\message;

$message = implode(
    ' ',
    array_slice($argv, 1) ?: ['Hello,', 'World!']
);

echo message()->command('PRIVMSG', $message);
