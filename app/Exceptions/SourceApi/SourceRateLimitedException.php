<?php

namespace App\Exceptions\SourceApi;

/**
 * The source kept answering HTTP 429 after all retries.
 */
class SourceRateLimitedException extends SourceApiException {}
