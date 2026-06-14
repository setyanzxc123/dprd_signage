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
            <ul class="ta-pagination ta-pagination-sm mb-0">
                <li class="ta-page-item <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="ta-page-link" href="<?= $pagination['page'] <= 1 ? '#' : esc($pageUrl($pagination['page'] - 1)) ?>">
                        Sebelumnya
                    </a>
                </li>
                <?php
                    $startPage = max(1, $pagination['page'] - 2);
                    $endPage   = min($pagination['totalPages'], $pagination['page'] + 2);
                ?>
                <?php if ($startPage > 1): ?>
                    <li class="ta-page-item">
                        <a class="ta-page-link" href="<?= esc($pageUrl(1)) ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="ta-page-item disabled"><span class="ta-page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <li class="ta-page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                        <a class="ta-page-link" href="<?= esc($pageUrl($p)) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($endPage < $pagination['totalPages']): ?>
                    <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                        <li class="ta-page-item disabled"><span class="ta-page-link">...</span></li>
                    <?php endif; ?>
                    <li class="ta-page-item">
                        <a class="ta-page-link" href="<?= esc($pageUrl($pagination['totalPages'])) ?>"><?= $pagination['totalPages'] ?></a>
                    </li>
                <?php endif; ?>
                <li class="ta-page-item <?= $pagination['page'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
                    <a class="ta-page-link" href="<?= $pagination['page'] >= $pagination['totalPages'] ? '#' : esc($pageUrl($pagination['page'] + 1)) ?>">
                        Berikutnya
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
