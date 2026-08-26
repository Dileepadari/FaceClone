<?php /** Minimal shell for error pages. */ ?>
<!doctype html>
<html lang="en">
<head><?= view_partial('layouts/head', ['title' => $title ?? 'FaceClone']) ?></head>
<body>
<?php if (current_user()): ?>
  <?= view_partial('partials/topbar', ['__user' => current_user(), '__chrome' => $__chrome ?? []]) ?>
  <main class="shell"><?= $content ?></main>
<?php else: ?>
  <main><?= $content ?></main>
<?php endif; ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
