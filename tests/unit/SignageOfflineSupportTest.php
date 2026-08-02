<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\ContentSecurityPolicy;

final class SignageOfflineSupportTest extends CIUnitTestCase
{
    public function testBrowserApiRecoveryUsesTimeoutRetryAndSnapshots(): void
    {
        $source = file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('fetchJsonWithRetry', $source);
        $this->assertStringContainsString('new AbortController()', $source);
        $this->assertStringContainsString('[1000, 3000]', $source);
        $this->assertStringContainsString('dataRequestInFlight', $source);
        $this->assertStringContainsString('weatherRequestInFlight', $source);
        $this->assertStringContainsString('localStorage.setItem', $source);
        $this->assertStringContainsString('SNAPSHOT_MAX_AGE_MS', $source);
        $this->assertStringContainsString('freshnessAt', $source);
        $this->assertStringContainsString('data.cached_at_epoch', $source);
        $this->assertStringContainsString(': data.cached_at', $source);
        $this->assertStringContainsString('Date.now() - freshnessAt > SNAPSHOT_MAX_AGE_MS', $source);
        $this->assertStringContainsString('readSnapshot', $source);
        $this->assertMatchesRegularExpression(
            '/function handleNetworkOnline\(\).*?loadData\(\);.*?loadCuaca\(\);/s',
            $source,
        );
    }

    public function testServiceWorkerCachesOnlyTheAppShell(): void
    {
        $source = file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');

        $this->assertIsString($source);
        $this->assertStringContainsString("'/signage'", $source);
        $this->assertStringContainsString("'/assets/css/signage.css'", $source);
        $this->assertStringContainsString("'/assets/vendor/vue/vue.global.prod.js'", $source);
        $this->assertStringContainsString("'/assets/vendor/qrcodejs/qrcode.min.js'", $source);
        $this->assertStringContainsString("'/assets/images/logo_dprd.jpg'", $source);
        $this->assertStringContainsString("request.mode === 'navigate'", $source);
        $this->assertStringContainsString('ignoreSearch: true', $source);
        $this->assertStringContainsString('caches.delete(key)', $source);
        $this->assertStringContainsString("data.type === 'ACTIVATE_UPDATE'", $source);
        $this->assertStringContainsString('self.skipWaiting()', $source);
        $this->assertStringNotContainsString('/api/signage/', $source);
    }

    public function testEveryPrecachedStaticAssetExists(): void
    {
        $source = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');
        preg_match_all("#'(/assets/[^']+)'#", $source, $matches);

        $this->assertNotEmpty($matches[1]);
        foreach (array_unique($matches[1]) as $asset) {
            $this->assertFileExists(FCPATH . ltrim($asset, '/'), $asset);
        }
    }

    public function testCspAllowsOnlySameOriginWorkers(): void
    {
        $this->assertSame('self', (new ContentSecurityPolicy())->workerSrc);
    }

    public function testBmkgWeatherIconHasARestrictedOfflineCache(): void
    {
        $worker = (string) file_get_contents(ROOTPATH . 'resources/js/signage-sw.js');
        $view = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString("WEATHER_ICON_CACHE_NAME = 'dprd-signage-weather-icons-v1'", $worker);
        $this->assertStringContainsString("BMKG_ICON_ORIGIN = 'https://api-apps.bmkg.go.id'", $worker);
        $this->assertStringContainsString("BMKG_ICON_PATH_PREFIX = '/storage/icon/cuaca/'", $worker);
        $this->assertStringContainsString("data.type === 'CACHE_WEATHER_ICON'", $worker);
        $this->assertStringContainsString('isBmkgWeatherIcon(url)', $worker);
        $this->assertStringContainsString("type: 'CACHE_WEATHER_ICON'", $view);
        $this->assertStringContainsString('weatherIconOfflineStatus', $view);
    }

    public function testWorkerUpdateWaitsForAPlaybackBoundaryBeforeReload(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertStringContainsString('loopedToStart', $view);
        $this->assertStringContainsString("activateWaitingServiceWorker('batas loop video')", $view);
        $this->assertStringContainsString("type: 'ACTIVATE_UPDATE'", $view);
        $this->assertStringContainsString("addEventListener('controllerchange'", $view);
        $this->assertStringContainsString('workerUpdateActivationRequested', $view);
    }
}
