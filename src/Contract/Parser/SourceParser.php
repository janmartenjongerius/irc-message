<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Parser;

use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Exception\MalformedSourceException;

interface SourceParser
{
    /**
     * @throws MalformedSourceException When the source is not well-formed.
     */
    public function parseSource(string $source): Source;
}
