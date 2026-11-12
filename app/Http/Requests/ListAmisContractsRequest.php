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
        ];
    }

    /**
     * @return array{search: string, province: ?int, type: ?string, status: ?string, source: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search')),
            'province' => $this->validated('province') !== null ? (int) $this->validated('province') : null,
            'type' => $this->validated('type'),
            'status' => $this->validated('status'),
            'source' => $this->validated('source'),
        ];
    }
}
