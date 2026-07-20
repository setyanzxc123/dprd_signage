<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\JadwalModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $jadwalModel = new JadwalModel();
        $today       = date('Y-m-d');
        $monthParam  = (string) $this->request->getGet('month');
        $activeMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam) ? $monthParam : date('Y-m');
        $monthStart  = date('Y-m-01', strtotime($activeMonth . '-01'));
        $monthEnd    = date('Y-m-t', strtotime($monthStart));
        $gridStart   = date('Y-m-d', strtotime($monthStart . ' -' . ((int) date('N', strtotime($monthStart)) - 1) . ' days'));
        $gridEnd     = date('Y-m-d', strtotime($monthEnd . ' +' . (7 - (int) date('N', strtotime($monthEnd))) . ' days'));
        $selectedDate = str_starts_with($today, $activeMonth) ? $today : $monthStart;
        $prevMonth    = date('Y-m', strtotime($monthStart . ' -1 month'));
        $nextMonth    = date('Y-m', strtotime($monthStart . ' +1 month'));

        $rapatHariIni = $jadwalModel->where('tanggal', $today)->countAllResults();

        $db      = \Config\Database::connect();
        $jadwals = $db->table('jadwal j')
            ->select('j.id, j.judul, j.keterangan, j.tanggal, j.waktu_mulai, j.waktu_selesai,
                      j.status, j.materi_url, j.lokasi_lainnya,
                      r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal >=', $monthStart)
            ->where('j.tanggal <=', $monthEnd)
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_column($jadwals, 'id'));

        $meetingsByDate = [];
        foreach ($jadwals as $j) {
            $date = $j['tanggal'];
            $meetingsByDate[$date][] = [
                'id'         => $j['id'],
                'date'       => $date,
                'start'      => substr($j['waktu_mulai'], 0, 5),
                'end'        => substr($j['waktu_selesai'], 0, 5),
                'title'      => $j['judul'],
                'subtitle'   => $j['keterangan'] ?? '',
                'room'       => $this->displayLocation($j),
                'group'      => $targetMap[$j['id']] ?? '-',
                'status'     => $j['status'],
                'status_key' => $this->statusKey((string) $j['status']),
                'detail_url' => base_url("admin/jadwal/{$j['id']}/edit"),
                'edit_url'   => base_url("admin/jadwal/{$j['id']}/edit"),
            ];
        }

        $calendarDays = [];
        $cursor = $gridStart;
        while ($cursor <= $gridEnd) {
            $dayMeetings = $meetingsByDate[$cursor] ?? [];
            $statusCounts = $this->statusCounts($dayMeetings);

            $calendarDays[] = [
                'date'             => $cursor,
                'day_name'         => $this->dayName($cursor),
                'day_short'        => $this->dayName($cursor, true),
                'date_num'         => date('d', strtotime($cursor)),
                'month'            => $this->monthName($cursor, true),
                'is_today'         => $cursor === $today,
                'is_current_month' => $cursor >= $monthStart && $cursor <= $monthEnd,
                'count'            => count($dayMeetings),
                'meetings'         => $dayMeetings,
                'status_counts'    => $statusCounts,
                'summary'          => $this->summaryText($statusCounts),
                'title'            => $this->dayName($cursor) . ', ' . date('d', strtotime($cursor)) . ' ' . $this->monthName($cursor),
            ];

            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        $calendarWeeks = array_chunk($calendarDays, 7);
        $weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

        return view('admin/dashboard/index', [
            'pageTitle'     => 'Dashboard',
            'breadcrumbs'   => [],
            'stats'         => [
                'rapat_hari_ini' => $rapatHariIni,
            ],
            'meetings'      => $meetingsByDate[$selectedDate] ?? [],
            'calendarDays'  => $calendarDays,
            'calendarWeeks' => $calendarWeeks,
            'weekdayLabels' => $weekdayLabels,
            'selectedDate'  => $selectedDate,
            'monthLabel'    => $this->monthName($monthStart) . ' ' . date('Y', strtotime($monthStart)),
            'prevMonthUrl'  => base_url('admin/dashboard?month=' . $prevMonth),
            'nextMonthUrl'  => base_url('admin/dashboard?month=' . $nextMonth),
            'todayMonthUrl' => base_url('admin/dashboard'),
        ]);
    }

    private function targetNamesByJadwalIds(array $jadwalIds): array
    {
        $jadwalIds = array_values(array_filter(array_map('intval', $jadwalIds)));
        if (empty($jadwalIds)) {
            return [];
        }

        $rows = \Config\Database::connect()
            ->table('jadwal_unit_rapat jur')
            ->select('jur.jadwal_id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->whereIn('jur.jadwal_id', $jadwalIds)
            ->orderBy('ur.urutan', 'ASC')
            ->orderBy('ur.nama', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['jadwal_id']][] = $row['nama'];
        }

        return array_map(static fn (array $names): string => implode(', ', $names), $map);
    }

    private function displayLocation(array $row): string
    {
        $other = trim((string) ($row['lokasi_lainnya'] ?? ''));
        if ($other !== '') {
            return $other;
        }

        return $row['nama_ruangan'] ?? '-';
    }

    private function statusKey(string $status): string
    {
        return match ($status) {
            'selesai'     => 'done',
            'berlangsung' => 'live',
            default       => 'next',
        };
    }

    private function statusCounts(array $meetings): array
    {
        $counts = ['all' => count($meetings), 'done' => 0, 'live' => 0, 'next' => 0];

        foreach ($meetings as $meeting) {
            $key = $meeting['status_key'] ?? 'next';
            if (isset($counts[$key])) {
                $counts[$key]++;
            }
        }

        return $counts;
    }

    private function summaryText(array $counts): string
    {
        if (($counts['all'] ?? 0) === 0) {
            return 'Tidak ada agenda pada tanggal ini.';
        }

        $parts = [];
        if (($counts['done'] ?? 0) > 0) {
            $parts[] = $counts['done'] . ' agenda selesai';
        }
        if (($counts['live'] ?? 0) > 0) {
            $parts[] = $counts['live'] . ' berlangsung';
        }
        if (($counts['next'] ?? 0) > 0) {
            $parts[] = $counts['next'] . ' mendatang';
        }

        return implode(', ', $parts) . '.';
    }

    private function dayName(string $date, bool $short = false): string
    {
        $days = [
            'Sunday'    => ['Minggu', 'Min'],
            'Monday'    => ['Senin', 'Sen'],
            'Tuesday'   => ['Selasa', 'Sel'],
            'Wednesday' => ['Rabu', 'Rab'],
            'Thursday'  => ['Kamis', 'Kam'],
            'Friday'    => ['Jumat', 'Jum'],
            'Saturday'  => ['Sabtu', 'Sab'],
        ];

        $name = date('l', strtotime($date));
        return $days[$name][$short ? 1 : 0] ?? $name;
    }

    private function monthName(string $date, bool $short = false): string
    {
        $months = [
            'January'   => ['Januari', 'Jan'],
            'February'  => ['Februari', 'Feb'],
            'March'     => ['Maret', 'Mar'],
            'April'     => ['April', 'Apr'],
            'May'       => ['Mei', 'Mei'],
            'June'      => ['Juni', 'Jun'],
            'July'      => ['Juli', 'Jul'],
            'August'    => ['Agustus', 'Agu'],
            'September' => ['September', 'Sep'],
            'October'   => ['Oktober', 'Okt'],
            'November'  => ['November', 'Nov'],
            'December'  => ['Desember', 'Des'],
        ];

        $name = date('F', strtotime($date));
        return $months[$name][$short ? 1 : 0] ?? $name;
    }
}
