<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Parser\Tag;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;
use JanMarten\IRC\Message\Exception\MalformedTagListException;

/**
 * @see https://modern.ircdocs.horse/#message-format
 * @see https://ircv3.net/specs/extensions/message-tags
 */
final readonly class PCRETagListParser implements TagListParser
{
    const string TAG_LIST_EXPRESSION = '/^@(?P<tags>\S+)$/';
    const string TAG_EXPRESSION = <<<PCRE
    ~^
      # Optional +client prefix
      (?:(?P<client_prefix>[+]))?
      
      # Optional vendor/
      (?:(?P<vendor>[^/]+)/)?
      
      # Key name
      (?P<key_name>[[:alnum:]-]+)
      
      # Optional escaped value
      # According to the spec, the escaped value should not contain:
      #  - NUL        0x00
      #  - CR         0x0D  \r
      #  - LF         0x0A  \n
      #  - Semicolon  0x3B  ;
      #  - Space      0x20
      (?:=(?P<escaped_value>[^\x00\r\n; ]+)?)?$
    ~x
    PCRE;
    const string VENDOR_EXPRESSION = <<<PCRE
    ~^
      # Total length ≤ 253 characters (max FQDN length per RFC 1035)
      (?=.{1,253}$)
        
      (?:
        # Each label starts with an alpha character.
        [A-Za-z]
            
        # Middle of label may have hyphens, but not at start or end.
        (?:
            [A-Za-z0-9-]{0,61}
            [A-Za-z0-9]
        )?

      # Labels separated by dot.
      \.)+

      # Final TLD is alphabetic, 2–63 chars (e.g. com, network, etc.)
      [A-Za-z]{2,63}$
    ~x
    PCRE;


    /**
     * @throws MalformedTagListException When the tag list is not well-formed.
     */
    public function parseTags(string $tags): TagList
    {
        if (!preg_match(self::TAG_LIST_EXPRESSION, $tags, $matches)) {
            throw new MalformedTagListException(
                'Tag list is not a well-formed IRC message tag list.'
            );
        }

        $tags = [];

        foreach (explode(';', $matches['tags']) as $tagLine) {
            if (!preg_match(self::TAG_EXPRESSION, $tagLine, $matches)) {
                throw new MalformedTagListException(
                    'Tag list is not a well-formed IRC message tag list.'
                );
            }

            if (strlen($matches['vendor']) > 0 && !self::isValidVendor($matches['vendor'])) {
                throw new MalformedTagListException(
                    'Tag list is not a well-formed IRC message tag list.'
                );
            }

            $tags[] = new ImmutableTag(
                keyName: $matches['key_name'],
                value: $matches['escaped_value'] ?? true,
                clientOnly: !empty($matches['client_prefix']),
                vendor: $matches['vendor'] ?: null
            );
        }

        return ImmutableTagList::createNormalizedTagList(...$tags);
    }

    private static function isValidVendor(string $vendor): bool
    {
        return preg_match(self::VENDOR_EXPRESSION, $vendor) === 1;
    }
}
