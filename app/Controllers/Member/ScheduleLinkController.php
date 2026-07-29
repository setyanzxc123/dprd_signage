<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

class ScheduleLinkController extends BaseController
{
    public function live(int $id): RedirectResponse
    {
        return $this->redirectToScheduleUrl($id, 'stream_url');
    }

    public function berkas(int $id): RedirectResponse
    {
        return $this->redirectToScheduleUrl($id, 'materi_url');
    }

    public function liveBanmus(int $id): RedirectResponse
    {
        return $this->redirectToBanmusUrl($id, 'stream_url');
    }

    public function berkasBanmus(int $id): RedirectResponse
    {
        return $this->redirectToBanmusUrl($id, 'materi_url');
    }

    private function redirectToScheduleUrl(int $id, string $column): RedirectResponse
    {
        if ($id < 1 || ! in_array($column, ['stream_url', 'materi_url'], true)) {
            return redirect()->to(base_url('agenda'), 303);
        }

        $row = db_connect()
            ->table('jadwal')
            ->select($column)
            ->where('id', $id)
            ->where($column . ' !=', '')
            ->where($column . ' IS NOT NULL', null, false)
            ->limit(1)
            ->get()
            ->getRowArray();
        $url = trim((string) ($row[$column] ?? ''));

        if ($url === '') {
            return redirect()->to(base_url('agenda'), 303);
        }

        return redirect()->to($url);
    }

    private function redirectToBanmusUrl(int $id, string $column): RedirectResponse
    {
        if ($id < 1 || ! in_array($column, ['stream_url', 'materi_url'], true)) {
            return redirect()->to(base_url('agenda'), 303);
        }

        $row = db_connect()
            ->table('jadwal_banmus')
            ->select($column)
            ->where('id', $id)
            ->whereIn('status', ['fixed', 'selesai'])
            ->where('deleted_at', null)
            ->where($column . ' !=', '')
            ->where($column . ' IS NOT NULL', null, false)
            ->limit(1)
            ->get()
            ->getRowArray();
        $url = trim((string) ($row[$column] ?? ''));

        return $url === ''
            ? redirect()->to(base_url('agenda'), 303)
            : redirect()->to($url);
    }
}
