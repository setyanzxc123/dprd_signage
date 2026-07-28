<?php

namespace Tests\Feature;

use App\Models\BanmusDocumentModel;
use App\Models\BanmusProjectionModel;
use CodeIgniter\Test\CIUnitTestCase;

class BanmusPhase2Test extends CIUnitTestCase
{
    public function testAutoStatusResolution(): void
    {
        $model = new BanmusProjectionModel();

        // 1. Tanggal kosong -> Proyeksi
        $statusProyeksi = $model->resolveStatus([
            'agenda'  => 'Tes Rapat LKPJ',
            'tanggal' => '',
            'status'  => 'proyeksi',
        ]);
        $this->assertSame('proyeksi', $statusProyeksi);

        // 2. Tanggal diisi -> Fixed
        $statusFixed = $model->resolveStatus([
            'agenda'  => 'Tes Rapat LKPJ',
            'tanggal' => '2026-07-29',
            'status'  => 'proyeksi',
        ]);
        $this->assertSame('fixed', $statusFixed);

        // 3. Status khusus (selesai/ditunda/dibatalkan) -> Dipertahankan
        $statusSelesai = $model->resolveStatus([
            'agenda'  => 'Tes Rapat LKPJ',
            'tanggal' => '2026-07-29',
            'status'  => 'selesai',
        ]);
        $this->assertSame('selesai', $statusSelesai);
    }
}
