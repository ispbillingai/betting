<?php
/**
 * Surebets tab — the main working screen. Lists active opportunities best ROI
 * first, with the stake split for the operator's chosen bankroll.
 * @var callable $t @var callable $h
 */
use Bet\Arb\Calculator;
use Bet\Arb\Scanner;

$minRoi  = isset($_GET['min_roi']) ? (float)$_GET['min_roi'] : 0.0;
$bankroll = max(1.0, (float)($_GET['bankroll'] ?? 1000));

try {
    $rows = Scanner::active(100, $minRoi);
} catch (Throwable $e) {
    $rows = [];
    $dbErr = $e->getMessage();
}
?>
<h2><?= $h($t('sb_title')) ?></h2>
<p class="lead"><?= $h($t('sb_lead')) ?></p>

<?php if (!empty($dbErr)): ?>
  <div class="warn"><?= $h($t('sb_no_table')) ?></div>
<?php endif; ?>

<form method="get" class="card" style="margin-bottom:16px">
  <input type="hidden" name="tab" value="odds">
  <div class="row">
    <label class="fld"><span><?= $h($t('sb_min_roi')) ?></span>
      <input type="number" name="min_roi" step="0.1" min="0" value="<?= $h($minRoi) ?>"></label>
    <label class="fld"><span><?= $h($t('sb_bankroll')) ?></span>
      <input type="number" name="bankroll" step="10" min="1" value="<?= $h((int)$bankroll) ?>"></label>
    <label class="fld" style="flex:0 0 auto;align-self:flex-end">
      <button class="btn" type="submit"><?= $h($t('sb_apply')) ?></button></label>
  </div>
</form>

<?php if (!$rows): ?>
  <div class="panel"><div class="empty"><?= $h($t('sb_none')) ?></div></div>
<?php else: ?>
  <?php foreach ($rows as $r):
      $legs = json_decode((string)$r['legs'], true) ?: [];
      $roi  = (float)$r['roi_pct'];
      // Rescale the stored 100-unit shares to the operator's bankroll.
      $prices = [];
      foreach ($legs as $l) { $prices[$l['selection']] = (float)$l['price']; }
      $split = Calculator::split($prices, $bankroll);
  ?>
  <div class="panel" style="margin-bottom:14px">
    <div class="panel-h">
      <h3>
        <?= $h($r['home']) ?> vs <?= $h($r['away']) ?>
        <span class="pill"><?= $h(Calculator::marketLabel((string)$r['market'])) ?></span>
      </h3>
      <div style="display:flex;gap:10px;align-items:center">
        <span class="badge ok"><span class="dot"></span>ROI <?= number_format($roi, 2) ?>%</span>
        <span class="muted small"><?= $h(date('d/m H:i', strtotime((string)$r['starts_at']))) ?></span>
      </div>
    </div>

    <table>
      <thead><tr>
        <th><?= $h($t('sb_selection')) ?></th>
        <th><?= $h($t('sb_book')) ?></th>
        <th><?= $h($t('sb_price')) ?></th>
        <th><?= $h($t('sb_stake')) ?></th>
        <th><?= $h($t('sb_returns')) ?></th>
      </tr></thead>
      <tbody>
      <?php foreach ($legs as $l):
          $sel   = (string)$l['selection'];
          $stake = $split['stakes'][$sel] ?? 0.0;
          $ret   = $stake * (float)$l['price'];
      ?>
        <tr>
          <td><b><?= $h(strtoupper($sel)) ?></b></td>
          <td><?= $h($l['bookmaker']) ?></td>
          <td><b><?= number_format((float)$l['price'], 2) ?></b></td>
          <td><?= number_format($stake, 2) ?></td>
          <td class="muted"><?= number_format($ret, 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="row" style="margin-top:12px">
      <div class="muted small" style="flex:1">
        <?= $h($t('sb_total')) ?>: <b><?= number_format($bankroll, 2) ?></b> &nbsp;·&nbsp;
        <?= $h($t('sb_payout')) ?>: <b><?= number_format($split['payout'], 2) ?></b> &nbsp;·&nbsp;
        <?= $h($t('sb_profit')) ?>: <b style="color:var(--green)"><?= number_format($split['profit'], 2) ?></b>
      </div>
      <form method="post" style="flex:0 0 auto">
        <input type="hidden" name="do" value="bet_take">
        <input type="hidden" name="surebet_id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="bankroll" value="<?= $h($bankroll) ?>">
        <button class="btn" type="submit"><?= svg('check') ?> <?= $h($t('sb_take')) ?></button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
