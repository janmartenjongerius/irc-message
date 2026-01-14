#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use function JanMarten\IRC\Message\parse;

$line = "@acme.org/id=1;acme.org/hash-algo=sha256;acme.org/hash=6ba1c07efba695e2b648a8e5353eac650b4da4051730d803492034c47e70d23d :JanMarten_Jongerius!janmarten@janmarten.name PRIVMSG #test :Hello, fellow chatters!\r\n";

// Accept data piped into the program.
if (!feof(STDIN)) {
    $line = sprintf("%s\r\n", trim(fgets(STDIN)));
}

$message = parse($line);

/**
 * Perform special operations on PRIVMSG commands.
 *
 * This supports the hypothetical scenario where the server can reject messages if the
 *   client requested the message to be verified with a hash.
 */
if ($message->command->verb === 'PRIVMSG' && $message->tags->contains('acme.org/hash')) {
    // Keep a hash of the acme.org specific tags.
    $ctx = hash_init(
        algo: $message->tags->unescape('acme.org/hash-algo'),
        flags: HASH_HMAC,
        key: $message->source->mask
    );

    // Find the acme.org tags.
    foreach ($message->tags as $key => $tag) {
        // Only process tags for the `acme.org` vendor.
        if ($tag->vendor !== 'acme.org') {
            continue;
        }

        // Ignore the tags that configure the hashing mechanism.
        if (str_starts_with($key, 'acme.org/hash')) {
            continue;
        }

        // Update the hash with the unescaped value of the current tag.
        hash_update($ctx, $message->tags->unescape($key));
    }

    // Finally, add the command arguments to the hash.
    foreach ($message->command->arguments as $argument) {
        hash_update($ctx, $argument);
    }

    $messageHash = hash_final($ctx);

    // Verify the hash.
    if ($messageHash !== $message->tags->unescape('acme.org/hash')) {
        printf(
            "Failed to verify message hash.\r\nCalculated <%s> and received <%s>.\r\n",
            $messageHash,
            $message->tags->unescape('acme.org/hash')
        );
        exit(1);
    }

    $arguments = $message->command->arguments;
    $channel = array_shift($arguments);

    printf(
        "\x1B[1m\x1B[38;5;177m%s\x1B[0m \x1B[38;5;12m%s:\x1B[0m \x1B[38;5;40m✔\x1B[0m %s\r\n",
        $channel,
        $message->source->nick,
        implode(' ', $arguments)
    );
}
