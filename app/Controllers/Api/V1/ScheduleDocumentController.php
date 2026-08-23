<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Schedule\ScheduleDocumentService;
use App\Libraries\Schedule\ScheduleResourceLinkService;
use App\Models\BanmusDocumentModel;

/**
 * Download dokumen terautentikasi untuk aplikasi mobile: undangan
 * rapat (anggota; banmus wajib rapat + terjadwal) dan dokumen SK
 * banmus (is_publik atau anggota). Aturan akses identik versi web
 * karena menempuh ScheduleDocumentService yang sama.
 */
class ScheduleDocumentController extends BaseController
{
    use ApiResponse;

    private const SOURCE_MAP = [
        'banmus'      => ScheduleResourceLinkService::SOURCE_BANMUS,
        'jadwal-umum' => ScheduleResourceLinkService::SOURCE_GENERAL,
    ];

    public function undangan(string $source, int $id)
    {
        $mappedSource = self::SOURCE_MAP[$source] ?? null;

        if ($mappedSource === null) {
            return $this->apiError('Sumber jadwal tidak dikenali.', 404);
        }

        $invitation = (new ScheduleDocumentService())->findInvitation($mappedSource, $id);

        if ($invitation === null) {
            return $this->apiError('Undangan tidak tersedia.', 404);
        }

        return $this->servePdf($invitation['path'], $invitation['download_name']);
    }

    public function sk(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);

        if ($document === null) {
            return $this->apiError('Dokumen tidak ditemukan.', 404);
        }

        $isMember = service('requestIdentity')->currentAnggota() !== null;
        if ((int) ($document['is_publik'] ?? 0) !== 1 && ! $isMember) {
            return $this->apiForbidden();
        }

        $resolved = (new ScheduleDocumentService())->resolveSkDocument($document);
        if ($resolved === null) {
            return $this->apiError('Dokumen tidak tersedia.', 404);
        }

        if (isset($resolved['url'])) {
            return redirect()->to($resolved['url']);
        }

        return $this->servePdf($resolved['path'], $resolved['download_name']);
    }

    private function servePdf(string $path, string $downloadName)
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return $this->apiError('Dokumen tidak dapat dibaca.', 404);
        }

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }
}
