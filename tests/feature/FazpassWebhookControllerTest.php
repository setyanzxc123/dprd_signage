<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FazpassWebhookControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const SECRET = 'webhook-test-secret';
    private string|false $previousEnvironmentSecret;
    private mixed $previousEnvSecret;
    private mixed $previousServerSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousEnvironmentSecret = getenv('FAZPASS_CALLBACK_SECRET');
        $this->previousEnvSecret = $_ENV['FAZPASS_CALLBACK_SECRET'] ?? null;
        $this->previousServerSecret = $_SERVER['FAZPASS_CALLBACK_SECRET'] ?? null;
        putenv('FAZPASS_CALLBACK_SECRET=' . self::SECRET);
        $_ENV['FAZPASS_CALLBACK_SECRET'] = self::SECRET;
        $_SERVER['FAZPASS_CALLBACK_SECRET'] = self::SECRET;
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentSecret();
        parent::tearDown();
    }

    public function testWrongSignatureIsRejected(): void
    {
        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Fazpass-Callback-Secret' => 'wrong-secret',
        ])->withBody('{"otp_id":"otp-a","status":"sent"}')
            ->post('/webhooks/otp/fazpass');

        $response->assertStatus(401);
        $this->assertFalse($this->json($response->response()->getBody())['status']);
    }

    public function testMalformedJsonIsRejected(): void
    {
        $response = $this->withValidSignature()
            ->withBody('{invalid')
            ->post('/webhooks/otp/fazpass');

        $response->assertStatus(422);
        $this->assertFalse($this->json($response->response()->getBody())['status']);
    }

    public function testMissingIdentifiersAreRejected(): void
    {
        $response = $this->withValidSignature()
            ->withBody('{"status":"sent"}')
            ->post('/webhooks/otp/fazpass');

        $response->assertStatus(422);
        $this->assertStringContainsString('ID OTP', $this->json($response->response()->getBody())['message']);
    }

    public function testUnknownStatusIsRejectedBeforeDatabaseAccess(): void
    {
        $response = $this->withValidSignature()
            ->withBody('{"otp_id":"otp-a","status":"mystery"}')
            ->post('/webhooks/otp/fazpass');

        $response->assertStatus(422);
        $this->assertStringContainsString('Status webhook', $this->json($response->response()->getBody())['message']);
    }

    public function testNonOtpServiceIsRejectedBeforeDatabaseAccess(): void
    {
        $response = $this->withValidSignature()
            ->withBody('{"transaction_id":"tx-a","service":"notification","status":"SENT"}')
            ->post('/webhooks/otp/fazpass');

        $response->assertStatus(422);
        $this->assertStringContainsString('Service webhook', $this->json($response->response()->getBody())['message']);
    }

    private function withValidSignature(): self
    {
        return $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Fazpass-Callback-Secret' => self::SECRET,
        ]);
    }

    /** @return array<string, mixed> */
    private function json(string $body): array
    {
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function restoreEnvironmentSecret(): void
    {
        if ($this->previousEnvironmentSecret === false) {
            putenv('FAZPASS_CALLBACK_SECRET');
        } else {
            putenv('FAZPASS_CALLBACK_SECRET=' . $this->previousEnvironmentSecret);
        }

        $this->restoreSuperglobal($_ENV, $this->previousEnvSecret);
        $this->restoreSuperglobal($_SERVER, $this->previousServerSecret);
    }

    /** @param array<string, mixed> $source */
    private function restoreSuperglobal(array &$source, mixed $previous): void
    {
        if ($previous === null) {
            unset($source['FAZPASS_CALLBACK_SECRET']);

            return;
        }

        $source['FAZPASS_CALLBACK_SECRET'] = $previous;
    }
}
