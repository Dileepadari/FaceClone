<?php /** @var array $__flashes */ ?>
<?php if (!empty($__flashes)): ?>
<div class="toast-stack">
  <?php foreach ($__flashes as $flash): ?>
    <div class="toast is-<?= e($flash['type']) ?>" role="status">
      <div class="grow"><?= e($flash['message']) ?></div>
      <button type="button" onclick="this.parentNode.remove()" aria-label="Dismiss">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
<script>setTimeout(function(){document.querySelectorAll('.toast').forEach(function(t){t.remove();});}, 6000);</script>
<?php endif; ?>
