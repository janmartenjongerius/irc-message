# IRC message library

This library implements IRC message representation in PHP following:

- https://ircv3.net/irc/
- https://modern.ircdocs.horse/

It aims to be compliant with the IRCv3 specification as described in the
aforementioned documentation.

- Parse strings into message objects
- Represent messages as objects and easily access specific information
- TODO: Format objects into message strings

## Installation

As a composer package, install it as follows:

```bash
composer require janmarten/irc-message
```

## Parsing IRC message lines

Make sure that messages provided to the parser are following the IRC specification.
This means that the message length, tag list length, and even the trailing CRLF
are enforced according to the specification for IRCv3.

### Parsing a message

If the goal is to parse an entire IRC message line, use the message parser.

```php
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Parser\MessageParser;

/** @var MessageParser $parser */
$message = $parser->parseMessage(
    "@acme.org/id=1 :Johnny!john@acme.org PRIVMSG #test :Hello, fellow chatters!"
);

// The resulting message implements the Message interface.
assert($message instanceof Message);
```

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

### Parsing a command

If the goal is to only parse the command and its arguments, then the following
parser is able to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Parser\CommandParser

/** @var CommandParser $parser */
$command = $parser->parseCommand('PRIVMSG #test :Hello, world!');

// The resulting command implements the Command interface.
assert($command instanceof Command);
```

The command above can be represented by the following structure:

```yaml
command:
  verb: 'PRIVMSG'
  arguments:
    - '#test'
    - 'Hello, world!'
```

### Parsing a source

If the goal is to only parse the source of a message, then the following parser
is able to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Parser\SourceParser;

/** @var SourceParser $parser */
$source = $parser->parseSource(':sugar');

// The resulting source implements the Source interface.
assert($source instanceof Source);
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

### Parsing tags

If the goal is to only parse a list of tags, then the following parser is able
to simplify this workflow:

```php
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;

/** @var TagListParser $parser */
$tags = $parser->parseTags('@id=12;+irc.llm/generated;irc.twitch.tv/ban-reason=kick');

// The resulting tags implement the tag list interface.
assert($tags instanceof TagList);
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

| Signature                        | Description                                                                                                                                                                                                                                               |
|:---------------------------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `get(string $key): string\|bool` | Get the value for a tag that matches `$key` or `false` when no tag was found.                                                                                                                                                                             |
| `is(string $key): bool`          | Produces `true` when a tag value matching `$key` can be found and its value is set to `true`, and `false` in all other circumstances.                                                                                                                     |
| `unescape(string $key): string`  | Produces the unescaped value for `$key` which would otherwise have been the escaped value when calling `get($key)`. Throws `OutOfBoundsException` when no tag matching `$key` exists. Throws `LengthException` when the selected tag value has no length. |

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
