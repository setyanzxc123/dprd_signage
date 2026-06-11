<?php
// Helper presentasi - mapping status rapat & notifikasi WA ke CSS class + label.

if (! function_exists('status_badge')) {
    // class CSS + label untuk status rapat
    function status_badge(string $status): array
    {
        return match ($status) {
            'berlangsung' => ['class' => 'badge-berlangsung', 'label' => 'Sedang Berlangsung'],
            'persiapan'   => ['class' => 'badge-persiapan',   'label' => 'Persiapan'],
            'menunggu'    => ['class' => 'badge-menunggu',    'label' => 'Menunggu Waktu'],
            'selesai'     => ['class' => 'badge-selesai',     'label' => 'Selesai'],
            default       => ['class' => 'badge-menunggu',    'label' => ucfirst($status)],
        };
    }
}

if (! function_exists('notif_config')) {
    // class CSS, ikon, dan label untuk status notifikasi WA
    function notif_config(string $status): array
    {
        return match ($status) {
            'sent'    => ['class' => 'badge-wa-sent',    'icon' => 'check',    'iconClass' => 'sent',    'label' => 'Terkirim', 'labelClass' => 'sent'],
            'failed'  => ['class' => 'badge-wa-failed',  'icon' => 'x',        'iconClass' => 'failed',  'label' => 'Gagal',    'labelClass' => 'failed'],
            'pending' => ['class' => 'badge-wa-pending', 'icon' => 'hourglass', 'iconClass' => 'pending', 'label' => 'Pending',  'labelClass' => 'pending'],
            default   => ['class' => 'badge-wa-pending', 'icon' => 'clock',    'iconClass' => '',        'label' => ucfirst($status), 'labelClass' => ''],
        };
    }
}
