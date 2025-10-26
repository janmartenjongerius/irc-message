<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Parser;

use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Exception\MalformedTagListException;

interface TagListParser
{
    /**
     * @throws MalformedTagListException When the tag list is not well-formed.
     */
    public function parseTags(string $tags): TagList;
}
