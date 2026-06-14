<?php
$pagination = $pagination ?? [
    'page'       => 1,
    'perPage'    => 10,
    'total'      => 0,
    'totalPages' => 1,
    'from'       => 0,
    'to'         => 0,
];
$paginationBase  = $paginationBase ?? current_url();
$paginationQuery = $paginationQuery ?? [];
$pageUrl = static function (int $page) use ($paginationBase, $paginationQuery): string {
    return $paginationBase . '?' . http_build_query($paginationQuery + ['page' => $page]);
};
?>

<?php if ($pagination['totalPages'] > 1): ?>
    <div class="section-card-footer flex justify-between items-center gap-3 flex-wrap">
        <div class="text-xs text-base-content/60">
            Halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?>
        </div>
        <nav aria-label="<?= esc($ariaLabel ?? 'Pagination') ?>">
            <div class="join">
                <a class="join-item btn btn-sm <?= $pagination['page'] <= 1 ? 'btn-disabled' : '' ?>" href="<?= $pagination['page'] <= 1 ? '#' : esc($pageUrl($pagination['page'] - 1)) ?>">
                    Sebelumnya
                </a>
                <?php
                    $startPage = max(1, $pagination['page'] - 2);
                    $endPage   = min($pagination['totalPages'], $pagination['page'] + 2);
                ?>
                <?php if ($startPage > 1): ?>
                    <a class="join-item btn btn-sm" href="<?= esc($pageUrl(1)) ?>">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="join-item btn btn-sm btn-disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <a class="join-item btn btn-sm <?= $p === $pagination['page'] ? 'btn-active' : '' ?>" href="<?= esc($pageUrl($p)) ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($endPage < $pagination['totalPages']): ?>
                    <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                        <span class="join-item btn btn-sm btn-disabled">...</span>
                    <?php endif; ?>
                    <a class="join-item btn btn-sm" href="<?= esc($pageUrl($pagination['totalPages'])) ?>"><?= $pagination['totalPages'] ?></a>
                <?php endif; ?>
                <a class="join-item btn btn-sm <?= $pagination['page'] >= $pagination['totalPages'] ? 'btn-disabled' : '' ?>" href="<?= $pagination['page'] >= $pagination['totalPages'] ? '#' : esc($pageUrl($pagination['page'] + 1)) ?>">
                    Berikutnya
                </a>
            </div>
        </nav>
    </div>
<?php endif; ?>
