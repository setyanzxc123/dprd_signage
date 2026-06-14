<?php
// Helper presentasi - mapping status rapat & notifikasi WA ke CSS class + label.

if (! function_exists('status_badge')) {
    // class CSS + label untuk status rapat
    function status_badge(string $status): array
    {
        return match ($status) {
            'berlangsung' => ['class' => 'badge-success', 'label' => 'Sedang Berlangsung'],
            'persiapan'   => ['class' => 'badge-warning', 'label' => 'Persiapan'],
            'menunggu'    => ['class' => 'badge-neutral', 'label' => 'Menunggu Waktu'],
            'selesai'     => ['class' => 'badge-info', 'label' => 'Selesai'],
            default       => ['class' => 'badge-neutral', 'label' => ucfirst($status)],
        };
    }
}

if (! function_exists('notif_config')) {
    // class CSS, ikon, dan label untuk status notifikasi WA
    function notif_config(string $status): array
    {
        return match ($status) {
            'sent'    => ['class' => 'badge-success', 'icon' => 'check',    'iconClass' => 'sent',    'label' => 'Terkirim', 'labelClass' => 'sent'],
            'failed'  => ['class' => 'badge-error',   'icon' => 'x',        'iconClass' => 'failed',  'label' => 'Gagal',    'labelClass' => 'failed'],
            'pending' => ['class' => 'badge-warning', 'icon' => 'hourglass', 'iconClass' => 'pending', 'label' => 'Pending',  'labelClass' => 'pending'],
            default   => ['class' => 'badge-neutral', 'icon' => 'clock',    'iconClass' => '',        'label' => ucfirst($status), 'labelClass' => ''],
        };
    }
}

