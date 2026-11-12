<?php

namespace App\Exceptions\SourceApi;

/**
 * The source could not be reached, or kept failing with HTTP 5xx after all retries.
 */
class SourceUnavailableException extends SourceApiException {}
