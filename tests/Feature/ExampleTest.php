<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_guest_is_redirected_from_main_to_login(): void
    {
        $response = $this->get('/main');

        $response->assertRedirect('/login');
    }
}