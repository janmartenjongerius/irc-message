<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Tag;

use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Formatter\TagFormatter;
use JanMarten\IRC\Message\Contract\Formatter\TagListFormatter;
use JanMarten\IRC\Message\Exception\EmptyTagException;
use JanMarten\IRC\Message\Exception\EmptyTagListException;

final readonly class TextTagFormatter implements TagListFormatter, TagFormatter
{
    public function formatTagList(TagList $tagList): string
    {
        if (count($tagList) === 0) {
            throw new EmptyTagListException('Cannot format an empty tag list.');
        }

        $tags = [];

        foreach ($tagList as $tag) {
            $tags[] = $this->formatTag($tag);
        }

        return sprintf('@%s', implode(';', $tags));
    }

    public static function escapeValue(string $value): string
    {
        return strtr(
            $value,
            [
                ';' =>  '\:',
                ' ' => '\s',
                '\\' => '\\\\',
                "\r" => '\r',
                "\n" => '\n',
            ]
        );
    }

    public function formatTag(Tag $tag): string
    {
        $result = $tag->key;

        if (is_string($tag->value)) {
            if (strlen($tag->value) === 0) {
                throw new EmptyTagException('Cannot format an empty tag.', $tag);
            }

            $result .= sprintf('=%s', self::escapeValue($tag->value));
        }

        return $result;
    }
}
