<?php /** A single post with its full comment thread already expanded. */ ?>
<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/left-rail', ['__user' => $__user, 'myGroups' => []]) ?>
  </aside>

  <div class="column-main">
    <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>

    <script>
      /* The thread is already known here, so open it without a round trip. */
      document.addEventListener('DOMContentLoaded', function () {
        var card = document.querySelector('[data-post-id]');
        if (!card) return;
        card.querySelector('[data-comment-form]')?.classList.remove('hidden');
        var list = card.querySelector('[data-comment-list]');
        if (list) list.innerHTML = <?= json_encode(implode('', array_map(
            static fn(array $c) => view_partial('partials/comment', ['comment' => $c, 'postId' => (int) $post['id'], '__user' => $__user]),
            $comments
        ))) ?>;
        if (list) list.dataset.loaded = '1';
      });
    </script>
  </div>

  <aside class="rail rail-right"></aside>
</div>
