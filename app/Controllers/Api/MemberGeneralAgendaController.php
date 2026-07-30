<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\GeneralAgendaReadService;

class MemberGeneralAgendaController extends BaseController
{
    public function index()
    {
        $result = (new GeneralAgendaReadService())->read([
            'date'  => $this->request->getGet('date'),
            'month' => $this->request->getGet('month'),
        ], true);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON(['status' => 'success', ...$result]);
    }
}
