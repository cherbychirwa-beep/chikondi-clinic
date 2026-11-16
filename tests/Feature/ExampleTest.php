<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Replaces Laravel's default example test (the root URL now redirects guests to the login page). */
class ExampleTest extends TestCase
{
    public function test_guests_are_redirected_from_the_root_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_loads(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Staff sign in');
    }
}
