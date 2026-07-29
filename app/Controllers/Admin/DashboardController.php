<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;

class DashboardController extends BaseController
{
    public function index(): string
    {
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

        $repository = new DatabaseScheduleReadRepository();
        $rapatHariIni = count($repository->findSchedules(false, $today, null, null));
        $jadwals = $repository->findSchedules(false, null, $activeMonth, null);
        $unitMap = $repository->findUnitsByScheduleIds(array_column($jadwals, 'id'));

        $meetingsByDate = [];
        foreach ($jadwals as $j) {
            $date = $j['tanggal'];
            $sourceId = (int) ($j['source_id'] ?? $j['id']);
            $isBanmus = ($j['source'] ?? 'jadwal') === 'banmus';
            $detailUrl = $isBanmus
                ? base_url('admin/jadwal-banmus/' . (int) $j['dokumen_banmus_id'])
                : base_url("admin/jadwal/{$sourceId}/edit");
            $meetingsByDate[$date][] = [
                'id'         => $j['id'],
                'date'       => $date,
                'start'      => substr($j['waktu_mulai'], 0, 5),
                'end'        => substr($j['waktu_selesai'], 0, 5),
                'title'      => $j['judul'],
                'subtitle'   => $j['keterangan'] ?? '',
                'room'       => $this->displayLocation($j),
                'group'      => isset($unitMap[(int) $j['id']])
                    ? implode(', ', array_column($unitMap[(int) $j['id']], 'nama'))
                    : '-',
                'status'     => $j['status'],
                'status_key' => $this->statusKey((string) $j['status']),
                'detail_url' => $detailUrl,
                'edit_url'   => $detailUrl,
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
