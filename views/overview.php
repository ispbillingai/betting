<?php
/**
 * Overview tab — live counters from the scanner and bet ledger.
 * @var callable $t @var callable $h
 */
use Bet\Arb\Bets;
use Bet\Arb\Calculator;
use Bet\Arb\Scanner;
use Bet\Db;

$books = $events = $active = 0;
$best  = null;
$stats = ['n' => 0, 'open_n' => 0, 'staked' => 0.0, 'profit' => 0.0, 'roi' => 0.0];

try {
    $pdo    = Db::pdo();
    $books  = (int)$pdo->query('SELECT COUNT(*) FROM bookmakers WHERE active = 1')->fetchColumn();
    $events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'prematch' AND starts_at > NOW()")->fetchColumn();
    $active = (int)$pdo->query('SELECT COUNT(*) FROM surebets WHERE active = 1')->fetchColumn();
    $stats  = Bets::stats();
    $top    = Scanner::active(5, 0.0);
    $best   = $top[0] ?? null;
} catch (Throwable $e) {
    $dbErr = true;
}
?>
<h2><?= $h($t('ov_title')) ?></h2>

<?php if (!empty($dbErr)): ?>
  <div class="warn"><?= $h($t('sb_no_table')) ?></div>
<?php endif; ?>

<div class="grid">
  <div class="tile">
    <div class="tile-top"><?= svg('books') ?><span><?= $h($t('ov_books')) ?></span></div>
    <span class="big"><?= $books ?></span>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('events') ?><span><?= $h($t('ov_events')) ?></span></div>
    <span class="big"><?= $events ?></span>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('odds') ?><span><?= $h($t('ov_active_sb')) ?></span></div>
    <span class="big" style="color:var(--green)"><?= $active ?></span>
    <?php if ($best): ?>
      <span class="sub"><?= $h($t('ov_best')) ?> <?= number_format((float)$best['roi_pct'], 2) ?>%</span>
    <?php endif; ?>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('trophy') ?><span><?= $h($t('ov_pnl')) ?></span></div>
    <span class="big" style="color:<?= $stats['profit'] >= 0 ? 'var(--green)' : 'var(--red)' ?>">
      <?= number_format($stats['profit'], 2) ?></span>
    <span class="sub"><?= $stats['n'] ?> <?= $h($t('ov_bets')) ?> · ROI <?= number_format($stats['roi'], 2) ?>%</span>
  </div>
</div>

<div class="panel">
  <div class="panel-h">
    <h3><?= svg('odds') ?><?= $h($t('ov_top')) ?></h3>
    <a class="btn ghost tiny" href="?tab=odds"><?= $h($t('ov_all')) ?></a>
  </div>
  <?php if (empty($top)): ?>
    <div class="empty"><?= $h($t('sb_none')) ?></div>
  <?php else: ?>
    <table>
      <thead><tr>
        <th><?= $h($t('ev_match')) ?></th>
        <th><?= $h($t('sb_market')) ?></th>
        <th>ROI</th>
        <th><?= $h($t('sb_book')) ?></th>
      </tr></thead>
      <tbody>
      <?php foreach ($top as $s):
          $legs = json_decode((string)$s['legs'], true) ?: [];
          $names = array_map(fn($l) => $l['bookmaker'] ?? '', $legs);
      ?>
        <tr>
          <td><b><?= $h($s['home']) ?></b> vs <b><?= $h($s['away']) ?></b></td>
          <td><span class="pill"><?= $h(Calculator::marketLabel((string)$s['market'])) ?></span></td>
          <td><span class="badge ok"><span class="dot"></span><?= number_format((float)$s['roi_pct'], 2) ?>%</span></td>
          <td class="muted small"><?= $h(implode(' · ', $names)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
