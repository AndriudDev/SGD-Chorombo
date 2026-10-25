<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'display_name' => ucfirst($roleName)]);

        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_administracion_login_redirects_to_admin_panel(): void
    {
        $user = $this->createUserWithRole('administracion');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.index'));
    }

    public function test_non_admin_roles_login_redirect_to_dashboard(): void
    {
        foreach (['secretaria', 'utp', 'directora', 'convivencia'] as $roleName) {
            $user = $this->createUserWithRole($roleName);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard'));
        }
    }

    public function test_any_authenticated_user_can_view_dashboard(): void
    {
        $user = $this->createUserWithRole('secretaria');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bienvenido');
    }

    public function test_admin_resources_are_forbidden_for_non_admin_roles(): void
    {
        $user = $this->createUserWithRole('secretaria');

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_role_can_access_admin_resources(): void
    {
        $user = $this->createUserWithRole('administracion');

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertOk();
    }
}
