<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleInvitationStorage;
use App\Models\JadwalBanmusModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleInvitationController extends BaseController
{
    public function banmus(int $id): ResponseInterface
    {
        return $this->serve('jadwal_banmus', $id);
    }

    public function general(int $id): ResponseInterface
    {
        return $this->serve('jadwal_umum', $id);
    }

    private function serve(string $table, int $id): ResponseInterface
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth) || (int) ($auth['anggota_id'] ?? 0) < 1 || $id < 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $builder = db_connect()->table($table)
            ->select('undangan_file, undangan_nama_asli')
            ->where('id', $id)
            ->where('undangan_file IS NOT NULL', null, false)
            ->where('undangan_file !=', '');
        if ($table === 'jadwal_banmus') {
            $builder->where('jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
                ->whereIn('status', JadwalBanmusModel::SCHEDULED_STATUSES)
                ->where('deleted_at', null);
        }
        $row = $builder->get(1)->getRowArray();
        if ($row === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = (new ScheduleInvitationStorage())->path($row['undangan_file'] ?? null);
        if ($path === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw PageNotFoundException::forPageNotFound();
        }
        $downloadName = basename((string) ($row['undangan_nama_asli'] ?: 'undangan-rapat.pdf'));
        $downloadName = str_replace(['"', "\r", "\n"], '', $downloadName);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }
}
