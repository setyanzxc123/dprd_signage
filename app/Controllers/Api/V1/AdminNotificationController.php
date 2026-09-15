<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Notification\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Endpoint feed notifikasi terpadu untuk aplikasi mobile admin.
 * Menyediakan ringkasan status darurat, alert sistem, dan proses AI.
 */
class AdminNotificationController extends BaseController
{
    use ApiResponse;

    private NotificationService $service;

    public function __construct(?NotificationService $service = null)
    {
        $this->service = $service ?? new NotificationService();
    }

    public function index(): ResponseInterface
    {
        $feed = $this->service->getFeed();

        return $this->apiSuccess(['data' => $feed]);
    }
}
