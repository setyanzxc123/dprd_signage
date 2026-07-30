<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Schedule\AgendaWorkspaceService;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalModel;

class AgendaWorkspaceController extends BaseController
{
    public function kalender(): string
    {
        (new JadwalModel())->autoUpdateStatuses();
        (new JadwalBanmusModel())->autoUpdateStatuses();

        $month = trim((string) $this->request->getGet('month'));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            $month = date('Y-m');
        }

        $view = $this->request->getGet('view') === 'calendar' ? 'calendar' : 'list';
        $filters = [];
        foreach (['source', 'jenis', 'lingkup', 'unit', 'lokasi', 'status', 'publikasi'] as $filter) {
            $filters[$filter] = trim((string) $this->request->getGet($filter));
        }

        $workspace = (new AgendaWorkspaceService())->loadMonth($month, $filters);
        $agendasByDate = [];
        if ($view === 'calendar') {
            foreach ($workspace['agendas'] as $agenda) {
                $agendasByDate[$agenda['tanggal']][] = $agenda;
            }
        }

        $monthTimestamp = strtotime($month . '-01');
        $monthLabel = $this->monthLabel($monthTimestamp);
        $query = ['view' => $view, ...array_filter($filters, static fn (string $value): bool => $value !== '')];

        return view('admin/agenda_workspace/kalender', [
            'pageTitle'       => 'Seluruh Agenda',
            'month'           => $month,
            'month_label'     => $monthLabel,
            'view_mode'       => $view,
            'filters'         => $filters,
            'filter_options'  => $workspace['options'],
            'counts'          => $workspace['counts'],
            'agendas'         => $workspace['agendas'],
            'calendar_days'   => $view === 'calendar' ? $this->calendarDays($month, $agendasByDate) : [],
            'previous_url'    => $this->calendarUrl(
                date('Y-m', strtotime('-1 month', $monthTimestamp)),
                $query,
            ),
            'next_url'        => $this->calendarUrl(
                date('Y-m', strtotime('+1 month', $monthTimestamp)),
                $query,
            ),
            'calendar_url'    => $this->calendarUrl($month, [...$query, 'view' => 'calendar']),
            'list_url'        => $this->calendarUrl($month, [...$query, 'view' => 'list']),
        ]);
    }

    /**
     * @param array<string, list<array<string, mixed>>> $agendasByDate
     * @return list<array{date: string|null, day: int|null, agendas: list<array<string, mixed>>, is_today: bool}>
     */
    private function calendarDays(string $month, array $agendasByDate): array
    {
        $firstDate = $month . '-01';
        $leadingDays = (int) date('N', strtotime($firstDate)) - 1;
        $daysInMonth = (int) date('t', strtotime($firstDate));
        $cells = [];

        for ($index = 0; $index < $leadingDays; ++$index) {
            $cells[] = ['date' => null, 'day' => null, 'agendas' => [], 'is_today' => false];
        }
        for ($day = 1; $day <= $daysInMonth; ++$day) {
            $date = sprintf('%s-%02d', $month, $day);
            $cells[] = [
                'date'     => $date,
                'day'      => $day,
                'agendas'  => $agendasByDate[$date] ?? [],
                'is_today' => $date === date('Y-m-d'),
            ];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = ['date' => null, 'day' => null, 'agendas' => [], 'is_today' => false];
        }

        return $cells;
    }

    /** @param array<string, string> $query */
    private function calendarUrl(string $month, array $query): string
    {
        return base_url('admin/kalender') . '?' . http_build_query([
            'month' => $month,
            ...$query,
        ]);
    }

    private function monthLabel(int $timestamp): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}
