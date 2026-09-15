<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Notification\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

class NotificationController extends BaseController
{
    private NotificationService $service;

    public function __construct(?NotificationService $service = null)
    {
        $this->service = $service ?? new NotificationService();
    }

    /**
     * Endpoint polling feed notifikasi dan aktivitas sistem untuk Web Admin Topbar.
     */
    public function feed(): ResponseInterface
    {
        $data = $this->service->getFeed();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
