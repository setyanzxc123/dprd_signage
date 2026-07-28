<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Operasional</p>
        <h1 class="page-title">Laporan Agenda</h1>
        <p class="mt-1 text-sm text-base-content/60">Rekap, statistik, dan export lintas seluruh sumber agenda.</p>
    </div>
</div>

<div class="card card-border bg-base-100 shadow-sm">
    <div class="card-body items-center py-20 text-center">
        <i data-lucide="chart-bar" class="h-14 w-14 text-base-content/20"></i>
        <h2 class="mt-4 text-lg font-bold">Segera Hadir</h2>
        <p class="mt-2 max-w-sm text-sm text-base-content/55">
            Laporan dan export lintas seluruh sumber agenda akan tersedia setelah Fase 6,
            ketika model data seluruh modul sudah stabil.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-2 text-xs text-base-content/50">
            <span class="badge badge-outline badge-sm">Rekap seluruh agenda</span>
            <span class="badge badge-outline badge-sm">Proyeksi vs Fixed Banmus</span>
            <span class="badge badge-outline badge-sm">Rekap per unit</span>
            <span class="badge badge-outline badge-sm">Export Excel</span>
            <span class="badge badge-outline badge-sm">Export PDF</span>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
