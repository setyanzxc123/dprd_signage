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

        $stagedManifestWrite = strpos($source, 'await cache.put(MEDIA_STAGED_MANIFEST_URL');
        $manifestWrite = strpos($source, 'await cache.put(MEDIA_MANIFEST_URL');
        $oldMediaCleanup = strpos($source, 'const keep = new Set');
        $this->assertIsInt($stagedManifestWrite);
        $this->assertIsInt($manifestWrite);
        $this->assertIsInt($oldMediaCleanup);
        $this->assertLessThan($manifestWrite, $stagedManifestWrite);
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

    public function testBrowserRequestsPersistentStorageAndKeepsReadinessInternal(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString('crossorigin="anonymous"', $source);
        $this->assertStringContainsString('navigator.storage.persist()', $source);
        $this->assertStringContainsString('navigator.storage.estimate()', $source);
        $this->assertStringContainsString("type: 'CACHE_ACTIVE_MEDIA'", $source);
        $this->assertStringContainsString("data.type !== 'SIGNAGE_MEDIA_STATUS'", $source);
        $this->assertStringContainsString('dataset.connectionStatus', $source);
        $this->assertStringContainsString('dataset.mediaOfflineStatus', $source);
        $this->assertStringContainsString('dataset.lastSyncAt', $source);
        $this->assertStringContainsString('dataset.mediaRecoveryCount', $source);
        $this->assertStringContainsString('dataset.mediaBufferedSeconds', $source);
        $this->assertStringContainsString('dataset.mediaLastError', $source);
        $this->assertStringNotContainsString('connectionBadgeClasses()', $source);
        $this->assertStringNotContainsString('mediaOfflineBadgeClasses()', $source);
        $this->assertStringNotContainsString('Sinkron terakhir', $source);
    }

    public function testMediaDownloadWaitsForStablePlaybackAndActivatesAtBoundary(): void
    {
        $worker = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');
        $view = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString("MEDIA_STAGED_MANIFEST_URL = '/__signage_media_staged_manifest__'", $worker);
        $this->assertStringContainsString('MEDIA_PROTOCOL_VERSION = 2', $worker);
        $this->assertStringContainsString("priority: 'low'", $worker);
        $this->assertStringContainsString("postMediaStatus(client, 'staged'", $worker);
        $this->assertStringContainsString("data.type === 'ACTIVATE_STAGED_MEDIA'", $worker);
        $this->assertStringContainsString('MEDIA_CACHE_MIN_STABLE_MS', $view);
        $this->assertStringContainsString('MEDIA_CACHE_MIN_BUFFER_SECONDS', $view);
        $this->assertStringContainsString('bufferedMediaSeconds(video)', $view);
        $this->assertStringContainsString('mediaWorkerProtocolVersion < 2', $view);
        $this->assertStringContainsString('|| waitingServiceWorker', $view);
        $this->assertStringContainsString("type: 'ACTIVATE_STAGED_MEDIA'", $view);
        $this->assertStringContainsString('@ended="handleMediaEnded"', $view);
        $this->assertStringContainsString("activateStagedMedia('batas loop video')", $view);
    }

    public function testStagedMediaIsValidatedCommittedOrRolledBack(): void
    {
        $worker = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');
        $view = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString('MEDIA_PREVIOUS_MANIFEST_URL', $worker);
        $this->assertStringContainsString("data.type === 'REJECT_STAGED_MEDIA'", $worker);
        $this->assertStringContainsString("data.type === 'COMMIT_ACTIVE_MEDIA'", $worker);
        $this->assertStringContainsString("data.type === 'ROLLBACK_ACTIVE_MEDIA'", $worker);
        $this->assertStringContainsString('readPreviousMediaManifest', $worker);
        $this->assertStringContainsString('validateStagedVideo', $view);
        $this->assertStringContainsString('probe.onloadedmetadata', $view);
        $this->assertStringContainsString('validateStagedImage', $view);
        $this->assertStringContainsString('MEDIA_COMMIT_STABLE_MS', $view);
        $this->assertStringContainsString("type: 'ROLLBACK_ACTIVE_MEDIA'", $view);
    }

    public function testOperationalDiagnosticsStayLocalAndHidden(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString("DIAGNOSTICS_KEY = 'dprd-signage:diagnostics:v1'", $source);
        $this->assertStringContainsString('persistSignageDiagnostics', $source);
        $this->assertStringContainsString('mediaLastRecoveryReason', $source);
        $this->assertStringContainsString('storageUsageBytes', $source);
        $this->assertStringNotContainsString('Sinkron terakhir', $source);
        $this->assertStringNotContainsString('mediaOfflineBadgeClasses()', $source);
    }

    public function testCompiledWorkerContainsWorkboxRangeHandling(): void
    {
        $compiled = (string) file_get_contents(FCPATH . 'signage-sw.js');

        $this->assertStringContainsString('dprd-signage-media-v1', $compiled);
        $this->assertStringContainsString('Range Not Satisfiable', $compiled);
        $this->assertStringContainsString('/uploads/media/', $compiled);
        $this->assertStringContainsString('/__signage_media_staged_manifest__', $compiled);
        $this->assertStringContainsString('/__signage_media_previous_manifest__', $compiled);
        $this->assertStringContainsString('ROLLBACK_ACTIVE_MEDIA', $compiled);
    }
}
