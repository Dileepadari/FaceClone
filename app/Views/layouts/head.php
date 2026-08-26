<?php
/**
 * Shared <head> contents plus the theme bootstrap.
 * @var string|null $title
 */
use App\Models\UserSetting;

$__me    = $__user ?? current_user();
$__theme = $__me ? UserSetting::forUser((int) $__me['id'])['theme'] : 'light';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1877f2">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="description" content="FaceClone is a social network for sharing posts, stories, photos and messages with friends.">
<title><?= e($title ?? 'FaceClone') ?></title>
<link rel="icon" href="<?= asset('assets/img/logo-mark.png') ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= asset('assets/img/logo-mark.png') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
<script>
  /* Resolve the theme before first paint so there is no light flash. */
  (function () {
    var pref = <?= json_encode($__theme) ?>;
    try { pref = localStorage.getItem('fc-theme') || pref; } catch (e) {}
    var resolved = pref === 'system'
      ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : pref;
    document.documentElement.setAttribute('data-theme', resolved);
    document.documentElement.dataset.themePref = pref;
  })();
</script>
