<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;

class PortalController extends BaseController
{
    public function index()
    {
        return redirect()->to(base_url('agenda'), 302);
    }
}
