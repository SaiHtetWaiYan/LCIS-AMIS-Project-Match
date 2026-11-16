<?php

namespace App\Http\Requests;

use App\Models\AmisContract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for the AMIS contracts list. An invalid filter (e.g. a hand-edited URL) sends the user back to the unfiltered list.
 */
class ListAmisContractsRequest extends FormRequest
{
    protected $redirectRoute = 'amis-contracts.index';

    /**
     * AMIS contract types and statuses as stored (status keeps the AMIS spelling "nomal").
     */
    public const CONTRACT_TYPES = ['concession', 'rental'];

    public const STATUSES = ['nomal', 'cancel', 'end'];

    /**
     * Minimum best-score filter options, in percent.
     */
    public const MIN_SCORES = [50, 75];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', AmisContract::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'province' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', Rule::in(self::CONTRACT_TYPES)],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'source' => ['nullable', Rule::in(['present', 'missing'])],
            'match' => ['nullable', Rule::in(['unmatched', 'proposed', 'matched'])],
            'min_score' => ['nullable', 'integer', Rule::in(self::MIN_SCORES)],
            'sort' => ['nullable', Rule::in(['latest', 'score'])],
        ];
    }

    /**
     * @return array{search: string, province: ?int, type: ?string, status: ?string, source: ?string, match: ?string, min_score: ?int, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search')),
            'province' => $this->validated('province') !== null ? (int) $this->validated('province') : null,
            'type' => $this->validated('type'),
            'status' => $this->validated('status'),
            'source' => $this->validated('source'),
            'match' => $this->validated('match'),
            'min_score' => $this->validated('min_score') !== null ? (int) $this->validated('min_score') : null,
            'sort' => $this->validated('sort') ?? 'latest',
        ];
    }
}
