<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_unknown_url_returns_a_branded_404(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertNotFound();
        $response->assertSee('Page not found');
        $response->assertSee('WedFlow Atelier');
    }
}
