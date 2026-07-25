<?php
/**
 * Overview tab. Counters are intentionally static zeros — no odds/bets data
 * source is connected yet. @var callable $t @var callable $h
 */
?>
<h2><?= $h($t('ov_title')) ?></h2>
<p class="lead"><?= $h($t('ov_lead')) ?></p>

<div class="grid">
  <div class="tile">
    <div class="tile-top"><?= svg('books') ?><span><?= $h($t('ov_books')) ?></span></div>
    <span class="big">0</span>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('events') ?><span><?= $h($t('ov_events')) ?></span></div>
    <span class="big">0</span>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('bets') ?><span><?= $h($t('ov_bets')) ?></span></div>
    <span class="big">0</span>
  </div>
  <div class="tile">
    <div class="tile-top"><?= svg('odds') ?><span><?= $h($t('ov_pnl')) ?></span></div>
    <span class="big">—</span>
  </div>
</div>

<div class="panel">
  <div class="panel-h"><h3><?= svg('clock') ?><?= $h($t('ov_activity')) ?></h3></div>
  <div class="empty"><?= $h($t('none_yet')) ?></div>
</div>
