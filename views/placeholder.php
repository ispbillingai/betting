<?php
/**
 * Shared stub for tabs whose domain logic isn't built yet (odds, events, bets,
 * rules, books, logs). Renders the tab's title + its own explanatory line so
 * the shell is navigable end to end. @var callable $t @var callable $h @var string $tab
 */
?>
<h2><?= $h($t('nav_' . $tab)) ?></h2>
<p class="lead"><?= $h($t('ph_' . $tab)) ?></p>

<div class="panel">
  <div class="panel-h"><h3><?= svg($tab) ?><?= $h($t('nav_' . $tab)) ?></h3></div>
  <div class="empty"><?= $h($t('coming_soon')) ?></div>
</div>
