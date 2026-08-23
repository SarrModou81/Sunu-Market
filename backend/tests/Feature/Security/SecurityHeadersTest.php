<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_security_headers(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_global_api_rate_limit_headers_are_present(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '120');
    }
}
