<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Formatter;

use JanMarten\IRC\Message\Contract\Component\Message;

interface MessageFormatter
{
    public function formatMessage(Message $message): string;
}
