<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Source;

use JanMarten\IRC\Message\Contract\Component\Source;

trait CreatesSourceMask
{
    public static function createSourceMask(Source $source): string
    {
        $mask = sprintf(':%s', $source->nick);

        if ($source->user !== null) {
            $mask .= sprintf('!%s', $source->user);
        }

        if ($source->host !== null) {
            $mask .= sprintf('@%s', $source->host);
        }

        return $mask;
    }
}
