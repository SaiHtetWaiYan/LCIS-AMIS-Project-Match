<?php

namespace App\Http\Requests;

use App\Enums\MatchStatus;
use App\Models\ProjectMatch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for the matches list and its CSV export. An invalid filter (e.g. a hand-edited URL)
 * sends the user back to the unfiltered list.
 */
class ListMatchesRequest extends FormRequest
{
    protected $redirectRoute = 'matches.index';

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', ProjectMatch::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::enum(MatchStatus::class)],
            'review' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{search: string, status: ?MatchStatus, needs_review: bool}
     */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search')),
            'status' => MatchStatus::tryFrom((string) $this->validated('status')),
            'needs_review' => $this->boolean('review'),
        ];
    }
}
