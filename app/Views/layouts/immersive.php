<?php /** Full-bleed shell with no chrome, used by the story viewer. */ ?>
<!doctype html>
<html lang="en">
<head><?= view_partial('layouts/head', ['title' => $title ?? null, '__user' => $__user ?? null]) ?></head>
<body style="background:#18191a">
<?= $content ?>
<?= view_partial('partials/flash', ['__flashes' => $__flashes ?? []]) ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
