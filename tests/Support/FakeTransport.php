<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Support;

use Varify\Core\Http\Transport;
use Varify\Core\VarifyError;

final class FakeTransport implements Transport
{
    public int $calls = 0;

    private function __construct(
        private readonly string $body,
        private readonly ?VarifyError $error,
    ) {}

    public static function responding(string $body): self
    {
        return new self($body, null);
    }

    public static function failing(VarifyError $error): self
    {
        return new self('', $error);
    }

    public function get(string $url, int $timeoutMs): string
    {
        ++$this->calls;
        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->body;
    }
}
