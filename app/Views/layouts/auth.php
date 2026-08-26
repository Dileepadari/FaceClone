<?php /** Signed-out shell for login, register and password recovery. */ ?>
<!doctype html>
<html lang="en">
<head><?= view_partial('layouts/head', ['title' => $title ?? null]) ?></head>
<body>
<div class="auth-page">
  <?= $content ?>
  <footer class="auth-foot">
    <p><strong>FaceClone</strong> &middot; A full-stack social network demo built with PHP and MariaDB</p>
    <p class="mt-8">Posts &middot; Stories &middot; Groups &middot; Messenger &middot; Marketplace &middot; Events</p>
  </footer>
</div>
<?= view_partial('partials/flash', ['__flashes' => $__flashes ?? []]) ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
