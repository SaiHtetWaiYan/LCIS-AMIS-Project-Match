<?php

namespace App\Exceptions\SourceApi;

/**
 * The source rejected our credentials (HTTP 401/403), or none are configured.
 */
class SourceAuthenticationException extends SourceApiException {}
