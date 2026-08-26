<?php /** Messenger shell: top bar plus a full-height two pane layout. */ ?>
<!doctype html>
<html lang="en">
<head><?= view_partial('layouts/head', ['title' => $title ?? null, '__user' => $__user ?? null]) ?></head>
<body>
<?= view_partial('partials/topbar', ['__user' => $__user ?? null, '__chrome' => $__chrome ?? []]) ?>
<main class="shell" style="padding-bottom:0"><?= $content ?></main>
<?= view_partial('partials/flash', ['__flashes' => $__flashes ?? []]) ?>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
