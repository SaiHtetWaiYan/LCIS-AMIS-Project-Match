<?php

namespace Tests\Feature\Admin;

use App\Enums\UserSide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserSide}>
     */
    public static function nonAdminSides(): array
    {
        return [
            'lcis' => [UserSide::Lcis],
            'amis' => [UserSide::Amis],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    #[DataProvider('nonAdminSides')]
    public function test_forbids_non_admin_users_from_the_user_list(UserSide $side): void
    {
        $user = User::factory()->create(['side' => $side]);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_sees_all_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Person']);
        User::factory()->lcis()->create(['name' => 'Lcis Person']);
        User::factory()->amis()->deactivated()->create(['name' => 'Amis Person']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSeeText(['Admin Person', 'Lcis Person', 'Amis Person']);
    }

    public function test_filters_users_by_side_status_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->amis()->create(['name' => 'Active Amis']);
        User::factory()->amis()->deactivated()->create(['name' => 'Deactivated Amis']);
        User::factory()->lcis()->deactivated()->create(['name' => 'Deactivated Lcis']);

        $response = $this->actingAs($admin)->get('/admin/users?side=amis&status=deactivated&search=amis');

        $response->assertOk();
        $response->assertSeeText('Deactivated Amis');
        $response->assertDontSeeText(['Active Amis', 'Deactivated Lcis']);
    }

    public function test_escapes_user_names_in_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => '<script>alert("x")</script>']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_admin_creates_a_user_with_a_side_and_hashed_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Amis User',
            'email' => 'new.amis@example.com',
            'side' => 'amis',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'User New Amis User created.');
        $user = User::firstWhere('email', 'new.amis@example.com');
        $this->assertSame(UserSide::Amis, $user->side);
        $this->assertTrue($user->isActive());
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_store_requires_name_email_side_and_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/users', []);

        $response->assertSessionHasErrors(['name', 'email', 'side', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_rejects_an_email_that_is_already_used(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'side' => 'lcis',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'The email has already been taken.']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_store_rejects_an_unknown_side(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'side' => 'superuser',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertSessionHasErrors(['side' => 'The selected side is invalid.']);
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
    }

    #[DataProvider('nonAdminSides')]
    public function test_forbids_non_admin_users_from_creating_users(UserSide $side): void
    {
        $user = User::factory()->create(['side' => $side]);

        $response = $this->actingAs($user)->post('/admin/users', [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.com',
            'side' => 'admin',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_admin_updates_details_and_side_keeping_the_password_when_left_blank(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->lcis()->create();
        $originalPasswordHash = $user->password;

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Renamed',
            'email' => 'renamed@example.com',
            'side' => 'amis',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertSame('renamed@example.com', $user->email);
        $this->assertSame(UserSide::Amis, $user->side);
        $this->assertSame($originalPasswordHash, $user->password);
    }

    public function test_admin_resets_a_users_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'side' => 'lcis',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $user->refresh()->password));
    }

    public function test_admin_cannot_change_their_own_side(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'side' => 'lcis',
        ]);

        $response->assertSessionHasErrors(['side' => 'You cannot change the side of your own account.']);
        $this->assertSame(UserSide::Admin, $admin->refresh()->side);
    }

    public function test_admin_can_update_their_own_name_without_sending_a_side(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => 'Renamed Admin',
            'email' => $admin->email,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed Admin', $admin->refresh()->name);
        $this->assertSame(UserSide::Admin, $admin->side);
    }

    public function test_edit_form_hides_the_side_field_for_the_admins_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get("/admin/users/{$admin->id}/edit");

        $response->assertOk();
        $response->assertSeeText('You cannot change the side of your own account.');
        $response->assertDontSee('name="side"', false);
    }
}
