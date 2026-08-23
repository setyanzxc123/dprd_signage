<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Schedule\AgendaWorkspaceService;
use App\Models\JadwalBanmusModel;

/**
 * Kalender agenda terpadu untuk aplikasi mobile admin — membungkus
 * AgendaWorkspaceService yang sama dengan halaman web admin, sehingga
 * gabungan sumber, penandaan konflik, dan aturan filter identik.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 */
class AdminAgendaController extends BaseController
{
    use ApiResponse;

    private const FILTER_KEYS = ['source', 'unit', 'lokasi', 'status', 'publikasi'];

    public function index()
    {
        (new JadwalBanmusModel())->autoUpdateStatuses();

        $month = trim((string) $this->request->getGet('month'));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            $month = date('Y-m');
        }

        $filters = [];
        foreach (self::FILTER_KEYS as $filter) {
            $filters[$filter] = trim((string) $this->request->getGet($filter));
        }

        $workspace = (new AgendaWorkspaceService())->loadMonth($month, $filters);

        return $this->apiSuccess([
            'month'   => $month,
            'filters' => $filters,
            'counts'  => $workspace['counts'],
            'options' => $workspace['options'],
            'data'    => array_map([$this, 'serializeAgenda'], $workspace['agendas']),
        ]);
    }

    /**
     * Field edit_url adalah kebutuhan presentasi web admin — navigasi
     * mobile dibangun dari source/source_id, jadi tidak diteruskan.
     *
     * @param array<string, mixed> $agenda
     * @return array<string, mixed>
     */
    private function serializeAgenda(array $agenda): array
    {
        unset($agenda['edit_url']);

        return $agenda;
    }
}
