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

    /**
     * Produces an instance of itself using the environment.
     * Environment variables can be used to influence the produced source.
     *
     *     property: nick
     *     variable: IRC_NICK
     *     fallback: get_current_user()
     *
     *     property: user
     *     variable: IRC_USER
     *     fallback: get_current_user()
     *
     *     property: host
     *     variable: IRC_HOST
     *     fallback: 'localhost'
     */
    public static function fromEnv(): self
    {
        return new self(
            nick: getenv('IRC_NICK') ?: get_current_user(),
            user: getenv('IRC_USER') ?: get_current_user(),
            host: getenv('IRC_HOST') ?: 'localhost',
        );
    }
}
