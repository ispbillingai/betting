<?php
/**
 * Users tab — list operators, create, toggle active, reset password, delete.
 * @var callable $t @var callable $h
 */
use Bet\Auth;

try {
    $users = Auth::all();
} catch (Throwable) {
    $users = []; // table not migrated yet
}
?>
<h2><?= $h($t('users_title')) ?></h2>
<p class="lead"><?= $h($t('users_lead')) ?></p>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th><?= $h($t('u_username')) ?></th>
      <th><?= $h($t('u_fullname')) ?></th>
      <th><?= $h($t('u_email')) ?></th>
      <th><?= $h($t('u_role')) ?></th>
      <th><?= $h($t('u_active')) ?></th>
      <th><?= $h($t('actions')) ?></th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$users): ?>
    <tr><td colspan="7" class="empty"><?= $h($t('none_yet')) ?></td></tr>
  <?php endif; ?>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><b><?= $h($u['username']) ?></b></td>
      <td><?= $h($u['full_name'] ?? '') ?></td>
      <td><?= $h($u['email'] ?? '') ?></td>
      <td><span class="pill"><?= $h($u['role']) ?></span></td>
      <td>
        <span class="pill <?= (int)$u['active'] === 1 ? 'pill-yes' : 'pill-no' ?>">
          <?= $h((int)$u['active'] === 1 ? $t('u_yes') : $t('u_no')) ?>
        </span>
      </td>
      <td>
        <form method="post" class="inline">
          <input type="hidden" name="do" value="user_toggle">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="active" value="<?= (int)$u['active'] === 1 ? 0 : 1 ?>">
          <button class="btn ghost tiny" type="submit">
            <?= $h((int)$u['active'] === 1 ? $t('u_disable') : $t('u_enable')) ?>
          </button>
        </form>
        <form method="post" class="inline">
          <input type="hidden" name="do" value="user_password">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="password" name="password" placeholder="<?= $h($t('u_password')) ?>" required minlength="3">
          <button class="btn ghost tiny" type="submit"><?= $h($t('save')) ?></button>
        </form>
        <form method="post" class="inline" onsubmit="return confirm('<?= $h($t('u_confirm_del')) ?>');">
          <input type="hidden" name="do" value="user_delete">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button class="btn danger tiny" type="submit"><?= $h($t('u_delete')) ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="card">
  <h3><?= $h($t('u_add')) ?></h3>
  <form method="post">
    <input type="hidden" name="do" value="user_create">
    <div class="row">
      <label class="fld"><span><?= $h($t('u_username')) ?></span>
        <input type="text" name="username" required></label>
      <label class="fld"><span><?= $h($t('u_password')) ?></span>
        <input type="password" name="password" required minlength="3"></label>
    </div>
    <div class="row">
      <label class="fld"><span><?= $h($t('u_fullname')) ?></span>
        <input type="text" name="full_name"></label>
      <label class="fld"><span><?= $h($t('u_email')) ?></span>
        <input type="email" name="email"></label>
      <label class="fld"><span><?= $h($t('u_role')) ?></span>
        <select name="role">
          <option value="admin">admin</option>
          <option value="operator">operator</option>
        </select></label>
    </div>
    <button class="btn" type="submit"><?= svg('check') ?> <?= $h($t('u_add')) ?></button>
  </form>
</div>
