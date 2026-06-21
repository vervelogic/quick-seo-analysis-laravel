<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_rejects_private_network_targets(): void
    {
        $response = $this->from('/')->post('/scan', [
            'url' => 'http://127.0.0.1',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('url');
    }

    public function test_scan_rejects_custom_ports(): void
    {
        $response = $this->from('/')->post('/scan', [
            'url' => 'https://93.184.216.34:8443',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('url');
    }
}
