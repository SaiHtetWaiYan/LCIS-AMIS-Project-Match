<?php

namespace App\Console\Commands;

use App\Enums\UserSide;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Bootstraps the first admin, since there is no public registration.
 * Prompts for anything not passed as an option; prefer the prompt for the password
 * so it does not end up in shell history.
 */
#[Signature('user:create-admin {--name= : Full name} {--email= : Login email} {--password= : Password (prompted if omitted)}')]
#[Description('Create an admin user')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $attributes = [
            'name' => $this->option('name') ?? text(label: 'Name', required: true),
            'email' => $this->option('email') ?? text(label: 'Email', required: true),
            'password' => $this->option('password') ?? password(label: 'Password', required: true),
        ];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([...$validator->validated(), 'side' => UserSide::Admin]);

        $this->components->info("Admin user [{$user->email}] created.");

        return self::SUCCESS;
    }
}
