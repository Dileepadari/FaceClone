<?php
/**
 * The signed-in application shell: top bar, page content, mobile nav.
 * @var string $content
 */
?>
<!doctype html>
<html lang="en">
<head><?= view_partial('layouts/head', ['title' => $title ?? null, '__user' => $__user ?? null]) ?></head>
<body>
<?= view_partial('partials/topbar', ['__user' => $__user ?? null, '__chrome' => $__chrome ?? []]) ?>

<main class="shell" id="main">
  <?= $content ?>
</main>

<?= view_partial('partials/bottom-nav', ['__chrome' => $__chrome ?? []]) ?>
<?= view_partial('partials/flash', ['__flashes' => $__flashes ?? []]) ?>

<div class="modal-backdrop" id="reactors-modal" role="dialog" aria-modal="true" aria-label="Reactions">
  <div class="modal">
    <div class="modal-head">
      <h2>Reactions</h2>
      <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button>
    </div>
    <div class="modal-body"></div>
  </div>
</div>

<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
