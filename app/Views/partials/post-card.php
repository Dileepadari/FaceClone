<?php
/**
 * One post in a feed.
 * @var array $post   Hydrated post row from Post::hydrate()
 * @var array $__user Viewer
 */
$me        = $__user ?? current_user();
$author    = $post['author'];
$group     = $post['group'] ?? null;
$summary   = $post['reactions'];
$mine      = $summary['mine'] ?? null;
$types     = reaction_types();
$hasMedia  = !empty($post['media']);
$bg        = background_style($post['background'] ?? null);
$isBigText = !$hasMedia && $bg === '' && empty($post['shared']) && mb_strlen((string) $post['content']) <= 90;
$postUrl   = '/posts/' . $post['id'];
?>
<article class="card post" data-post-id="<?= (int) $post['id'] ?>">
  <header class="post-head">
    <a href="<?= e(profile_url($author)) ?>">
      <img class="avatar avatar-40" src="<?= e(avatar_url($author)) ?>" alt="<?= e(full_name($author)) ?>">
    </a>
    <div class="grow" style="min-width:0">
      <div>
        <a class="post-author" href="<?= e(profile_url($author)) ?>"><?= e(full_name($author)) ?></a>
        <?php if ($group): ?>
          <span class="muted small">&rsaquo;</span>
          <a class="post-author" href="/g/<?= e($group['slug']) ?>"><?= e($group['name']) ?></a>
        <?php endif; ?>
        <?php if (!empty($post['feeling'])): ?>
          <span class="muted"> is feeling <span class="bold" style="color:var(--text)"><?= e($post['feeling']) ?></span></span>
        <?php endif; ?>
        <?php if (!empty($post['location'])): ?>
          <span class="muted"> in <span class="bold" style="color:var(--text)"><?= e($post['location']) ?></span></span>
        <?php endif; ?>
      </div>
      <div class="post-meta">
        <a href="<?= e($postUrl) ?>" title="<?= e(full_datetime($post['created_at'])) ?>"><?= e(time_ago($post['created_at'])) ?></a>
        <?php if (!empty($post['edited_at'])): ?><span>&middot; Edited</span><?php endif; ?>
        <span>&middot;</span>
        <span title="<?= e(privacy_label($post['privacy'])) ?>"><?= icon(privacy_icon($post['privacy']), 12) ?></span>
      </div>
    </div>

    <div class="dropdown">
      <button class="icon-btn" style="background:none;width:36px;height:36px" type="button"
              data-dropdown="post-menu-<?= (int) $post['id'] ?>"
              aria-controls="post-menu-<?= (int) $post['id'] ?>" aria-expanded="false"
              aria-label="Post options"><?= icon('more', 20) ?></button>
      <div class="dropdown-panel narrow" id="post-menu-<?= (int) $post['id'] ?>">
        <button class="menu-item" type="button" data-save-post="<?= (int) $post['id'] ?>">
          <span class="menu-glyph"><?= icon('bookmark', 18) ?></span>
          <span class="grow" data-save-label><?= !empty($post['is_saved']) ? 'Remove from saved' : 'Save post' ?></span>
        </button>
        <a class="menu-item" href="<?= e($postUrl) ?>">
          <span class="menu-glyph"><?= icon('info', 18) ?></span><span class="grow">Open post</span>
        </a>
        <?php if ((int) $post['user_id'] === (int) $me['id']): ?>
          <button class="menu-item" type="button" data-modal-open="edit-post-<?= (int) $post['id'] ?>">
            <span class="menu-glyph"><?= icon('edit', 18) ?></span><span class="grow">Edit post</span>
          </button>
        <?php endif; ?>
        <?php if (!empty($post['can_edit'])): ?>
          <button class="menu-item is-danger" type="button" data-delete-post="<?= (int) $post['id'] ?>">
            <span class="menu-glyph"><?= icon('trash', 18) ?></span><span class="grow">Delete post</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if ($bg !== '' && trim((string) $post['content']) !== ''): ?>
    <div class="post-bg" style="background:<?= e($bg) ?>"><?= rich_text($post['content']) ?></div>
  <?php elseif (trim((string) $post['content']) !== ''): ?>
    <div class="post-body">
      <div class="post-text<?= $isBigText ? ' is-big' : '' ?>"><?= rich_text($post['content']) ?></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($post['shared'])): ?>
    <?= view_partial('partials/post-shared', ['shared' => $post['shared']]) ?>
  <?php endif; ?>

  <?php if ($hasMedia): ?>
    <?= view_partial('partials/post-media', ['media' => $post['media'], 'postUrl' => $postUrl]) ?>
  <?php endif; ?>

  <?php if ($summary['total'] > 0 || $post['comment_count'] > 0 || $post['share_count'] > 0): ?>
  <div class="post-stats">
    <div class="row" data-reaction-summary<?= $summary['total'] === 0 ? ' class="hidden"' : '' ?>>
      <?php if ($summary['total'] > 0): ?>
        <span class="reaction-chips" data-reaction-chips>
          <?php foreach ($summary['top'] as $type): ?>
            <span class="reaction-chip"><?= $types[$type]['emoji'] ?? '👍' ?></span>
          <?php endforeach; ?>
        </span>
        <button type="button" data-reactors="/posts/<?= (int) $post['id'] ?>/reactions">
          <span data-reaction-count><?= number_short((int) $summary['total']) ?></span>
        </button>
      <?php endif; ?>
    </div>
    <div class="row">
      <?php if ($post['comment_count'] > 0): ?>
        <button type="button" data-load-comments data-comment-count>
          <?= (int) $post['comment_count'] ?> <?= $post['comment_count'] === 1 ? 'comment' : 'comments' ?>
        </button>
      <?php endif; ?>
      <?php if ($post['share_count'] > 0): ?>
        <span><?= (int) $post['share_count'] ?> <?= $post['share_count'] === 1 ? 'share' : 'shares' ?></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="post-actions">
    <div class="grow" style="position:relative;display:flex" data-reaction-holder
         data-react-url="/posts/<?= (int) $post['id'] ?>/react">
      <div class="reaction-bar">
        <?php foreach ($types as $key => $meta): ?>
          <button type="button" data-react="<?= e($key) ?>" title="<?= e($meta['label']) ?>"
                  aria-label="React <?= e($meta['label']) ?>"><?= $meta['emoji'] ?></button>
        <?php endforeach; ?>
      </div>
      <button class="post-action grow<?= $mine ? ' is-reacted' : '' ?>" type="button"
              data-reaction-button data-react="<?= $mine ? e($mine) : 'like' ?>"
              data-reaction="<?= $mine ? e($mine) : '' ?>"
              data-default-glyph='<?= icon('like-outline', 18, 'like-glyph') ?>'>
        <?php if ($mine): ?>
          <span class="emoji" data-reaction-glyph><?= $types[$mine]['emoji'] ?></span>
        <?php else: ?>
          <?= str_replace('<svg', '<svg data-reaction-glyph', icon('like-outline', 18)) ?>
        <?php endif; ?>
        <span data-reaction-label><?= $mine ? e($types[$mine]['label']) : 'Like' ?></span>
      </button>
    </div>

    <button class="post-action" type="button" data-load-comments>
      <?= icon('comment', 18) ?> <span>Comment</span>
    </button>

    <button class="post-action" type="button" data-modal-open="share-post-<?= (int) $post['id'] ?>">
      <?= icon('share', 18) ?> <span>Share</span>
    </button>
  </div>

  <div class="comment-area">
    <div data-comment-list<?= $post['comment_count'] > 0 ? '' : ' data-loaded="1"' ?>></div>
  </div>

  <form class="comment-form<?= $post['comment_count'] > 0 ? '' : ' hidden' ?>" data-comment-form
        method="post" action="/comments" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
    <img class="avatar avatar-32" src="<?= e(avatar_url($me)) ?>" alt="">
    <div class="comment-input-wrap">
      <textarea class="comment-input" name="content" rows="1"
                placeholder="Write a comment…" aria-label="Write a comment"></textarea>
      <div data-comment-preview class="hidden mt-8"></div>
      <div class="comment-tools">
        <label title="Add a photo">
          <?= icon('camera', 18) ?>
          <input type="file" name="image" accept="image/*" hidden
                 data-file-preview="[data-post-id='<?= (int) $post['id'] ?>'] [data-comment-preview]">
        </label>
        <button class="send-btn" type="submit" aria-label="Post comment"><?= icon('send', 18) ?></button>
      </div>
    </div>
  </form>
</article>

<?= view_partial('partials/share-modal', ['post' => $post, '__user' => $me]) ?>
<?php if ((int) $post['user_id'] === (int) $me['id']): ?>
  <?= view_partial('partials/edit-post-modal', ['post' => $post]) ?>
<?php endif; ?>
