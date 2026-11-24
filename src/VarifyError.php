<?php

declare(strict_types=1);

namespace Varify\Core;

use RuntimeException;
use Throwable;

final class VarifyError extends RuntimeException
{
    public function __construct(string $message, public readonly VarifyErrorReason $reason, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
