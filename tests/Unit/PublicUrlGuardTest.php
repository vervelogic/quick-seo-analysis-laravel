<?php

namespace Tests\Unit;

use App\Services\Scanner\PublicUrlGuard;
use PHPUnit\Framework\TestCase;

class PublicUrlGuardTest extends TestCase
{
    public function test_it_allows_public_http_and_https_urls(): void
    {
        $guard = new PublicUrlGuard();

        $this->assertTrue($guard->isAllowed('https://93.184.216.34'));
        $this->assertTrue($guard->isAllowed('http://93.184.216.34'));
    }

    public function test_it_blocks_private_local_and_risky_urls(): void
    {
        $guard = new PublicUrlGuard();

        $this->assertFalse($guard->isAllowed('http://127.0.0.1'));
        $this->assertFalse($guard->isAllowed('http://10.0.0.5'));
        $this->assertFalse($guard->isAllowed('http://172.16.0.5'));
        $this->assertFalse($guard->isAllowed('http://192.168.1.10'));
        $this->assertFalse($guard->isAllowed('http://localhost'));
        $this->assertFalse($guard->isAllowed('ftp://93.184.216.34'));
        $this->assertFalse($guard->isAllowed('https://user:pass@93.184.216.34'));
        $this->assertFalse($guard->isAllowed('https://93.184.216.34:8443'));
    }
}
