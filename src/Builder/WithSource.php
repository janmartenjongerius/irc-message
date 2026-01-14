<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Builder;

use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Contract\Component\Source;

trait WithSource
{
    private ?Source $source;

    public function withSourceFromEnv(): static
    {
        return $this->withSource(ImmutableSource::fromEnv());
    }

    public function withSource(Source $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function withoutSource(): static
    {
        $this->source = null;
        return $this;
    }
}
