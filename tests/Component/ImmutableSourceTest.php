<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Component;

use JanMarten\IRC\Message\Component\ImmutableSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImmutableSource::class)]
final class ImmutableSourceTest extends TestCase
{
    #[RequiresEnvironmentVariable('IRC_NICK')]
    #[RequiresEnvironmentVariable('IRC_USER')]
    #[RequiresEnvironmentVariable('IRC_HOST')]
    public function testFromEnvironmentVariables(): void
    {
        self::assertSame(
            ':test!Test@janmarten.name',
            ImmutableSource::fromEnv()->mask
        );
    }
}
