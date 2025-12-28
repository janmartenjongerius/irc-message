<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Formatter;

use JanMarten\IRC\Message\Contract\Component\Source;

interface SourceFormatter
{
    public function formatSource(Source $source): string;
}
