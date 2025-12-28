<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Formatter;

use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Exception\EmptyTagListException;

interface TagListFormatter
{
    /**
     * @throws EmptyTagListException When the given tag list does not contain at least one tag.
     */
    public function formatTagList(TagList $tagList): string;
}
