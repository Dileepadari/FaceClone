<?php
/**
 * The media grid. Facebook shows at most 4 tiles and stacks a "+n" scrim on
 * the last one when there are more.
 * @var array  $media
 * @var string $postUrl
 */
$count   = count($media);
$visible = array_slice($media, 0, 4);
$extra   = $count - count($visible);
$class   = match (true) {
    $count === 1 => 'post-media-1',
    $count === 2 => 'post-media-2',
    $count === 3 => 'post-media-3',
    $count === 4 => 'post-media-4',
    default      => 'post-media-many',
};
?>
<div class="post-media <?= $class ?>">
  <?php foreach ($visible as $i => $item): ?>
    <?php $isLast = ($i === count($visible) - 1) && $extra > 0; ?>
    <?php if ($item['type'] === 'video'): ?>
      <video src="<?= e($item['path']) ?>" controls preload="metadata" playsinline></video>
    <?php else: ?>
      <a href="<?= e($item['path']) ?>" data-lightbox="<?= e($item['path']) ?>"
         <?= $isLast ? 'class="post-media-more" data-more="+' . $extra . '"' : '' ?>>
        <img src="<?= e($item['path']) ?>" alt="Photo in post" loading="lazy">
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
