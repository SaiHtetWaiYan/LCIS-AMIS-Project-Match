<?php

namespace App\Http\Requests;

use App\Models\AmisContract;
use App\Models\LcisProject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The pair to propose. Whether this user may propose it is decided by ProjectMatchPolicy::create.
 */
class ProposeMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lcis_project_id' => ['required', 'integer', Rule::exists(LcisProject::class, 'id')],
            'amis_contract_id' => ['required', 'integer', Rule::exists(AmisContract::class, 'id')],
        ];
    }
}
