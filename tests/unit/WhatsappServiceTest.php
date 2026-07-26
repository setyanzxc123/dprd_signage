<?php

use App\Libraries\WhatsappService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class WhatsappServiceTest extends CIUnitTestCase
{
    /**
     * @dataProvider phoneNormalizationProvider
     */
    public function testNormalizesIndonesianPhoneNumbers(string $input, string $expected): void
    {
        $this->assertSame($expected, WhatsappService::normalizePhone($input));
    }

    public static function phoneNormalizationProvider(): array
    {
        return [
            'local prefix'       => ['0812-3456-7890', '6281234567890'],
            'without zero'       => ['812 3456 7890', '6281234567890'],
            'international plus' => ['+62 812-3456-7890', '6281234567890'],
            'normalized'         => ['6281234567890', '6281234567890'],
        ];
    }

    public function testRejectsSendWhenTokenIsMissing(): void
    {
        $service = new WhatsappService('', 'https://example.test/send', 'https://example.test/device');

        $result = $service->send('081234567890', 'Kode OTP: 123456');

        $this->assertFalse($result['success']);
        $this->assertSame('Layanan WhatsApp belum dikonfigurasi.', $result['error']);
    }

    public function testRejectsInvalidPhoneWithoutCallingProvider(): void
    {
        $service = new WhatsappService('test-token', 'https://example.test/send', 'https://example.test/device');

        $result = $service->send('123', 'Kode OTP: 123456');

        $this->assertFalse($result['success']);
        $this->assertSame('Nomor WhatsApp tujuan tidak valid.', $result['error']);
    }

    public function testRejectsEmptyMessageWithoutCallingProvider(): void
    {
        $service = new WhatsappService('test-token', 'https://example.test/send', 'https://example.test/device');

        $result = $service->send('081234567890', '   ');

        $this->assertFalse($result['success']);
        $this->assertSame('Pesan WhatsApp tidak boleh kosong.', $result['error']);
    }
}
