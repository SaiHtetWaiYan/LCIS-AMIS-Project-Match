<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lcis_and_amis_users_cannot_manage_users(): void
    {
        $target = User::factory()->deactivated()->create();

        foreach ([User::factory()->lcis()->create(), User::factory()->amis()->create()] as $user) {
            $this->assertFalse($user->can('viewAny', User::class), "{$user->side->value} viewAny");
            $this->assertFalse($user->can('create', User::class), "{$user->side->value} create");
            $this->assertFalse($user->can('update', $target), "{$user->side->value} update");
            $this->assertFalse($user->can('changeSide', $target), "{$user->side->value} changeSide");
            $this->assertFalse($user->can('reactivate', $target), "{$user->side->value} reactivate");
        }
    }

    public function test_admin_can_manage_other_users(): void
    {
        $admin = User::factory()->admin()->create();
        $activeUser = User::factory()->create();
        $deactivatedUser = User::factory()->deactivated()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $activeUser));
        $this->assertTrue($admin->can('changeSide', $activeUser));
        $this->assertTrue($admin->can('deactivate', $activeUser));
        $this->assertTrue($admin->can('reactivate', $deactivatedUser));
    }

    public function test_deactivate_and_reactivate_only_apply_to_the_matching_state(): void
    {
        $admin = User::factory()->admin()->create();
        $activeUser = User::factory()->create();
        $deactivatedUser = User::factory()->deactivated()->create();

        $this->assertFalse($admin->can('deactivate', $deactivatedUser));
        $this->assertFalse($admin->can('reactivate', $activeUser));
    }

    public function test_admin_cannot_deactivate_or_change_the_side_of_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('update', $admin));
        $this->assertFalse($admin->can('changeSide', $admin));
        $this->assertFalse($admin->can('deactivate', $admin));
    }
}
