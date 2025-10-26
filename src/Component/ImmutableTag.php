<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Tag;

final class ImmutableTag implements Tag
{
    public string $key {
        get {
            $key = $this->vendor !== null
                ? sprintf('%s/%s', $this->vendor, $this->keyName)
                : $this->keyName;

            return $this->clientOnly
                ? sprintf('+%s', $key)
                : $key;
        }
    }

    public function __construct(
        public readonly string      $keyName,
        public readonly string|true $value,
        public readonly bool        $clientOnly,
        public readonly ?string     $vendor
    ) {
    }
}
