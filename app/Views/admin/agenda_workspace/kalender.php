<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Operasional</p>
        <h1 class="page-title">Kalender Seluruh Agenda</h1>
        <p class="mt-1 text-sm text-base-content/60">Tampilan gabungan seluruh agenda dari semua sumber.</p>
    </div>
</div>

<div class="card card-border bg-base-100 shadow-sm">
    <div class="card-body items-center py-20 text-center">
        <i data-lucide="calendar-range" class="h-14 w-14 text-base-content/20"></i>
        <h2 class="mt-4 text-lg font-bold">Segera Hadir</h2>
        <p class="mt-2 max-w-sm text-sm text-base-content/55">
            Kalender terpadu yang menggabungkan Agenda Banmus, Insidental Internal,
            dan Agenda Eksternal dalam satu tampilan akan tersedia setelah Fase 5.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-outline btn-sm gap-1">
                <i data-lucide="file-stack" class="h-4 w-4"></i>
                Agenda Banmus
            </a>
            <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline btn-sm gap-1">
                <i data-lucide="calendar-days" class="h-4 w-4"></i>
                Insidental Internal
            </a>
            <a href="<?= base_url('admin/agenda-umum') ?>" class="btn btn-outline btn-sm gap-1">
                <i data-lucide="calendar-range" class="h-4 w-4"></i>
                Agenda Eksternal
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
