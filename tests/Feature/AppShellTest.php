<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppShellTest extends TestCase
{
    public function test_the_login_page_uses_the_new_server_rendered_shell(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('Nama')
            ->assertSee('name="login"', false)
            ->assertDontSee('id="app"', false)
            ->assertDontSee('data-app-shell="dianas-mandarin-pwa"', false)
            ->assertDontSee('manifest.webmanifest', false)
            ->assertDontSee('/sw.js', false);
    }
}
