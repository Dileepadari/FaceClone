<div class="layout-narrow">
  <div class="spread mb-16">
    <h1 class="page-title" style="margin:0">Stories</h1>
    <a class="btn btn-primary" href="/stories/create"><?= icon('plus', 16) ?> Create story</a>
  </div>

  <?php if (!$trays): ?>
    <div class="card"><div class="empty-state">
      <?= icon('reels', 40) ?><h3>No stories right now</h3>
      <p>Stories disappear after 24 hours. Post one and your friends will see it here.</p>
      <a class="btn btn-primary mt-16" href="/stories/create">Create your story</a>
    </div></div>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <div class="story-rail" style="flex-wrap:wrap;gap:12px">
          <?php foreach ($trays as $tray): ?>
            <?php
              $latest = end($tray['stories']);
              $bg     = background_style($latest['background'] ?? null);
            ?>
            <a class="story-card" style="width:140px;height:250px" href="/stories/user/<?= (int) $tray['user']['id'] ?>">
              <?php if ($latest['type'] === 'photo' && $latest['media']): ?>
                <img class="story-bg" src="<?= e($latest['media']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <div class="story-text-bg" style="background:<?= e($bg ?: 'linear-gradient(135deg,#7b4dff,#e356a7)') ?>">
                  <?= e(mb_substr((string) $latest['text'], 0, 80)) ?>
                </div>
              <?php endif; ?>
              <span class="story-scrim"></span>
              <span class="story-ring<?= $tray['all_seen'] ? ' is-seen' : '' ?>">
                <img src="<?= e(avatar_url($tray['user'])) ?>" alt="">
              </span>
              <span class="story-name">
                <?= e($tray['is_self'] ? 'Your story' : full_name($tray['user'])) ?><br>
                <span class="tiny" style="font-weight:400;opacity:.85">
                  <?= count($tray['stories']) ?> <?= count($tray['stories']) === 1 ? 'story' : 'stories' ?>
                </span>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
