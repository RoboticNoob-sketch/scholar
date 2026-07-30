<?php

declare(strict_types=1);

/** @var string $tableId */
/** @var string $ajaxUrl */
/** @var array<int, string> $columns */
/** @var string|null $filterForm */
/** @var array<int, array{order: int, dir: string}>|null $defaultOrder */

$filterForm = $filterForm ?? null;
$defaultOrder = $defaultOrder ?? [[0, 'desc']];
/** @var int|null $checkboxColumn */
$checkboxColumn = $checkboxColumn ?? null;
?>
<div class="card table-card">
  <div class="table-wrap">
    <table
      id="<?= e($tableId) ?>"
      class="table datatable-server"
      width="100%"
      data-ajax-url="<?= e($ajaxUrl) ?>"
      <?php if ($filterForm): ?>data-filter-form="<?= e($filterForm) ?>"<?php endif; ?>
      <?php if ($checkboxColumn !== null): ?>data-checkbox-column="<?= (int) $checkboxColumn ?>"<?php endif; ?>
      data-default-order="<?= e(json_encode($defaultOrder)) ?>"
    >
      <thead>
        <tr>
          <?php foreach ($columns as $column): ?>
            <th><?= e($column) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
    </table>
  </div>
</div>
