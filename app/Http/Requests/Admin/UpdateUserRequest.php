<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserSide;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Leaving the password blank keeps the current one. The side is only accepted when the
 * admin may change it (not for their own account).
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->editedUser());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $canChangeSide = $this->user()->can('changeSide', $this->editedUser());

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->editedUser())],
            'side' => $canChangeSide
                ? ['required', Rule::enum(UserSide::class)]
                : ['prohibited'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'side.prohibited' => __('users.messages.cannot_change_own_side'),
        ];
    }

    private function editedUser(): User
    {
        return $this->route('user');
    }
}
