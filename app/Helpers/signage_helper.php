<?php
// Helper presentasi - mapping status rapat ke CSS class dan label.

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

