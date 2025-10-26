<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Source;

final class ImmutableSource implements Source
{
    public function __construct(
        public string $nick,
        public ?string $user,
        public ?string $host
    ) {
    }

    public string $mask {
        get {
            $mask = sprintf(':%s', $this->nick);

            if ($this->user !== null) {
                $mask .= sprintf('!%s', $this->user);
            }

            if ($this->host !== null) {
                $mask .= sprintf('@%s', $this->host);
            }

            return $mask;
        }
    }
}
