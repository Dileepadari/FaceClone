<?php
/**
 * Body of the "who reacted" dialog: filter tabs plus the reactor list.
 * @var array  $reactors
 * @var array  $summary
 * @var string $active
 * @var int    $postId
 */
$types = reaction_types();
?>
<div class="chip-row mb-16">
  <a class="chip<?= $active === '' ? ' is-active' : '' ?>" href="#"
     data-reactor-filter="/posts/<?= (int) $postId ?>/reactions">All <?= (int) $summary['total'] ?></a>
  <?php foreach ($summary['counts'] ?? [] as $type => $n): ?>
    <a class="chip<?= $active === $type ? ' is-active' : '' ?>" href="#"
       data-reactor-filter="/posts/<?= (int) $postId ?>/reactions?type=<?= e($type) ?>">
      <?= $types[$type]['emoji'] ?? '' ?> <?= (int) $n ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$reactors): ?>
  <p class="muted center">No reactions yet.</p>
<?php else: ?>
  <?php foreach ($reactors as $person): ?>
    <a class="friend-tile" href="<?= e(profile_url($person)) ?>">
      <span style="position:relative;flex:none">
        <img class="avatar avatar-40" src="<?= e(avatar_url($person)) ?>" alt="">
        <span style="position:absolute;right:-2px;bottom:-2px;font-size:15px"><?= $types[$person['type']]['emoji'] ?? '' ?></span>
      </span>
      <span class="grow bold"><?= e(full_name($person)) ?></span>
    </a>
  <?php endforeach; ?>
<?php endif; ?>
