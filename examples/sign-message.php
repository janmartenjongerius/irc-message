#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use JanMarten\IRC\Message\Contract\Component\Tag;
use function JanMarten\IRC\Message\fromMessage;
use function JanMarten\IRC\Message\message;

$userMessage = 'Hello, fellow chatters!';

if (count($argv) > 1) {
    $userMessage = implode(' ', array_slice($argv, 1));
}

$message = message()
    ->withAddedTag('id', '1', 'acme.org')
    ->command('PRIVMSG', '#test', $userMessage)
    ->build();

$hashAlgo = 'sha256';

// Keep a hash of the acme.org specific tags.
$ctx = hash_init(
    algo: $hashAlgo,
    flags: HASH_HMAC,
    key: $message->source->mask
);

$tags = iterator_to_array($message->tags);
$vendorTags = array_filter(
    $tags,
    fn (Tag $tag) => $tag->vendor === 'acme.org'
);
$tags = array_values($tags);

foreach ($vendorTags as $key => $tag) {
    // Update the hash with the unescaped value of the current tag.
    hash_update($ctx, $message->tags->unescape($key));
}

// Finally, add the command arguments to the hash.
foreach ($message->command->arguments as $argument) {
    hash_update($ctx, $argument);
}

echo fromMessage($message)
    ->withAddedTag('hash-algo', $hashAlgo, 'acme.org')
    ->withAddedTag('hash', hash_final($ctx), 'acme.org');
