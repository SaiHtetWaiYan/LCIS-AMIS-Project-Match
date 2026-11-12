<?php

namespace App\Exceptions\SourceApi;

use InvalidArgumentException;

/**
 * A single source record cannot be read (for example it has no id). The sync skips it and counts it.
 */
class InvalidSourceRecordException extends InvalidArgumentException {}
