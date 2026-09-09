<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $today       = date('Y-m-d');
        $monthParam  = (string) $this->request->getGet('month');
        $activeMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam) ? $monthParam : date('Y-m');
        $monthStart  = date('Y-m-01', strtotime($activeMonth . '-01'));
        $prevMonth   = date('Y-m', strtotime($monthStart . ' -1 month'));
        $nextMonth   = date('Y-m', strtotime($monthStart . ' +1 month'));

        $repository   = new DatabaseScheduleReadRepository();
        $rapatHariIni = count($repository->findSchedules(false, $today, null, null));
        $jadwals      = $repository->findSchedules(false, null, $activeMonth, null);

        foreach ($jadwals as &$jadwal) {
            $jadwal['status'] = $this->resolveStatus($jadwal);
        }
        unset($jadwal);

        $unitMap = $repository->findUnitsByScheduleIds(array_column($jadwals, 'id'));
        $sedangBerlangsung = count(array_filter(
            $jadwals,
            static fn (array $j): bool => ($j['status'] ?? '') === 'berlangsung'
        ));
        $agendaMendatang = count(array_filter(
            $jadwals,
            static fn (array $j): bool => in_array(
                $j['status'] ?? '',
                ['menunggu', 'persiapan'],
                true
            )
        ));

        $enrichedJadwals = [];
        $meetingsByDate  = [];

        foreach ($jadwals as $j) {
            $date      = $j['tanggal'];
            $sourceId  = (int) ($j['source_id'] ?? $j['id']);
            $isBanmus  = ($j['source'] ?? '') === 'banmus';
            $detailUrl = $isBanmus
                ? base_url('admin/jadwal-banmus/' . (int) ($j['dokumen_banmus_id'] ?? 0))
                : base_url("admin/jadwal-umum/{$sourceId}/edit");

            $startStr  = empty($j['waktu_mulai']) ? null : substr((string) $j['waktu_mulai'], 0, 5);
            $endStr    = empty($j['waktu_selesai']) ? null : substr((string) $j['waktu_selesai'], 0, 5);
            $timeRange = 'Kegiatan harian';
            if ($startStr && $endStr) {
                $timeRange = "{$startStr}–{$endStr} WITA";
            } elseif ($startStr) {
                $timeRange = "{$startStr} WITA";
            } elseif (!empty($j['tanggal_selesai']) && $j['tanggal_selesai'] !== $j['tanggal']) {
                $timeRange = 'Rentang hari';
            }

            $badge = status_badge($j['status']);
            $badgeClass = match ($j['status']) {
                'berlangsung' => 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30',
                'persiapan'   => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30',
                'menunggu'    => 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20',
                'selesai'     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                'dibatalkan'  => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20 line-through',
                'ditunda'     => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 italic',
                default       => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
            };
            $statusTextClass = match ($j['status']) {
                'berlangsung' => 'text-red-600 dark:text-red-400',
                'persiapan'   => 'text-amber-600 dark:text-amber-400',
                'menunggu'    => 'text-indigo-600 dark:text-indigo-400',
                'selesai'     => 'text-sky-600 dark:text-sky-400',
                default       => 'text-slate-600 dark:text-slate-400',
            };

            $meetingItem = [
                'id'                => $j['id'],
                'tanggal'           => $date,
                'formatted_date'    => $this->dayName($date) . ', ' . (int) date('d', strtotime($date)) . ' ' . $this->monthName($date) . ' ' . date('Y', strtotime($date)),
                'start'             => $startStr,
                'end'               => $endStr,
                'time_range'        => $timeRange,
                'judul'             => $j['judul'],
                'keterangan'        => $j['keterangan'] ?? '',
                'room'              => $this->displayLocation($j),
                'group'             => isset($unitMap[(int) $j['id']])
                    ? implode(', ', array_column($unitMap[(int) $j['id']], 'nama'))
                    : '-',
                'status'            => $j['status'],
                'status_badge'      => [
                    'label'       => $badge['label'] ?? ucfirst((string) $j['status']),
                    'short_label' => ($j['status'] === 'berlangsung') ? 'Berlangsung' : ($badge['label'] ?? ucfirst((string) $j['status'])),
                    'class'       => $badgeClass,
                ],
                'status_text_class' => $statusTextClass,
                'source'         => $j['source'] ?? 'umum',
                'source_label'   => $isBanmus ? 'SK Banmus' : 'Jadwal Umum',
                'is_banmus'      => $isBanmus,
                'detail_url'     => $detailUrl,
            ];

            $enrichedJadwals[]       = $meetingItem;
            $meetingsByDate[$date][] = $meetingItem;
        }

        $daysInMonth     = (int) date('t', strtotime($monthStart));
        $startWeekday    = (int) date('N', strtotime($monthStart));
        $prevDaysCount   = $startWeekday - 1;
        $daysInPrevMonth = (int) date('t', strtotime($monthStart . ' -1 month'));

        $calendarPrevDays = [];
        for ($i = 0; $i < $prevDaysCount; $i++) {
            $calendarPrevDays[] = $daysInPrevMonth - $prevDaysCount + 1 + $i;
        }

        $calendarDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateKey   = sprintf('%s-%02d', $activeMonth, $d);
            $hasAgenda = ! empty($meetingsByDate[$dateKey]);
            $calendarDays[] = [
                'date'           => $dateKey,
                'day'            => $d,
                'has_agenda'     => $hasAgenda,
                'is_today'       => ($dateKey === $today),
                'formatted_date' => $this->dayName($dateKey) . ', ' . $d . ' ' . $this->monthName($dateKey) . ' ' . date('Y', strtotime($dateKey)),
            ];
        }

        $totalCells       = $prevDaysCount + $daysInMonth;
        $trailingCells    = (7 - ($totalCells % 7)) % 7;
        $calendarNextDays = [];
        for ($i = 1; $i <= $trailingCells; $i++) {
            $calendarNextDays[] = $i;
        }

        $selectedDate = str_starts_with($today, $activeMonth) ? $today : null;
        if ($selectedDate === null && ! empty($calendarDays)) {
            foreach ($calendarDays as $cd) {
                if ($cd['has_agenda']) {
                    $selectedDate = $cd['date'];
                    break;
                }
            }
        }
        if ($selectedDate === null) {
            $selectedDate = $monthStart;
        }

        $selectedDateIndex = array_search($selectedDate, array_column($calendarDays, 'date'), true);
        $selectedDateHeading = $selectedDateIndex !== false
            ? $calendarDays[$selectedDateIndex]['formatted_date']
            : ($this->dayName($selectedDate) . ', ' . date('d', strtotime($selectedDate)) . ' ' . $this->monthName($selectedDate));

        return view('admin/dashboard/index', [
            'pageTitle'           => 'Dashboard',
            'breadcrumbs'         => [],
            'stats'               => [
                'rapat_hari_ini'   => $rapatHariIni,
                'agenda_bulan_ini' => count($jadwals),
                'berlangsung'      => $sedangBerlangsung,
                'mendatang'        => $agendaMendatang,
            ],
            'jadwals'             => $enrichedJadwals,
            'meetingsByDate'      => $meetingsByDate,
            'calendarPrevDays'    => $calendarPrevDays,
            'calendarDays'        => $calendarDays,
            'calendarNextDays'    => $calendarNextDays,
            'selectedDate'        => $selectedDate,
            'selectedDateHeading' => $selectedDateHeading,
            'monthLabel'          => $this->monthName($monthStart) . ' ' . date('Y', strtotime($monthStart)),
            'prevMonthUrl'        => base_url('admin/dashboard?month=' . $prevMonth),
            'nextMonthUrl'        => base_url('admin/dashboard?month=' . $nextMonth),
            'todayMonthUrl'       => base_url('admin/dashboard'),
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

    private function resolveStatus(array $row): string
    {
        if (($row['source'] ?? '') === JadwalUmumModel::SOURCE) {
            return JadwalUmumModel::resolveLifecycleStatus(
                (string) $row['tanggal'],
                $row['waktu_mulai'] ?? null,
                $row['waktu_selesai'] ?? null,
            );
        }

        return JadwalBanmusModel::resolveLifecycleStatus(
            true,
            (string) $row['tanggal'],
            $row['waktu_mulai'] ?? null,
            $row['waktu_selesai'] ?? null,
        );
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
