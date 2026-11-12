<?php

namespace App\Services\SourceApi;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\SourceApiException;
use App\Exceptions\SourceApi\SourceAuthenticationException;
use App\Exceptions\SourceApi\SourceNotFoundException;
use App\Exceptions\SourceApi\SourceRateLimitedException;
use App\Exceptions\SourceApi\SourceResponseException;
use App\Exceptions\SourceApi\SourceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * Shared GET handling for the read-only source APIs: timeouts, spacing between requests,
 * retries with backoff for connection failures, 429 and 5xx, and typed exceptions.
 */
abstract class SourceApiClient
{
    /**
     * Seconds to wait before retry n (1-based) after a connection failure or 5xx.
     *
     * @var list<int>
     */
    protected const BACKOFF_SECONDS = [2, 5, 15];

    /**
     * Upper bound for a Retry-After header, so a bad header cannot stall a sync.
     */
    protected const MAX_RETRY_AFTER_SECONDS = 60;

    private ?float $lastRequestAt = null;

    /**
     * @param  array{base_url: string, timeout: int, connect_timeout: int, max_attempts: int, min_request_interval_ms: int, page_size: int}  $config
     */
    public function __construct(protected readonly array $config) {}

    abstract public function source(): SyncSource;

    /**
     * The request with authentication applied. Throws when no credential is configured.
     */
    abstract protected function authenticatedRequest(PendingRequest $request): PendingRequest;

    /**
     * @return array{code: ?string, message: ?string, detail: ?string}
     */
    abstract protected function errorDetails(Response $response): array;

    public function pageSize(): int
    {
        return $this->config['page_size'];
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = [], bool $authenticated = true): array
    {
        $maxAttempts = max(1, $this->config['max_attempts']);

        for ($attempt = 1; ; $attempt++) {
            $this->waitForRequestSlot();

            try {
                $request = $this->baseRequest();
                $response = ($authenticated ? $this->authenticatedRequest($request) : $request)->get($path, $query);
            } catch (ConnectionException $exception) {
                if ($attempt < $maxAttempts) {
                    Sleep::for($this->backoffSeconds($attempt))->seconds();

                    continue;
                }

                throw new SourceUnavailableException(
                    $this->source(), 'CONNECTION_FAILED', "Could not connect to {$this->source()->label()}: {$exception->getMessage()}", previous: $exception,
                );
            }

            if ($response->successful()) {
                return $this->decode($response);
            }

            if ($attempt < $maxAttempts && $response->status() === 429) {
                Sleep::for($this->retryAfterSeconds($response, $attempt))->seconds();

                continue;
            }

            if ($attempt < $maxAttempts && $response->serverError()) {
                Sleep::for($this->backoffSeconds($attempt))->seconds();

                continue;
            }

            throw $this->exceptionFor($response);
        }
    }

    protected function missingCredential(string $setting): SourceAuthenticationException
    {
        return new SourceAuthenticationException(
            $this->source(), 'CREDENTIAL_MISSING', "No {$this->source()->label()} API credential is configured ({$setting}).",
        );
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->config['base_url'], '/'))
            ->acceptJson()
            ->connectTimeout($this->config['connect_timeout'])
            ->timeout($this->config['timeout']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            throw new SourceResponseException(
                $this->source(), 'INVALID_JSON', "{$this->source()->label()} returned a response that is not a JSON object.", $response->status(),
            );
        }

        return $json;
    }

    private function exceptionFor(Response $response): SourceApiException
    {
        $status = $response->status();
        $error = $this->errorDetails($response);
        $code = $error['code'] ?? "HTTP_{$status}";
        $message = $error['message'] ?? "{$this->source()->label()} returned HTTP {$status}.";

        $class = match (true) {
            in_array($status, [401, 403], true) => SourceAuthenticationException::class,
            $status === 404 => SourceNotFoundException::class,
            $status === 429 => SourceRateLimitedException::class,
            $response->serverError() => SourceUnavailableException::class,
            default => SourceResponseException::class,
        };

        return new $class($this->source(), $code, $message, $status, $error['detail']);
    }

    /**
     * Spaces requests so a full sync stays under the source's rate limit.
     */
    private function waitForRequestSlot(): void
    {
        $intervalMs = $this->config['min_request_interval_ms'];

        if ($intervalMs > 0 && $this->lastRequestAt !== null) {
            $elapsedMs = (int) ((microtime(true) - $this->lastRequestAt) * 1000);

            if ($elapsedMs < $intervalMs) {
                Sleep::for($intervalMs - $elapsedMs)->milliseconds();
            }
        }

        $this->lastRequestAt = microtime(true);
    }

    private function backoffSeconds(int $attempt): int
    {
        return static::BACKOFF_SECONDS[min($attempt, count(static::BACKOFF_SECONDS)) - 1];
    }

    private function retryAfterSeconds(Response $response, int $attempt): int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_numeric($retryAfter)) {
            return min(max((int) $retryAfter, 1), static::MAX_RETRY_AFTER_SECONDS);
        }

        return $this->backoffSeconds($attempt);
    }
}
