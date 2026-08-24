<?php

namespace Tests\Unit\Services\Payments;

use App\Models\Payment;
use App\Services\Payments\PaytechGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaytechGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.paytech.base_url' => 'https://paytech.sn',
            'payments.paytech.api_key' => 'test-api-key',
            'payments.paytech.api_secret' => 'test-api-secret',
            'payments.paytech.env' => 'test',
            'payments.paytech.ipn_url' => 'https://example.com/api/webhooks/paytech',
        ]);
    }

    public function test_initiate_checkout_sends_expected_request_and_returns_redirect_url(): void
    {
        Http::fake([
            'paytech.sn/api/payment/request-payment' => Http::response([
                'success' => 1,
                'token' => 'abc123',
                'redirect_url' => 'https://paytech.sn/payment/checkout/abc123',
            ]),
        ]);

        $payment = Payment::factory()->create(['provider' => Payment::PROVIDER_PAYTECH]);

        $result = (new PaytechGateway)->initiateCheckout($payment);

        $this->assertSame('https://paytech.sn/payment/checkout/abc123', $result->checkoutUrl);
        $this->assertSame($payment->reference, $result->providerReference);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($payment) {
            return $request->url() === 'https://paytech.sn/api/payment/request-payment'
                && $request->hasHeader('API_KEY', 'test-api-key')
                && $request->hasHeader('API_SECRET', 'test-api-secret')
                && $request['ref_command'] === $payment->reference
                && $request['item_price'] === (string) $payment->amount
                && $request['env'] === 'test';
        });
    }

    public function test_initiate_checkout_throws_when_provider_returns_no_token(): void
    {
        Http::fake([
            'paytech.sn/api/payment/request-payment' => Http::response(['success' => -1, 'message' => 'Invalid API key'], 400),
        ]);

        $payment = Payment::factory()->create(['provider' => Payment::PROVIDER_PAYTECH]);

        $this->expectException(RuntimeException::class);

        (new PaytechGateway)->initiateCheckout($payment);
    }

    public function test_webhook_signature_is_verified_via_sha256_of_credentials(): void
    {
        $gateway = new PaytechGateway;

        $valid = Request::create('/api/webhooks/paytech', 'POST', [
            'ref_command' => 'SM-TEST',
            'type_event' => 'sale_complete',
            'api_key_sha256' => hash('sha256', 'test-api-key'),
            'api_secret_sha256' => hash('sha256', 'test-api-secret'),
        ]);

        $invalid = Request::create('/api/webhooks/paytech', 'POST', [
            'ref_command' => 'SM-TEST',
            'type_event' => 'sale_complete',
            'api_key_sha256' => 'wrong',
            'api_secret_sha256' => 'wrong',
        ]);

        $this->assertTrue($gateway->verifyWebhookSignature($valid));
        $this->assertFalse($gateway->verifyWebhookSignature($invalid));
    }

    public function test_parse_webhook_maps_reference_and_success_state(): void
    {
        $gateway = new PaytechGateway;

        $success = Request::create('/api/webhooks/paytech', 'POST', [
            'ref_command' => 'SM-TEST',
            'type_event' => 'sale_complete',
        ]);
        $failure = Request::create('/api/webhooks/paytech', 'POST', [
            'ref_command' => 'SM-TEST',
            'type_event' => 'sale_canceled',
        ]);

        $this->assertTrue($gateway->parseWebhook($success)->succeeded);
        $this->assertFalse($gateway->parseWebhook($failure)->succeeded);
        $this->assertSame('SM-TEST', $gateway->parseWebhook($success)->providerReference);
    }
}
