<?php

namespace App\Services\Matches;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * A match action that is not allowed right now. The message is meant for the user and is shown
 * as an error on the page they came from.
 */
class MatchWorkflowException extends RuntimeException implements ShouldntReport
{
    public function render(): RedirectResponse
    {
        return back()->with('error', $this->getMessage());
    }
}
