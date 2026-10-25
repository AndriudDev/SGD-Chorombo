<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    public function test_register_routes_are_removed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@ejemplo.cl',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_login_page_does_not_offer_registration(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Registrarse')
            ->assertDontSee('/register');
    }

    public function test_guest_cannot_access_user_creation(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/users/create')->assertRedirect(route('login'));
    }
}
