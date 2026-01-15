#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use JanMarten\IRC\Message\Component\ImmutableSource;
use function JanMarten\IRC\Message\fromMessage;
use function JanMarten\IRC\Message\parse;

$originalMessage = parse('PING _');

echo fromMessage($originalMessage)
    ->withSource(ImmutableSource::fromEnv())
    ->withAddedTag('id', bin2hex(random_bytes(16)))
;
