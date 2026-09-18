<?php

namespace App\Filters;

use App\Libraries\Notification\NotificationService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Menghapus mikro-cache feed notifikasi setelah setiap request mutasi
 * agar polling berikutnya menghitung ulang alert dari kondisi terbaru,
 * bukan menunggu TTL cache habis.
 */
class NotificationCacheFlushFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        NotificationService::flushFeedCache();
    }
}
