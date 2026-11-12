<?php

namespace App\Exceptions\SourceApi;

/**
 * The requested record does not exist at the source (HTTP 404).
 */
class SourceNotFoundException extends SourceApiException {}
