The purpose of this library is to understand IRC message syntax and expose its
  components as modern data structures inside PHP.

It implements IRC message representation following:

- https://ircv3.net/irc/
- https://modern.ircdocs.horse/

The aim is to be compliant with the IRCv3 specification as described in the
aforementioned documentation.

# Table of contents

- [Installation](#installation)
- [Parsing IRC message lines](#parsing-irc-message-lines)
  - [Parsing a message](#parsing-a-message)
  - [Parsing a command](#parsing-a-command)
  - [Parsing a source](#parsing-a-source)
  - [Parsing tags](#parsing-tags)
- [Composing IRC messages](#composing-irc-messages)
  - [Composing new messages](#composing-new-messages)
  - [Patching command messages](#patching-command-messages)
- [Formatting IRC message lines](#formatting-irc-message-lines)
  - [Formatting a message](#formatting-a-message)
  - [Formatting a command](#formatting-a-command)
  - [Formatting a source](#formatting-a-source)
  - [Formatting tags](#formatting-tags)

# Installation

As a composer package, install it as follows:

```bash
composer require janmarten/irc-message
```

# Parsing IRC message lines

Make sure that messages provided to the parser are following the IRC specification.
This means that the message length, tag list length, and even the trailing CRLF
are enforced according to the specification for IRCv3.

The following two example files show a custom implementation of IRC message
  signing using the HMAC method.

- [Sign an IRC message](examples/sign-message.php)
- [Verify an IRC message](examples/verify-message.php)

```bash
examples/sign-message.php Hello, World! | examples/verify-message.php
```

## Parsing a message

If the goal is to parse an entire IRC message line, use the message parser.

Either the `parse` function:

```php
use function \JanMarten\IRC\Message\parse;

$message = parse('@acme.org/id=1 :Johnny!john@acme.org PRIVMSG #test :Hello, fellow chatters!');
```

> N.B.: The `parse` function will automatically append CRLF `\r\n` to the end of
>   the string, if not present.

Or an instance of the message parser:

```php
use JanMarten\IRC\Message\Contract\Parser\MessageParser;

/** @var MessageParser $parser */
$message = $parser->parseMessage(
    "@acme.org/id=1 :Johnny!john@acme.org PRIVMSG #test :Hello, fellow chatters!\r\n"
);
```

> N.B.: The message parser is stricter than the `parse` function and will find
>   messages without trailing CRLF `\r\n` to not be a well-formed IRC message.

The message above can be represented by the following structure:

```yaml
message:
  command:
    verb: 'PRIVMSG'
    arguments:
      - '#test'
      - 'Hello, fellow chatters!'

  source:
    mask: ':Johnny!john@acme.org'
    nick: 'Johnny'
    user: 'john'
    host: 'acme.org'

  tags:
    acme.org/id:
      key: 'acme.org/id'
      value: '1'
      clientOnly: false
```

The source object may be omitted when there is no source in the message.
Contrary to the source, the tags will always be represented by a tag list object,
even if there were no tags provided in the message, to make using tags as config
easier.

The following exceptions can be produced when parsing a message:

| Exception name              | Expected when...                                                                           |
|:----------------------------|:-------------------------------------------------------------------------------------------|
| `MalformedMessageException` | When the message is not well-formed against the ABNF.                                      |
| `MessageTooLongException`   | When the message length exceeds the spec limits. Both including and excluding tags length. |

## Parsing a command

If the goal is to only parse the command and its arguments, then the following
parser is able to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Parser\CommandParser

/** @var CommandParser $parser */
$command = $parser->parseCommand('PRIVMSG #test :Hello, world!');
```

The command above can be represented by the following structure:

```yaml
command:
  verb: 'PRIVMSG'
  arguments:
    - '#test'
    - 'Hello, world!'
```

The following exceptions can be produced when parsing a command:

| Exception name              | Expected when...                                   |
|:----------------------------|:---------------------------------------------------|
| `MalformedCommandException` | When the command is not a well-formed IRC command. |

## Parsing a source

If the goal is to only parse the source of a message, then the following parser
is able to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Parser\SourceParser;

/** @var SourceParser $parser */
$source = $parser->parseSource(':sugar');
```

The source above can be represented by the following structure:

```yaml
source:
  mask: ':sugar'
  nick: 'sugar'
  user: null
  host: null
```

Note that the `nick` property can also represent a server, rather than a user,
in case the source is meant to identify a server in server-to-server communication.

The parser cannot successfully know when `:sugar` or `:localhost` are representing
a user or a server, so no attempt is made to solve this.

The following exceptions can be produced when parsing a source:

| Exception name             | Expected when...                    |
|:---------------------------|:------------------------------------|
| `MalformedSourceException` | When the source is not well-formed. |

## Parsing tags

If the goal is to only parse a list of tags, then the following parser is able
to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Parser\TagListParser;

/** @var TagListParser $parser */
$tags = $parser->parseTags('@id=12;+irc.llm/generated;irc.twitch.tv/ban-reason=kick');
```

The tags above can be represented by the following structure:

```yaml
tags:
  - key: id
    value: '12'
    clientOnly: false
    vendor: null
  - key: +irc.llm/generated
    value: true
    clientOnly: true
    vendor: irc.llm
  - key: irc.twitch.tv/ban-reason
    value: 'kick'
    clientOnly: false
    vendor: irc.twitch.tv
```

The tag list has the following helper functions:

| Signature                        | Description                                                                                                                                                                                                                                                                                  |
|:---------------------------------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `get(string $key): string\|bool` | Get the value for a tag that matches `$key` or `false` when no tag was found.                                                                                                                                                                                                                |
| `is(string $key): bool`          | Produces `true` when a tag value matching `$key` can be found and its value is set to `true`, and `false` in all other circumstances.                                                                                                                                                        |
| `unescape(string $key): string`  | Produces the unescaped value for `$key` which would otherwise have been the escaped value when calling `get($key)`. Throws `OutOfBoundsException` when no tag matching `$key` exists. Throws `\JanMarten\IRC\Message\Exception\EmptyTagException` when the selected tag value has no length. |
| `contains(string $key): bool`    | Query whether a tag with the given key is set on the tags.                                                                                                                                                                                                                                   |

The goal of those methods is to allow tags to be accessed as if they are configuration values.

```php
use JanMarten\IRC\Message\Contract\Component\TagList;

/** @var TagList $tags */

// Conditional logic.
if ($tags->is('delayed')) {
    // ... Forward message to message queue
    return;
}

// Get information from metadata.
if ($isBanned) {
    $reason = $tags->get('irc.twitch.tv/ban-reason');
}

// Read information that could contain reserved characters.
$adminNote = $tags->unescape('irc.twitch.tv/ban-reason-comment');
```

The following exceptions can be produced when parsing a list of tags:

| Exception name              | Expected when...                      |
|:----------------------------|:--------------------------------------|
| `MalformedTagListException` | When the tag list is not well-formed. |

# Composing IRC messages

While manually creating the correct IRC message objects is a perfectly valid
approach, the `message` and `command` helper functions are available to expose
a builder pattern for composing messages.

To compose a message, use the following example:

- [Compose a message](examples/compose-message.php)

## Composing new messages

Using default values for the message builder, composing a message can be as
  simple as the following:

```php
use function \JanMarten\IRC\Message\message;

echo message()->command('PRIVMSG', '#test', 'Hello, World!');
```

This may produce the following, depending on your local environment:

```
:janmarten!janmarten@localhost PRIVMSG #test :Hello, World!
```

The automatically created source `:janmarten!janmarten@localhost` is sensitive
  to the following environment variables:

| Environment variable  | Fallback             | Description                     |
|:----------------------|:---------------------|:--------------------------------|
| `IRC_NICK`            | `get_current_user()` | The IRC nickname.               |
| `IRC_USER`            | `get_current_user()` | The IRC user.                   |
| `IRC_HOST`            | `'localhost'`        | The host of the message source. |

In that sense, the following two lines work identically:

```php
use JanMarten\IRC\Message\Component\ImmutableSource;
use function JanMarten\IRC\Message\message;

$builder1 = message();
$builder2 = message(ImmutableSource::fromEnv());
```

The `message()` function produces a message builder, which allows to register
  tags that are applicable to all commands produced using the `command()` method
  on the message builder.

```php
use function \JanMarten\IRC\Message\message;

// All commands from this builder have a `acme.org/hash-algo=sha256` tag.
$builder = message()->withAddedTag('hash-algo', 'sha256', 'acme.org');

// Prepare a message for the #test channel.
$welcome = $builder->command('PRIVMSG', '#test');

// Say hi.
echo $welcome
    ->withAddedArgument('Hello, World!')
    ->withAddedTag('id', bin2hex(random_bytes(12)), 'acme.org');

// Ping the remote party.
echo $builder->command('PING', '_');
```

Would produce something like the following:

```
@acme.org/hash-algo=sha256;acme.org/id=f369169e73df5de830b03837 :janmarten!janmarten@localhost PRIVMSG #test :Hello, World!
@acme.org/hash-algo=sha256 :janmarten!janmarten@localhost PING _
```

## Patching command messages

When patching an existing message object, the `command` helper function can be
  used to produce a builder that builds on top of the existing message information.

```php
use function JanMarten\IRC\Message\command;
use function JanMarten\IRC\Message\parse;

$originalMessage = parse('PING _');

echo command($originalMessage)
    ->withSourceFromEnv()
    ->withAddedTag('id', bin2hex(random_bytes(16)))
;
```

Would produce something like the following:

```
@id=b2156ad7835214cab67a1408e168279d :janmarten!janmarten@localhost PING _
```

# Formatting IRC message lines

When a message is ready to be converted (back) into string format, the `format`
helper can be used to format a message or a message component into a string.

```php
use function JanMarten\IRC\Message\format;
use function JanMarten\IRC\Message\parse;

$message = parse('@id=1 :foo!foo@localhost PRIVMSG #test Hello');

echo format($message);
printf("  Command: %s\r\n", format($message->command));

if ($message->source) {
    printf("  Source:  %s\r\n", format($message->source));
}

if ($message->tags) {
    printf("  Tags:    %s\r\n", format($message->tags));
}
```

Will produce:

```
@id=1 :foo!foo@localhost PRIVMSG #test Hello
  Command: PRIVMSG #test Hello
  Source:  :foo!foo@localhost
  Tags:    @id=1
```

## Formatting a message

If the goal is to specifically format complete IRC messages, use the text
  message formatter.

```php
use JanMarten\IRC\Message\Formatter\Message\TextMessageFormatter;
use function JanMarten\IRC\Message\parse;

$message = parse('@id=1 :foo!foo@localhost PRIVMSG #test Hello');

/** @var TextMessageFormatter $formatter */
echo $formatter->formatMessage($message);
```

Produces the following output:

```
@id=1 :foo!foo@localhost PRIVMSG #test Hello
```

## Formatting a command

When the goal is to format a command without the source and tags, use the
command text formatter.

```php
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use function JanMarten\IRC\Message\parse;

$message = parse('@id=1 :foo!foo@localhost PRIVMSG #test Hello');

/** @var TextCommandFormatter */
printf("%s\r\n", $formatter->formatSource($message->command));
```

Produces the following output:

```
PRIVMSG #test Hello
```

## Formatting a source

When specifically the source needs formatting, use the text source formatter.

```php
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use function JanMarten\IRC\Message\parse;

$message = parse('@id=1 :foo!foo@localhost PRIVMSG #test Hello');

/** @var TextSourceFormatter $formatter */
printf("%s\r\n", $formatter->formatSource($message->source));
```

Produces the following output:

```
:foo!foo@localhost
```

## Formatting tags

To format a tag or a list of tags, use the text tag formatter.

```php
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;
use function JanMarten\IRC\Message\parse;

$message = parse('@id=1;+janmarten.name/key=foo :foo!foo@localhost PRIVMSG #test Hello');

/** @var TextTagFormatter $formatter */
printf("%s\r\n", $formatter->formatTagList($message->tags));

foreach ($message->tags as $tag) {
    printf("  %s\r\n", $formatter->formatTag($tag));
}
```

Produces the following:

```
@id=1;+janmarten.name/key=foo
  id=1
  +janmarten.name/key=foo
```

The following exceptions can be produced when formatting a tag list:

| Exception name          | Expected when...                                   |
|:------------------------|:---------------------------------------------------|
| `EmptyTagListException` | When the tag list does not contain at least 1 tag. |
