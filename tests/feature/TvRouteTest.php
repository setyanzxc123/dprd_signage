<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class TvRouteTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge  = Database::forge('tests');
        $this->forge->dropTable('settings', true);
        $this->forge->addField([
            'key_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'    => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key_name');
        $this->forge->createTable('settings');
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('settings', true);
        }

        parent::tearDown();
    }

    public function testTvRouteReturnsOk(): void
    {
        $response = $this->get('/tv');

        $response->assertOK();
        $this->assertStringContainsString('id="app"', $response->response()->getBody());
        $this->assertStringContainsString('SIGNAGE_WORKER_VERSION', $response->response()->getBody());
    }

    public function testSignageRouteRedirectsPermanentlyToTv(): void
    {
        $response = $this->get('/signage');

        $response->assertStatus(301);
        $response->assertRedirectTo(base_url('tv'));
    }

    public function testSignageRoutePreservesQueryStringWhenRedirecting(): void
    {
        $response = $this->get('/signage?tema=light');

        $response->assertStatus(301);
        $response->assertRedirectTo(base_url('tv?tema=light'));
    }
}
