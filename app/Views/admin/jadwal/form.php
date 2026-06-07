<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">
        <?= $meeting ? 'Perbarui jadwal dan pengaturan notifikasi' : 'Buat jadwal baru dan atur notifikasi WA otomatis' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-3">

        <div class="col-lg-8">
            <div class="form-card mb-3">

                <div class="form-section-title">Informasi Dasar Rapat</div>

                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="judul">
                            Judul Rapat <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="judul" name="judul"
                            value="<?= esc($meeting['judul'] ?? '') ?>"
                            placeholder="Contoh: Rapat Paripurna Pembahasan APBD 2026" required />
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="ruangan_id">
                            Ruangan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="ruangan_id" name="ruangan_id" required>
                            <option value="">-- Pilih Ruangan --</option>
                            <?php if (empty($rooms)): ?>
                                <option disabled>Belum ada ruangan — tambah di Master Data dulu</option>
                            <?php else: ?>
                                <?php foreach ($rooms as $r):
                                    $selected = ($meeting['ruangan_id'] ?? '') == $r['id'] ? 'selected' : '';
                                    ?>
                                    <option value="<?= $r['id'] ?>" <?= $selected ?>>
                                        <?= esc($r['name'] ?? '') ?><?= isset($r['kapasitas']) ? ' (Kap. ' . $r['kapasitas'] . ' orang)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="waktu_mulai">
                            Waktu Mulai <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai"
                            value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_mulai'])
                                ? $meeting['tanggal'] . 'T' . $meeting['waktu_mulai']
                                : '') ?>" required />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="waktu_selesai">
                            Waktu Selesai <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="waktu_selesai" name="waktu_selesai"
                            value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_selesai'])
                                ? $meeting['tanggal'] . 'T' . $meeting['waktu_selesai']
                                : '') ?>" required />
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="keterangan">Keterangan / Agenda</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                            placeholder="Uraian singkat agenda rapat..."><?= esc($meeting['keterangan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="materi_url">
                            <i class="bi bi-qr-code me-1 text-primary"></i>
                            Link Materi Rapat
                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem;">QR Code</span>
                        </label>
                        <input type="url" class="form-control" id="materi_url" name="materi_url"
                            value="<?= esc($meeting['materi_url'] ?? '') ?>"
                            placeholder="https://drive.google.com/... atau link dokumen lainnya" />
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Opsional. Jika diisi, QR Code akan otomatis muncul di layar TV saat rapat berlangsung —
                            masyarakat dapat scan untuk mengunduh dokumen materi.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="stream_url">
                            <i class="bi bi-play-circle me-1 text-danger"></i>
                            Link Live Streaming / Arsip Video
                        </label>
                        <input type="url" class="form-control" id="stream_url" name="stream_url"
                            value="<?= esc($meeting['stream_url'] ?? '') ?>"
                            placeholder="https://youtube.com/live/... atau link streaming lainnya" />
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Opsional. Jika diisi, tombol "Tonton" akan muncul di portal publik.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">

                <div class="form-section-title">Klasifikasi & Visibilitas</div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis Rapat</label>
                    <div class="form-text mb-2">Apakah rapat ini terencana dalam SK Bamus atau mendadak?</div>
                    <?php $jenis = $meeting['jenis'] ?? 'insidental'; ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="jenis"
                            id="jenis-bamus" value="bamus"
                            <?= $jenis === 'bamus' ? 'checked' : '' ?> />
                        <label class="form-check-label" for="jenis-bamus">
                            <span class="badge bg-purple-subtle text-purple">Bamus</span>
                            Terencana dalam SK Bamus
                        </label>
                    </div>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="radio" name="jenis"
                            id="jenis-insidental" value="insidental"
                            <?= $jenis === 'insidental' ? 'checked' : '' ?> />
                        <label class="form-check-label" for="jenis-insidental">
                            <span class="badge bg-secondary-subtle text-secondary">Insidental</span>
                            Rapat mendadak / di luar SK
                        </label>
                    </div>
                </div>

                <hr />

                <div class="mb-3">
                    <label class="form-label fw-semibold">Visibilitas Publik</label>
                    <div class="form-text mb-2">
                        Apakah jadwal ini boleh ditampilkan di layar TV lobby dan portal publik?
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="is_publik" name="is_publik" value="1"
                            <?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'checked' : '' ?> />
                        <label class="form-check-label" for="is_publik">
                            <span id="publik-label"><?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'Tampil di Publik' : 'Hanya Internal' ?></span>
                        </label>
                    </div>
                </div>

                <hr />

                <div class="form-section-title">Target Peserta & WA</div>

                <!-- Pilih komisi yang akan dinotifikasi -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Peserta</label>
                    <div class="form-text mb-2">
                        Centang komisi yang akan menerima notifikasi WA.
                    </div>

                    <?php
                    $targetKomisi = $meeting['target_komisi'] ?? [];
                    foreach ($komisi_list as $k):
                        $checked = in_array($k, $targetKomisi) ? 'checked' : '';
                    ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="target_komisi[]" value="<?= $k ?>"
                                id="komisi-<?= str_replace(' ', '-', strtolower($k)) ?>" <?= $checked ?> />
                            <label class="form-check-label" for="komisi-<?= str_replace(' ', '-', strtolower($k)) ?>">
                                <?= $k ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr />

                <!-- Jadwal blast WA -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="blast_before">
                        Jadwal Blast WA
                    </label>
                    <select class="form-select form-select-sm" id="blast_before" name="blast_before">
                        <option value="1440" <?= ($meeting['blast_before'] ?? '') == 1440 ? 'selected' : '' ?>>H-1 Hari sebelum rapat</option>
                        <option value="120"  <?= ($meeting['blast_before'] ?? '') == 120  ? 'selected' : '' ?>>H-2 Jam sebelum rapat</option>
                        <option value="60"   <?= ($meeting['blast_before'] ?? '') == 60   ? 'selected' : '' ?>>H-1 Jam sebelum rapat</option>
                        <option value="30"   <?= ($meeting['blast_before'] ?? '') == 30   ? 'selected' : '' ?>>H-30 Menit sebelum rapat</option>
                        <option value="0"    <?= ($meeting['blast_before'] ?? '') == 0    ? 'selected' : '' ?>>Tepat saat rapat dimulai</option>
                    </select>
                    <div class="form-text">
                        Sistem akan otomatis mengirim WA sesuai waktu ini.
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /.row -->

    <!-- Tombol Aksi -->
    <div class="d-flex gap-2 mt-3">
        <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-calendar-check me-1"></i>
            <?= $meeting ? 'Simpan Perubahan' : 'Simpan & Jadwalkan Notifikasi' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('is_publik');
        const label  = document.getElementById('publik-label');
        if (toggle && label) {
            toggle.addEventListener('change', function() {
                label.textContent = this.checked ? 'Tampil di Publik' : 'Hanya Internal';
            });
        }
    });
</script>
<?= $this->endSection() ?>

