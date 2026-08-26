<?php
/**
 * The "What's on your mind?" card plus its full composer dialog.
 * @var array    $__user
 * @var int|null $groupId    Set when posting inside a group
 * @var string   $placeholder
 */
$me      = $__user ?? current_user();
$groupId = $groupId ?? null;
$modalId = 'composer-modal' . ($groupId ? '-g' . $groupId : '');
$prompt  = $placeholder ?? ("What's on your mind, " . e($me['first_name']) . '?');
?>
<div class="card">
  <div class="composer-trigger">
    <a href="<?= e(profile_url($me)) ?>"><img class="avatar avatar-40" src="<?= e(avatar_url($me)) ?>" alt=""></a>
    <button class="composer-fake" type="button" data-modal-open="<?= e($modalId) ?>"><?= $prompt ?></button>
  </div>
  <div class="composer-actions">
    <button class="composer-action" type="button" data-modal-open="<?= e($modalId) ?>">
      <span class="g-live"><?= icon('videocall', 22) ?></span> <span class="composer-action-label">Live video</span>
    </button>
    <button class="composer-action" type="button" data-modal-open="<?= e($modalId) ?>">
      <span class="g-photo"><?= icon('photo', 22) ?></span> <span class="composer-action-label">Photo/video</span>
    </button>
    <button class="composer-action" type="button" data-modal-open="<?= e($modalId) ?>">
      <span class="g-feeling"><?= icon('emoji', 22) ?></span> <span class="composer-action-label">Feeling/activity</span>
    </button>
  </div>
</div>

<div class="modal-backdrop" id="<?= e($modalId) ?>" role="dialog" aria-modal="true" aria-label="Create post">
  <div class="modal" data-composer>
    <form method="post" action="/posts" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?php if ($groupId): ?><input type="hidden" name="group_id" value="<?= (int) $groupId ?>"><?php endif; ?>
      <input type="hidden" name="background" value="">

      <div class="modal-head">
        <h2>Create post</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button>
      </div>

      <div class="modal-body">
        <div class="row mb-12">
          <img class="avatar avatar-40" src="<?= e(avatar_url($me)) ?>" alt="">
          <div>
            <div class="bold"><?= e(full_name($me)) ?></div>
            <?php if ($groupId): ?>
              <span class="privacy-select"><?= icon('groups', 12) ?> Group members</span>
            <?php else: ?>
              <select class="privacy-select" name="privacy" aria-label="Post audience">
                <option value="friends">Friends</option>
                <option value="public">Public</option>
                <option value="only_me">Only me</option>
              </select>
            <?php endif; ?>
          </div>
        </div>

        <textarea class="composer-textarea" name="content" data-composer-text data-autofocus
                  placeholder="<?= $prompt ?>" aria-label="Post text"></textarea>

        <div class="bg-picker" data-bg-picker>
          <button class="bg-swatch bg-none is-active" type="button" data-bg="" title="No background">Aa</button>
          <?php foreach (post_backgrounds() as $key => $css): ?>
            <button class="bg-swatch" type="button" data-bg="<?= e($key) ?>" data-css="<?= e($css) ?>"
                    style="background:<?= e($css) ?>" aria-label="Background <?= e($key) ?>"></button>
          <?php endforeach; ?>
        </div>

        <div class="composer-tray hidden" data-composer-tray>
          <div class="composer-previews" data-composer-previews></div>
        </div>

        <div class="field-row mt-12">
          <input class="input" type="text" name="feeling" placeholder="Feeling or activity (optional)" maxlength="60">
          <input class="input" type="text" name="location" placeholder="Add a location (optional)" maxlength="120">
        </div>

        <div class="composer-options">
          <span class="bold small">Add to your post</span>
          <div class="composer-option-icons">
            <button type="button" title="Photo or video"
                    data-trigger-file="#<?= e($modalId) ?>-files"><span class="g-photo" style="color:#45bd62"><?= icon('photo', 22) ?></span></button>
            <button type="button" title="Tag a location" onclick="this.closest('.modal-body').querySelector('[name=location]').focus()">
              <span style="color:#f5533d"><?= icon('location', 22) ?></span>
            </button>
            <button type="button" title="Feeling" onclick="this.closest('.modal-body').querySelector('[name=feeling]').focus()">
              <span style="color:#f7b928"><?= icon('emoji', 22) ?></span>
            </button>
          </div>
        </div>

        <input type="file" id="<?= e($modalId) ?>-files" name="media[]" accept="image/*,video/*"
               multiple hidden data-composer-files>
      </div>

      <div class="modal-foot">
        <button class="btn btn-primary btn-block btn-lg" type="submit" data-composer-submit disabled>Post</button>
      </div>
    </form>
  </div>
</div>
