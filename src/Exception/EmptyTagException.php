<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Exception;

use JanMarten\IRC\Message\Contract\Component\Tag;

final class EmptyTagException extends \LengthException
{
    public function __construct(string $message, public readonly Tag $tag)
    {
        parent::__construct($message);
    }
}
