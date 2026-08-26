<?php
/**
 * Grid card used by Friends, Suggestions and Search.
 * @var array  $person
 * @var string $primaryAction   HTML for the main button
 * @var string $secondaryAction HTML for the secondary button (optional)
 * @var string $subtitle
 */
?>
<div class="person-card" data-person-card>
  <a class="person-photo" href="<?= e(profile_url($person)) ?>">
    <img src="<?= e(avatar_url($person)) ?>" alt="<?= e(full_name($person)) ?>" loading="lazy">
  </a>
  <div class="person-body">
    <a class="person-name truncate" style="display:block;color:var(--text)" href="<?= e(profile_url($person)) ?>">
      <?= e(full_name($person)) ?>
    </a>
    <?php if (!empty($subtitle)): ?>
      <div class="tiny muted truncate"><?= e($subtitle) ?></div>
    <?php endif; ?>
    <div class="stack mt-8">
      <?= $primaryAction ?? '' ?>
      <?= $secondaryAction ?? '' ?>
    </div>
  </div>
</div>
