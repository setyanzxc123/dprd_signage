<?php

namespace App\Libraries\Schedule;

use App\Models\AgendaUmumModel;

final class GeneralAgendaReadService
{
    /**
     * @return array{month: string, data: list<array<string, mixed>>}
     */
    public function read(array $filters, bool $includeInternal): array
    {
        $date = trim((string) ($filters['date'] ?? ''));
        $month = trim((string) ($filters['month'] ?? ''));
        $selectedMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1
            ? $month
            : date('Y-m');

        $model = new AgendaUmumModel();
        if (! $includeInternal) {
            $model->where('is_publik', 1);
        }
        if ($this->validDate($date)) {
            $model->where('tanggal', $date);
            $selectedMonth = substr($date, 0, 7);
        } else {
            $start = $selectedMonth . '-01';
            $model
                ->where('tanggal >=', $start)
                ->where('tanggal <=', date('Y-m-t', strtotime($start)));
        }

        $rows = $model
            ->orderBy('tanggal', 'ASC')
            ->orderBy('waktu_mulai', 'ASC')
            ->findAll(100);

        return [
            'month' => $selectedMonth,
            'data' => array_map(static fn (array $row): array => [
                'id'                => (int) $row['id'],
                'source'            => AgendaUmumModel::SOURCE,
                'judul'             => (string) $row['judul'],
                'kategori'          => (string) $row['kategori'],
                'pihak_eksternal'   => $row['pihak_eksternal'],
                'tanggal'           => (string) $row['tanggal'],
                'waktu_mulai'       => substr((string) $row['waktu_mulai'], 0, 5),
                'waktu_selesai'     => $row['waktu_selesai'] !== null
                    ? substr((string) $row['waktu_selesai'], 0, 5)
                    : null,
                'lokasi'            => (string) $row['lokasi'],
                'sumber_informasi'  => $row['sumber_informasi'],
                'keterangan'        => $row['keterangan'],
                'is_public'         => (int) ($row['is_publik'] ?? 0) === 1,
            ], $rows),
        ];
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }
}
