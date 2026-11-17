<?php

namespace Tests\Feature;

use Tests\TestCase;

class Fase2RequisitosActualizadosTest extends TestCase
{
    /**
     * The root URL redirects guests to the login page (public registration removed).
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
