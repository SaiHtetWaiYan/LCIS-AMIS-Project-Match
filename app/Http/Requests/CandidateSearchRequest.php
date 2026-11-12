<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The manual search of the other system on a project or contract page. Access to the page
 * itself is authorised in the controller.
 */
class CandidateSearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function searchTerm(): string
    {
        return trim((string) $this->validated('q'));
    }
}
