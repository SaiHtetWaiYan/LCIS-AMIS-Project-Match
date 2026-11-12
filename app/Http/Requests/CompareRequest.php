<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The pair to compare: an LCIS project id and an AMIS contract id. Access to the records is
 * authorised in the controller.
 */
class CompareRequest extends FormRequest
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
            'lcis' => ['required', 'integer', 'exists:lcis_projects,id'],
            'amis' => ['required', 'integer', 'exists:amis_contracts,id'],
        ];
    }
}
