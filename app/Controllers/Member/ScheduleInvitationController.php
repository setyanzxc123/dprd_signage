<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleDocumentService;
use App\Libraries\Schedule\ScheduleResourceLinkService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleInvitationController extends BaseController
{
    public function banmus(int $id): ResponseInterface
    {
        return $this->serve(ScheduleResourceLinkService::SOURCE_BANMUS, $id);
    }

    public function general(int $id): ResponseInterface
    {
        return $this->serve(ScheduleResourceLinkService::SOURCE_GENERAL, $id);
    }

    private function serve(string $source, int $id): ResponseInterface
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth) || (int) ($auth['anggota_id'] ?? 0) < 1 || $id < 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $invitation = (new ScheduleDocumentService())->findInvitation($source, $id);
        if ($invitation === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->servePdf($invitation['path'], $invitation['download_name']);
    }

    private function servePdf(string $path, string $downloadName): ResponseInterface
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }
}
