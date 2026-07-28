<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class AgendaWorkspaceController extends BaseController
{
    public function kalender(): string
    {
        return view('admin/agenda_workspace/kalender', [
            'pageTitle' => 'Kalender Seluruh Agenda',
        ]);
    }

    public function laporan(): string
    {
        return view('admin/agenda_workspace/laporan', [
            'pageTitle' => 'Laporan Agenda',
        ]);
    }
}
