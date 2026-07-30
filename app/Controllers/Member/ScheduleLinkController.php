<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleResourceLinkService;
use CodeIgniter\HTTP\RedirectResponse;

class ScheduleLinkController extends BaseController
{
    public function live(int $id): RedirectResponse
    {
        return $this->redirectToResource(
            ScheduleResourceLinkService::SOURCE_INSIDENTAL,
            $id,
            'stream',
        );
    }

    public function berkas(int $id): RedirectResponse
    {
        return $this->redirectToResource(
            ScheduleResourceLinkService::SOURCE_INSIDENTAL,
            $id,
            'materi',
        );
    }

    public function liveBanmus(int $id): RedirectResponse
    {
        return $this->redirectToResource(
            ScheduleResourceLinkService::SOURCE_BANMUS,
            $id,
            'stream',
        );
    }

    public function berkasBanmus(int $id): RedirectResponse
    {
        return $this->redirectToResource(
            ScheduleResourceLinkService::SOURCE_BANMUS,
            $id,
            'materi',
        );
    }

    private function redirectToResource(
        string $source,
        int $id,
        string $resource,
    ): RedirectResponse
    {
        $auth = session()->get('member_auth');
        $memberId = is_array($auth) ? (int) ($auth['anggota_id'] ?? 0) : 0;
        $url = (new ScheduleResourceLinkService())->memberUrl(
            $source,
            $id,
            $resource,
            $memberId,
        );

        return $url === null
            ? redirect()->to(base_url('agenda'), 303)
            : redirect()->to($url);
    }
}
