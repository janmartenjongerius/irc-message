<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Formatter\Source\CreatesSourceMask;

final class ImmutableSource implements Source
{
    use CreatesSourceMask;

    public function __construct(
        public string $nick,
        public ?string $user,
        public ?string $host
    ) {
    }

    public string $mask {
        get {
            return self::createSourceMask($this);
        }
    }
}
