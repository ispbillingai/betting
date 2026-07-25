<?php
/**
 * Settings tab — writes dot-path keys into the `settings` table, which
 * Bootstrap overlays onto config/config.php at boot. @var callable $t @var callable $h
 */
use Bet\Config;
?>
<h2><?= $h($t('set_title')) ?></h2>
<p class="lead"><?= $h($t('set_lead')) ?></p>

<div class="card">
  <form method="post">
    <input type="hidden" name="do" value="settings_save">
    <div class="row">
      <label class="fld"><span><?= $h($t('set_company')) ?></span>
        <input type="text" name="s[app.company_name]"
               value="<?= $h(Config::get('app.company_name', '')) ?>"></label>
      <label class="fld"><span><?= $h($t('set_baseurl')) ?></span>
        <input type="text" name="s[app.base_url]"
               value="<?= $h(Config::get('app.base_url', '')) ?>"></label>
    </div>
    <div class="row">
      <label class="fld"><span><?= $h($t('set_lang')) ?></span>
        <?php $cur = (string)Config::get('app.default_lang', 'en'); ?>
        <select name="s[app.default_lang]">
          <option value="en" <?= $cur === 'en' ? 'selected' : '' ?>>English</option>
          <option value="it" <?= $cur === 'it' ? 'selected' : '' ?>>Italiano</option>
        </select></label>
      <label class="fld"><span><?= $h($t('set_tz')) ?></span>
        <input type="text" name="s[app.timezone]"
               value="<?= $h(Config::get('app.timezone', 'UTC')) ?>"></label>
    </div>
    <button class="btn" type="submit"><?= svg('check') ?> <?= $h($t('save')) ?></button>
  </form>
</div>
