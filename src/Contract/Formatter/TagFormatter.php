<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Formatter;

use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Exception\EmptyTagException;

interface TagFormatter
{
    /**
     * @throws EmptyTagException When the value of the tag is a zero length string.
     */
    public function formatTag(Tag $tag): string;
}
