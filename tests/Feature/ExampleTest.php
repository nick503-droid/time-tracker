<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Prueba que la raíz redirige al login.
     */
    public function test_the_application_redirects_to_login(): void
    {
        $response = $this->get('/');

        // Ahora le decimos que ESPERE una redirección (302) hacia la ruta de login
        $response->assertRedirect(route('login'));
    }

    /**
     * Prueba que la pantalla de login sí carga correctamente (200).
     */
    public function test_the_login_page_renders_properly(): void
    {
        $response = $this->get('/login');

        // La pantalla de login sí debe devolver 200 OK
        $response->assertStatus(200);
    }
}
