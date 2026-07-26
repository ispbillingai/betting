<?php
/**
 * Bets tab — positions taken from surebets, with settle actions and totals.
 * @var callable $t @var callable $h
 */
use Bet\Arb\Calculator;
use Bet\Db;

try {
    $rows = Db::pdo()->query(
        'SELECT b.*, e.home, e.away
           FROM bets b
           LEFT JOIN events e ON e.id = b.event_id
          ORDER BY b.placed_at DESC
          LIMIT 200'
    )->fetchAll();
    $tot = Db::pdo()->query(
        "SELECT
            COUNT(*) AS n,
            SUM(total_stake) AS staked,
            SUM(CASE WHEN status='open' THEN total_stake ELSE 0 END) AS open_stake,
            SUM(COALESCE(profit,0)) AS profit
          FROM bets"
    )->fetch() ?: [];
} catch (Throwable $e) {
    $rows = []; $tot = []; $dbErr = true;
}

$staked = (float)($tot['staked'] ?? 0);
$profit = (float)($tot['profit'] ?? 0);
$roi    = $staked > 0 ? ($profit / $staked) * 100 : 0.0;
?>
<h2><?= $h($t('nav_bets')) ?></h2>
<p class="lead"><?= $h($t('bt_lead')) ?></p>

<?php if (!empty($dbErr)): ?>
  <div class="warn"><?= $h($t('sb_no_table')) ?></div>
<?php endif; ?>

<div class="grid">
  <div class="tile"><div class="tile-top"><?= svg('bets') ?><span><?= $h($t('bt_count')) ?></span></div>
    <span class="big"><?= (int)($tot['n'] ?? 0) ?></span></div>
  <div class="tile"><div class="tile-top"><?= svg('odds') ?><span><?= $h($t('bt_staked')) ?></span></div>
    <span class="big"><?= number_format($staked, 2) ?></span></div>
  <div class="tile"><div class="tile-top"><?= svg('clock') ?><span><?= $h($t('bt_open')) ?></span></div>
    <span class="big"><?= number_format((float)($tot['open_stake'] ?? 0), 2) ?></span></div>
  <div class="tile"><div class="tile-top"><?= svg('trophy') ?><span><?= $h($t('bt_profit')) ?></span></div>
    <span class="big" style="color:<?= $profit >= 0 ? 'var(--green)' : 'var(--red)' ?>">
      <?= number_format($profit, 2) ?></span>
    <span class="sub">ROI <?= number_format($roi, 2) ?>%</span></div>
</div>

<table>
  <thead><tr>
    <th><?= $h($t('ev_match')) ?></th>
    <th><?= $h($t('sb_market')) ?></th>
    <th><?= $h($t('bt_legs')) ?></th>
    <th><?= $h($t('bt_stake')) ?></th>
    <th><?= $h($t('bt_exp_roi')) ?></th>
    <th><?= $h($t('bt_status')) ?></th>
    <th><?= $h($t('bt_profit')) ?></th>
    <th><?= $h($t('actions')) ?></th>
  </tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="8" class="empty"><?= $h($t('bt_none')) ?></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $b):
      $legs = json_decode((string)$b['legs'], true) ?: [];
      $st   = (string)$b['status'];
  ?>
    <tr>
      <td><b><?= $h(($b['home'] ?? '?') . ' vs ' . ($b['away'] ?? '?')) ?></b>
          <div class="muted small"><?= $h(date('d/m H:i', strtotime((string)$b['placed_at']))) ?></div></td>
      <td><span class="pill"><?= $h(Calculator::marketLabel((string)$b['market'])) ?></span></td>
      <td class="small">
        <?php foreach ($legs as $l): ?>
          <div><b><?= $h(strtoupper((string)$l['selection'])) ?></b>
               <?= $h($l['bookmaker'] ?? '') ?>
               @<?= number_format((float)($l['price'] ?? 0), 2) ?>
               <span class="muted">(<?= number_format((float)($l['stake'] ?? 0), 2) ?>)</span></div>
        <?php endforeach; ?>
      </td>
      <td><?= number_format((float)$b['total_stake'], 2) ?></td>
      <td class="muted"><?= number_format((float)$b['expected_roi'], 2) ?>%</td>
      <td><span class="pill pill-<?= $h($st) ?>"><?= $h($t('bt_' . $st) !== 'bt_' . $st ? $t('bt_' . $st) : $st) ?></span></td>
      <td><?= $b['profit'] === null ? '—'
            : '<b style="color:' . ((float)$b['profit'] >= 0 ? 'var(--green)' : 'var(--red)') . '">'
              . number_format((float)$b['profit'], 2) . '</b>' ?></td>
      <td>
        <?php if ($st === 'open'): ?>
          <form method="post" class="inline">
            <input type="hidden" name="do" value="bet_settle">
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <select name="result">
              <?php foreach ($legs as $l): ?>
                <option value="<?= $h($l['selection']) ?>"><?= $h(strtoupper((string)$l['selection'])) ?></option>
              <?php endforeach; ?>
              <option value="void"><?= $h($t('bt_void')) ?></option>
            </select>
            <button class="btn ghost tiny" type="submit"><?= $h($t('bt_settle')) ?></button>
          </form>
        <?php else: ?>
          <span class="muted small">—</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
