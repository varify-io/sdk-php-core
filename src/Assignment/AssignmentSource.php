<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

enum AssignmentSource: string
{
    case Forced = 'forced';
    case Stored = 'stored';
    case Hash = 'hash';
    case Fallback = 'fallback';
}
