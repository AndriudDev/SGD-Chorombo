<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CacheControlHeadersTest extends TestCase
{
    use RefreshDatabase;

    private const CACHE_CONTROL = 'must-revalidate, no-cache, no-store, private';

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'display_name' => ucfirst($roleName)]);

        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_login_page_forbids_browser_cache(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Cache-Control', self::CACHE_CONTROL);
    }

    public function test_authenticated_pages_forbid_browser_cache(): void
    {
        $user = $this->createUserWithRole('secretaria');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('Cache-Control', self::CACHE_CONTROL);
    }

    public function test_logout_redirect_forbids_browser_cache(): void
    {
        $user = $this->createUserWithRole('secretaria');

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'))
            ->assertHeader('Cache-Control', self::CACHE_CONTROL);
    }
}
