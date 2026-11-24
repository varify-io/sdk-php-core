<?php

declare(strict_types=1);

namespace Varify\Core\Http;

use Varify\Core\VarifyError;

interface Transport
{
    /**
     * Returns the body of a 2xx response.
     *
     * @throws VarifyError with reason timeout, network or http
     */
    public function get(string $url, int $timeoutMs): string;
}
