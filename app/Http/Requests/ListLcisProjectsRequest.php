<?php

namespace App\Http\Requests;

use App\Models\LcisProject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for the LCIS projects list. An invalid filter (e.g. a hand-edited URL) sends the user back to the unfiltered list.
 */
class ListLcisProjectsRequest extends FormRequest
{
    protected $redirectRoute = 'lcis-projects.index';

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', LcisProject::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'province' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'integer', 'min:1'],
            'source' => ['nullable', Rule::in(['present', 'missing'])],
        ];
    }

    /**
     * @return array{search: string, province: ?int, type: ?int, source: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search')),
            'province' => $this->validated('province') !== null ? (int) $this->validated('province') : null,
            'type' => $this->validated('type') !== null ? (int) $this->validated('type') : null,
            'source' => $this->validated('source'),
        ];
    }
}
