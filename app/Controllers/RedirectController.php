<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

class RedirectController extends BaseController
{
    /**
     * GET /go/jadwal/{id}/live
     * Redirect ke stream_url dari jadwal publik tertentu.
     * Jika tidak ada → tampilkan halaman info singkat.
     */
    public function live(int $id): RedirectResponse|string
    {
        $url = $this->_getScheduleUrl($id, 'stream_url');

        if ($url) {
            return redirect()->to($url);
        }

        return $this->_noActive('Siaran Langsung', 'Siaran langsung untuk rapat ini belum tersedia.');
    }

    /**
     * GET /go/jadwal/{id}/berkas
     * Redirect ke materi_url dari jadwal publik tertentu.
     * Jika tidak ada → tampilkan halaman info singkat.
     */
    public function berkas(int $id): RedirectResponse|string
    {
        $url = $this->_getScheduleUrl($id, 'materi_url');

        if ($url) {
            return redirect()->to($url);
        }

        return $this->_noActive('Berkas Rapat', 'Berkas untuk rapat ini belum tersedia.');
    }

    // ── Private Helpers ──────────────────────────────────────────────────

    /**
     * Ambil nilai kolom URL dari jadwal publik tertentu.
     *
     * @param  string      $column  'stream_url' atau 'materi_url'
     * @return string|null          URL jika ada, null jika tidak
     */
    private function _getScheduleUrl(int $id, string $column): ?string
    {
        if (! in_array($column, ['stream_url', 'materi_url'], true)) {
            return null;
        }

        $db = \Config\Database::connect();

        $row = $db->table('jadwal')
            ->select($column)
            ->where('id', $id)
            ->where('is_publik', 1)
            ->where($column . ' !=', '')
            ->whereNotNull($column)
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row[$column] ?? null;
    }

    /**
     * Tampilkan halaman sederhana jika URL belum tersedia.
     */
    private function _noActive(string $judul, string $pesan): string
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
