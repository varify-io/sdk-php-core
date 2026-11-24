<?php

declare(strict_types=1);

namespace Varify\Core\Http;

use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;

use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function is_string;

use const CURLE_OPERATION_TIMEDOUT;
use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_NOSIGNAL;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_REDIR_PROTOCOLS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT_MS;
use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;

final class CurlTransport implements Transport
{
    public function get(string $url, int $timeoutMs): string
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new VarifyError('Loading the Varify config failed', VarifyErrorReason::Network);
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            // Without it, libcurl's default resolver ignores timeouts below one second.
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($handle);
        $errno = curl_errno($handle);

        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new VarifyError(
                "Loading the Varify config took longer than {$timeoutMs} ms",
                VarifyErrorReason::Timeout,
            );
        }

        if ($errno !== 0 || !is_string($body)) {
            throw new VarifyError(
                'Loading the Varify config failed: ' . curl_error($handle),
                VarifyErrorReason::Network,
            );
        }

        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($status < 200 || $status >= 300) {
            throw new VarifyError("Loading the Varify config failed with HTTP {$status}", VarifyErrorReason::Http);
        }

        return $body;
    }
}
