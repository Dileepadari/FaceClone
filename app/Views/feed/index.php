<?php
/**
 * Home feed: three columns with stories, composer and an infinite post list.
 */
?>
<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/left-rail', ['__user' => $__user, 'myGroups' => $myGroups]) ?>
  </aside>

  <div class="column-main">
    <?= view_partial('partials/story-rail', ['trays' => $trays, '__user' => $__user]) ?>
    <?= view_partial('partials/composer', ['__user' => $__user]) ?>

    <div data-feed data-next-offset="<?= (int) $nextOffset ?>" data-has-more="<?= count($posts) >= 8 ? '1' : '0' ?>">
      <?php foreach ($posts as $post): ?>
        <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>
      <?php endforeach; ?>
    </div>

    <?php if (!$posts): ?>
      <div class="card">
        <div class="empty-state">
          <?= icon('home', 40) ?>
          <h3>Your feed is quiet</h3>
          <p>Add some friends or write your first post and it will show up here.</p>
          <div class="row mt-16" style="justify-content:center">
            <a class="btn btn-primary" href="/friends/suggestions">Find friends</a>
            <a class="btn" href="/groups/discover">Discover groups</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div data-feed-sentinel></div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right">
    <?= view_partial('partials/right-rail', [
      'requests'    => $requests,
      'suggestions' => $suggestions,
      'events'      => $events,
      'contacts'    => $contacts,
    ]) ?>
  </aside>
</div>
