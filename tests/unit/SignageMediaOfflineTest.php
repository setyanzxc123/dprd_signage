<?php

use CodeIgniter\Test\CIUnitTestCase;

final class SignageMediaOfflineTest extends CIUnitTestCase
{
    public function testMediaIsCachedAsACompleteTransactionalResponse(): void
    {
        $source = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');

        $this->assertStringContainsString("MEDIA_CACHE_NAME = 'dprd-signage-media-v1'", $source);
        $this->assertStringContainsString("response.status !== 200", $source);
        $this->assertStringContainsString("headers.get('Content-Length')", $source);
        $this->assertStringContainsString('await cache.put(url, response)', $source);
        $this->assertStringContainsString("status: 'ready'", $source);
        $this->assertStringContainsString('cached_at: new Date().toISOString()', $source);

        $manifestWrite = strpos($source, 'await cache.put(MEDIA_MANIFEST_URL');
        $oldMediaCleanup = strpos($source, 'const keep = new Set');
        $this->assertIsInt($manifestWrite);
        $this->assertIsInt($oldMediaCleanup);
        $this->assertLessThan($oldMediaCleanup, $manifestWrite);
    }

    public function testRangeRequestsOnlyUseACompleteCachedResponse(): void
    {
        $source = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');

        $this->assertStringContainsString("from 'workbox-range-requests'", $source);
        $this->assertStringContainsString("request.headers.has('Range')", $source);
        $this->assertStringContainsString('createPartialResponse(request, cached)', $source);
        $this->assertStringNotContainsString('cache.put(request, response)', $source);
    }

    public function testBrowserRequestsPersistentStorageAndShowsReadiness(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString('crossorigin="anonymous"', $source);
        $this->assertStringContainsString('navigator.storage.persist()', $source);
        $this->assertStringContainsString('navigator.storage.estimate()', $source);
        $this->assertStringContainsString("type: 'CACHE_ACTIVE_MEDIA'", $source);
        $this->assertStringContainsString("data.type !== 'SIGNAGE_MEDIA_STATUS'", $source);
        $this->assertStringContainsString('Media siap offline', $source);
        $this->assertStringContainsString('Penyimpanan tidak cukup', $source);
        $this->assertStringContainsString('Menggunakan data tersimpan', $source);
        $this->assertStringContainsString('Sinkron terakhir', $source);
    }

    public function testCompiledWorkerContainsWorkboxRangeHandling(): void
    {
        $compiled = (string) file_get_contents(FCPATH . 'signage-sw.js');

        $this->assertStringContainsString('dprd-signage-media-v1', $compiled);
        $this->assertStringContainsString('Range Not Satisfiable', $compiled);
        $this->assertStringContainsString('/uploads/media/', $compiled);
    }
}
