<?php

declare(strict_types=1);

namespace Varify\Core;

enum VarifyErrorReason: string
{
    case Timeout = 'timeout';
    case Network = 'network';
    case Http = 'http';
    case InvalidConfig = 'invalid-config';
    case Cache = 'cache';
}
