<?php
/**
 * Events tab — fixtures with how many books are quoting and the best 1X2 price
 * per outcome. Useful for spotting thin coverage. @var callable $t @var callable $h
 */
use Bet\Db;

try {
    $rows = Db::pdo()->query(
        "SELECT e.id, e.home, e.away, e.league, e.starts_at, e.status,
                COUNT(DISTINCT o.bookmaker_id) AS books,
                MAX(CASE WHEN o.market='1x2' AND o.selection='1' THEN o.price END) AS b1,
                MAX(CASE WHEN o.market='1x2' AND o.selection='X' THEN o.price END) AS bx,
                MAX(CASE WHEN o.market='1x2' AND o.selection='2' THEN o.price END) AS b2
           FROM events e
           LEFT JOIN odds o ON o.event_id = e.id
          GROUP BY e.id
          ORDER BY e.starts_at
          LIMIT 200"
    )->fetchAll();
} catch (Throwable $e) {
    $rows = [];
    $dbErr = true;
}
?>
<h2><?= $h($t('nav_events')) ?></h2>
<p class="lead"><?= $h($t('ev_lead')) ?></p>

<?php if (!empty($dbErr)): ?>
  <div class="warn"><?= $h($t('sb_no_table')) ?></div>
<?php endif; ?>

<table>
  <thead><tr>
    <th><?= $h($t('ev_match')) ?></th>
    <th><?= $h($t('ev_league')) ?></th>
    <th><?= $h($t('ev_start')) ?></th>
    <th><?= $h($t('ev_books')) ?></th>
    <th>1</th><th>X</th><th>2</th>
  </tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="empty"><?= $h($t('none_yet')) ?></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><b><?= $h($r['home']) ?></b> vs <b><?= $h($r['away']) ?></b></td>
      <td class="muted"><?= $h($r['league'] ?? '') ?></td>
      <td class="muted small"><?= $h(date('d/m H:i', strtotime((string)$r['starts_at']))) ?></td>
      <td><span class="pill"><?= (int)$r['books'] ?></span></td>
      <td><?= $r['b1'] !== null ? number_format((float)$r['b1'], 2) : '—' ?></td>
      <td><?= $r['bx'] !== null ? number_format((float)$r['bx'], 2) : '—' ?></td>
      <td><?= $r['b2'] !== null ? number_format((float)$r['b2'], 2) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
