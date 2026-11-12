<?php

namespace App\Exceptions\SourceApi;

use App\Enums\SyncSource;
use RuntimeException;
use Throwable;

/**
 * A call to the LCIS or AMIS API failed. errorCode is the source's own code (for example
 * API_KEY_EXPIRED) when it sent one, otherwise HTTP_<status> or a code of ours.
 */
class SourceApiException extends RuntimeException
{
    public function __construct(
        public readonly SyncSource $source,
        public readonly string $errorCode,
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $detail = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'source' => $this->source->value,
            'error_code' => $this->errorCode,
            'http_status' => $this->httpStatus,
            'detail' => $this->detail,
        ];
    }
}
