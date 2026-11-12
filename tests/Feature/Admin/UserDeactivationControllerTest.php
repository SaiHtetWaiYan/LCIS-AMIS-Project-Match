<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeactivationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_deactivates_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Somchai']);

        $response = $this->actingAs($admin)->from('/admin/users')->post("/admin/users/{$user->id}/deactivation");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('status', 'User Somchai deactivated. They can no longer log in.');
        $this->assertFalse($user->refresh()->isActive());
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/deactivation");

        $response->assertForbidden();
        $this->assertTrue($admin->refresh()->isActive());
    }

    public function test_admin_reactivates_a_deactivated_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->deactivated()->create();

        $response = $this->actingAs($admin)->from('/admin/users')->delete("/admin/users/{$user->id}/deactivation");

        $response->assertRedirect('/admin/users');
        $this->assertTrue($user->refresh()->isActive());
    }

    public function test_forbids_non_admin_users_from_deactivating_others(): void
    {
        $lcisUser = User::factory()->lcis()->create();
        $amisUser = User::factory()->amis()->create();

        $response = $this->actingAs($lcisUser)->post("/admin/users/{$amisUser->id}/deactivation");

        $response->assertForbidden();
        $this->assertTrue($amisUser->refresh()->isActive());
    }
}
