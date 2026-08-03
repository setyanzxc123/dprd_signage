<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleResourceLinkService;
use CodeIgniter\HTTP\RedirectResponse;

class RedirectController extends BaseController
{
    public function liveBanmus(int $id): RedirectResponse|string
    {
        $url = (new ScheduleResourceLinkService())->publicUrl(
            ScheduleResourceLinkService::SOURCE_BANMUS,
            $id,
            'stream',
        );

        return $url
            ? redirect()->to($url)
            : $this->noActive('Siaran Langsung', 'Siaran langsung untuk rapat ini belum tersedia.');
    }

    public function berkasBanmus(int $id): RedirectResponse|string
    {
        $url = (new ScheduleResourceLinkService())->publicUrl(
            ScheduleResourceLinkService::SOURCE_BANMUS,
            $id,
            'materi',
        );

        return $url
            ? redirect()->to($url)
            : $this->noActive('Berkas Rapat', 'Berkas untuk rapat ini belum tersedia.');
    }

    public function liveGeneral(int $id): RedirectResponse|string
    {
        $url = (new ScheduleResourceLinkService())->publicUrl(
            ScheduleResourceLinkService::SOURCE_GENERAL,
            $id,
            'stream',
        );

        return $url
            ? redirect()->to($url)
            : $this->noActive('Siaran Langsung', 'Siaran langsung untuk agenda ini belum tersedia.');
    }

    public function berkasGeneral(int $id): RedirectResponse|string
    {
        $url = (new ScheduleResourceLinkService())->publicUrl(
            ScheduleResourceLinkService::SOURCE_GENERAL,
            $id,
            'materi',
        );

        return $url
            ? redirect()->to($url)
            : $this->noActive('Berkas Agenda', 'Berkas untuk agenda ini belum tersedia.');
    }

    private function noActive(string $judul, string $pesan): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$judul} — DPRD Sulawesi Tengah</title>
            <style>
                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #0f172a;
                    font-family: system-ui, sans-serif;
                    color: #e2e8f0;
                    text-align: center;
                    padding: 2rem;
                }
                .card {
                    max-width: 420px;
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 1rem;
                    padding: 2.5rem 2rem;
                }
                .icon { font-size: 3rem; margin-bottom: 1rem; }
                h1 { font-size: 1.25rem; margin: 0 0 .75rem; color: #f1f5f9; }
                p  { font-size: .9rem; color: #94a3b8; margin: 0; line-height: 1.6; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">📭</div>
                <h1>{$judul}</h1>
                <p>{$pesan}</p>
            </div>
        </body>
        </html>
        HTML;
    }
}
