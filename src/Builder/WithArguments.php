<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Builder;

trait WithArguments
{
    private array $arguments;

    public function withAddedArgument(string $argument): static
    {
        $this->arguments[] = $argument;
        return $this;
    }

    public function withArguments(string ...$arguments): static
    {
        $this->arguments = $arguments;
        return $this;
    }
}
