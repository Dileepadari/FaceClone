<?php
$tabs = ['all' => 'All', 'people' => 'People', 'posts' => 'Posts', 'groups' => 'Groups'];
?>
<div class="layout-3col">
  <aside class="rail rail-left">
    <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Search results</div>
    <?php foreach ($tabs as $key => $label): ?>
      <a class="rail-link<?= $tab === $key ? ' is-active' : '' ?>"
         href="/search?q=<?= urlencode($term) ?>&amp;tab=<?= $key ?>">
        <span class="rail-glyph">
          <?= icon(['all' => 'search', 'people' => 'users', 'posts' => 'edit', 'groups' => 'groups'][$key], 20) ?>
        </span> <?= $label ?>
      </a>
    <?php endforeach; ?>
  </aside>

  <div class="column-main" style="max-width:680px">
    <?php if ($term === ''): ?>
      <div class="card">
        <div class="card-body">
          <h1 class="page-title">Search FaceClone</h1>
          <p class="muted">Look for people, posts and groups using the box at the top of the page.</p>
          <?php if ($recent): ?>
            <h2 class="section-title mt-16">Your friends</h2>
            <?php foreach ($recent as $person): ?>
              <a class="friend-tile" href="<?= e(profile_url($person)) ?>">
                <img class="avatar avatar-40" src="<?= e(avatar_url($person)) ?>" alt="">
                <span class="grow bold"><?= e(full_name($person)) ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <h1 class="page-title">Results for &ldquo;<?= e($term) ?>&rdquo;</h1>

      <?php if (!$people && !$posts && !$groups): ?>
        <div class="card"><div class="empty-state">
          <?= icon('search', 40) ?><h3>No results found</h3>
          <p>We could not find anything matching &ldquo;<?= e($term) ?>&rdquo;. Try a different spelling or keyword.</p>
        </div></div>
      <?php endif; ?>

      <?php if ($people): ?>
        <div class="card">
          <div class="card-head"><h2 style="font-size:17px">People</h2>
            <?php if ($tab === 'all'): ?>
              <a class="small" href="/search?q=<?= urlencode($term) ?>&amp;tab=people">See all</a>
            <?php endif; ?>
          </div>
          <hr class="divider">
          <div class="card-body">
            <?php foreach ($people as $person): ?>
              <div class="spread" style="padding:8px 0" data-person-card>
                <a class="row" href="<?= e(profile_url($person)) ?>" style="color:inherit;min-width:0">
                  <img class="avatar avatar-60" src="<?= e(avatar_url($person)) ?>" alt="">
                  <span style="min-width:0">
                    <span class="bold truncate" style="display:block"><?= e(full_name($person)) ?></span>
                    <span class="small muted"><?= e($person['city'] ?: '@' . $person['username']) ?></span>
                  </span>
                </a>
                <?php $status = \App\Models\Friendship::status((int) $__user['id'], (int) $person['id']); ?>
                <?php if ($status === \App\Models\Friendship::FRIENDS): ?>
                  <a class="btn btn-sm btn-soft" href="/messages/new/<?= (int) $person['id'] ?>">Message</a>
                <?php elseif ($status === \App\Models\Friendship::SENT): ?>
                  <span class="btn btn-sm" style="cursor:default">Request sent</span>
                <?php elseif ($status === \App\Models\Friendship::SELF): ?>
                  <a class="btn btn-sm" href="<?= e(profile_url($person)) ?>">Your profile</a>
                <?php else: ?>
                  <button class="btn btn-primary btn-sm" type="button"
                          data-friend-action="/friends/<?= (int) $person['id'] ?>/request"
                          data-replace-with='<span class="btn btn-sm">Request sent</span>'>Add friend</button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($groups): ?>
        <div class="card">
          <div class="card-head"><h2 style="font-size:17px">Groups</h2>
            <?php if ($tab === 'all'): ?>
              <a class="small" href="/search?q=<?= urlencode($term) ?>&amp;tab=groups">See all</a>
            <?php endif; ?>
          </div>
          <hr class="divider">
          <div class="card-body">
            <?php foreach ($groups as $group): ?>
              <a class="friend-tile" href="/g/<?= e($group['slug']) ?>">
                <?php if (!empty($group['cover'])): ?>
                  <img class="avatar avatar-60" style="border-radius:8px" src="<?= e($group['cover']) ?>" alt="">
                <?php else: ?>
                  <span class="rail-glyph" style="border-radius:8px;width:60px;height:60px"><?= icon('groups', 24) ?></span>
                <?php endif; ?>
                <span class="grow" style="min-width:0">
                  <span class="bold truncate" style="display:block"><?= e($group['name']) ?></span>
                  <span class="small muted">
                    <?= $group['privacy'] === 'private' ? 'Private' : 'Public' ?> group
                    &middot; <?= number_short((int) $group['member_count']) ?> members
                  </span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($posts): ?>
        <h2 class="section-title">Posts</h2>
        <?php foreach ($posts as $post): ?>
          <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>
        <?php endforeach; ?>
        <?php if ($tab === 'all'): ?>
          <p class="center"><a href="/search?q=<?= urlencode($term) ?>&amp;tab=posts">See all matching posts</a></p>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
