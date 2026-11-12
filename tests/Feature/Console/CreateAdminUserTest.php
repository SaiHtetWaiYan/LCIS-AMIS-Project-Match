<?php

namespace Tests\Feature\Console;

use App\Enums\UserSide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_active_admin_from_options(): void
    {
        $this->artisan('user:create-admin', [
            '--name' => 'First Admin',
            '--email' => 'admin@example.com',
            '--password' => 'secret-password',
        ])->assertSuccessful();

        $admin = User::firstWhere('email', 'admin@example.com');
        $this->assertSame(UserSide::Admin, $admin->side);
        $this->assertTrue($admin->isActive());
        $this->assertTrue(Hash::check('secret-password', $admin->password));
    }

    public function test_prompts_for_missing_values(): void
    {
        $this->artisan('user:create-admin')
            ->expectsQuestion('Name', 'Prompted Admin')
            ->expectsQuestion('Email', 'prompted@example.com')
            ->expectsQuestion('Password', 'secret-password')
            ->assertSuccessful();

        $this->assertSame(UserSide::Admin, User::firstWhere('email', 'prompted@example.com')->side);
    }

    public function test_fails_when_the_email_is_already_used(): void
    {
        User::factory()->lcis()->create(['email' => 'taken@example.com']);

        $this->artisan('user:create-admin', [
            '--name' => 'Duplicate',
            '--email' => 'taken@example.com',
            '--password' => 'secret-password',
        ])
            ->expectsOutputToContain('The email has already been taken.')
            ->assertFailed();

        $this->assertSame(UserSide::Lcis, User::firstWhere('email', 'taken@example.com')->side);
    }

    public function test_fails_when_the_password_is_too_short(): void
    {
        $this->artisan('user:create-admin', [
            '--name' => 'Weak',
            '--email' => 'weak@example.com',
            '--password' => 'short',
        ])
            ->expectsOutputToContain('The password field must be at least 8 characters.')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }
}
