<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_shows_the_users_details_and_password_form(): void
    {
        $user = User::factory()->amis()->create(['name' => 'Somchai', 'email' => 'somchai@example.com']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSeeText(['Somchai', 'somchai@example.com', 'AMIS']);
        $response->assertSee(route('password.update'));
    }

    public function test_users_cannot_change_their_own_name_or_email(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($user)->patch('/profile', ['name' => 'Changed', 'email' => 'changed@example.com']);

        $response->assertMethodNotAllowed();
        $this->assertSame('Original', $user->refresh()->name);
    }

    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $response->assertMethodNotAllowed();
        $this->assertModelExists($user);
    }
}
