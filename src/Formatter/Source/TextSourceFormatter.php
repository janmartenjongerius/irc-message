<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Source;

use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Formatter\SourceFormatter;

final readonly class TextSourceFormatter implements SourceFormatter
{
    use CreatesSourceMask;

    public function formatSource(Source $source): string
    {
        return self::createSourceMask($source);
    }
}
