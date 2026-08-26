<?php
/**
 * Cover photo, avatar, name, relationship buttons and tab strip.
 * Shared by every profile tab.
 */
$isSelf = $isSelf ?? false;
$cover  = cover_url($owner);
$tabs   = [
    'posts'   => ['Posts',   profile_url($owner)],
    'about'   => ['About',   profile_url($owner) . '/about'],
    'friends' => ['Friends', profile_url($owner) . '/friends'],
    'photos'  => ['Photos',  profile_url($owner) . '/photos'],
    'groups'  => ['Groups',  profile_url($owner) . '/groups'],
];
?>
<div class="profile-head">
  <div class="profile-head-inner">
    <div class="cover">
      <?php if ($cover): ?><img src="<?= e($cover) ?>" alt="Cover photo"><?php endif; ?>
      <?php if ($isSelf): ?>
        <form class="cover-edit" method="post" action="/profile/cover" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="file" id="cover-input" name="cover" accept="image/*" hidden data-auto-submit>
          <button class="btn" type="button" data-trigger-file="#cover-input">
            <?= icon('camera', 18) ?> <span class="btn-label"><?= $cover ? 'Edit cover photo' : 'Add cover photo' ?></span>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <div class="profile-identity">
      <div class="profile-avatar-wrap">
        <?php if ($hasStory ?? false): ?>
          <a href="/stories/user/<?= (int) $owner['id'] ?>" title="View story" style="display:block;border-radius:50%;padding:3px;background:var(--blue)">
            <img class="profile-avatar" src="<?= e(avatar_url($owner)) ?>" alt="<?= e(full_name($owner)) ?>">
          </a>
        <?php else: ?>
          <img class="profile-avatar" src="<?= e(avatar_url($owner)) ?>" alt="<?= e(full_name($owner)) ?>">
        <?php endif; ?>
        <?php if ($isSelf): ?>
          <form method="post" action="/profile/avatar" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="file" id="avatar-input" name="avatar" accept="image/*" hidden data-auto-submit>
            <button class="profile-avatar-edit" type="button" data-trigger-file="#avatar-input"
                    aria-label="Change profile photo"><?= icon('camera', 18) ?></button>
          </form>
        <?php endif; ?>
      </div>

      <div>
        <h1 class="profile-name"><?= e(full_name($owner)) ?></h1>
        <div class="profile-sub">
          <?= number_short((int) $friendCount) ?> <?= $friendCount === 1 ? 'friend' : 'friends' ?>
          <?php if (($mutualCount ?? 0) > 0): ?>
            &middot; <?= (int) $mutualCount ?> mutual
          <?php endif; ?>
        </div>
        <?php if (!empty($mutuals)): ?>
          <div class="profile-friend-strip">
            <?php foreach ($mutuals as $m): ?>
              <a href="<?= e(profile_url($m)) ?>" title="<?= e(full_name($m)) ?>">
                <img src="<?= e(avatar_url($m)) ?>" alt="<?= e(full_name($m)) ?>">
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="profile-cta">
        <?php if ($isSelf): ?>
          <button class="btn btn-primary" type="button" data-modal-open="composer-modal">
            <?= icon('plus', 16) ?> Add to story
          </button>
          <a class="btn" href="<?= e(profile_url($owner)) ?>/about"><?= icon('edit', 16) ?> Edit profile</a>
        <?php else: ?>
          <?php switch ($relationship):
                  case \App\Models\Friendship::FRIENDS: ?>
            <div class="dropdown">
              <button class="btn" type="button" data-dropdown="friend-menu" aria-controls="friend-menu" aria-expanded="false">
                <?= icon('check', 16) ?> Friends <?= icon('chevron-down', 14) ?>
              </button>
              <div class="dropdown-panel narrow" id="friend-menu">
                <button class="menu-item is-danger" type="button"
                        data-friend-action="/friends/<?= (int) $owner['id'] ?>/remove"
                        data-confirm="Remove <?= e(full_name($owner)) ?> from your friends?">
                  <span class="menu-glyph"><?= icon('trash', 18) ?></span><span class="grow">Unfriend</span>
                </button>
                <button class="menu-item is-danger" type="button"
                        data-friend-action="/friends/<?= (int) $owner['id'] ?>/block"
                        data-confirm="Block <?= e(full_name($owner)) ?>? They will not be able to see your profile or message you.">
                  <span class="menu-glyph"><?= icon('shield', 18) ?></span><span class="grow">Block</span>
                </button>
              </div>
            </div>
          <?php break; case \App\Models\Friendship::SENT: ?>
            <button class="btn" type="button" data-friend-action="/friends/<?= (int) $owner['id'] ?>/cancel"
                    data-replace-with='<a class="btn btn-primary" href="/friends/<?= (int) $owner['id'] ?>">Add friend</a>'>
              <?= icon('close', 16) ?> Cancel request
            </button>
          <?php break; case \App\Models\Friendship::RECEIVED: ?>
            <button class="btn btn-primary" type="button" data-friend-action="/friends/<?= (int) $owner['id'] ?>/accept"
                    data-replace-with='<span class="btn"><?= icon('check', 16) ?> Friends</span>'>
              <?= icon('check', 16) ?> Confirm request
            </button>
          <?php break; default: ?>
            <button class="btn btn-primary" type="button" data-friend-action="/friends/<?= (int) $owner['id'] ?>/request"
                    data-replace-with='<span class="btn"><?= icon('check', 16) ?> Request sent</span>'>
              <?= icon('plus', 16) ?> Add friend
            </button>
          <?php endswitch; ?>

          <button class="btn" type="button" data-friend-action="/friends/<?= (int) $owner['id'] ?>/follow">
            <?= icon('bell', 16) ?> <span data-follow-label><?= ($isFollowing ?? false) ? 'Following' : 'Follow' ?></span>
          </button>
          <a class="btn btn-primary" href="/messages/new/<?= (int) $owner['id'] ?>">
            <?= icon('messenger', 16) ?> Message
          </a>
        <?php endif; ?>
      </div>
    </div>

    <nav class="profile-tabs" aria-label="Profile sections">
      <?php foreach ($tabs as $key => [$label, $href]): ?>
        <a href="<?= e($href) ?>" class="<?= $tab === $key ? 'is-active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</div>
