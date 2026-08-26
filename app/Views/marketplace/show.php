<div class="layout-narrow" style="max-width:1000px">
  <div class="card">
    <div style="display:grid;grid-template-columns:1fr;gap:0">
      <?php if (!empty($listing['image'])): ?>
        <div style="background:#000;display:grid;place-items:center;max-height:520px;overflow:hidden">
          <a href="<?= e($listing['image']) ?>" data-lightbox="<?= e($listing['image']) ?>">
            <img src="<?= e($listing['image']) ?>" alt="<?= e($listing['title']) ?>" style="max-height:520px;object-fit:contain">
          </a>
        </div>
      <?php endif; ?>

      <div class="card-body">
        <h1 style="font-size:24px;font-weight:700"><?= e($listing['title']) ?></h1>
        <p class="listing-price mt-8" style="font-size:22px">
          <?= (float) $listing['price'] > 0 ? '$' . number_format((float) $listing['price'], 2) : 'Free' ?>
          <?php if ($listing['is_sold']): ?><span class="badge-sold" style="position:static;margin-left:8px">Sold</span><?php endif; ?>
        </p>
        <p class="small muted mt-8">
          Listed <?= e(time_ago($listing['created_at'])) ?>
          <?php if (!empty($listing['location'])): ?>&middot; <?= e($listing['location']) ?><?php endif; ?>
          &middot; <?= e($categories[$listing['category']] ?? 'Other') ?>
        </p>

        <?php if (!empty($listing['description'])): ?>
          <hr class="divider mt-16 mb-16">
          <h2 class="section-title">Description</h2>
          <p><?= nl2br(e($listing['description'])) ?></p>
        <?php endif; ?>

        <hr class="divider mt-16 mb-16">
        <h2 class="section-title mb-12">Seller</h2>
        <div class="spread">
          <a class="row" href="<?= e(profile_url($listing['seller'])) ?>" style="color:inherit">
            <img class="avatar avatar-48" src="<?= e(avatar_url($listing['seller'])) ?>" alt="">
            <span><span class="bold" style="display:block"><?= e(full_name($listing['seller'])) ?></span>
              <span class="small muted"><?= e($listing['seller']['city'] ?: 'FaceClone member') ?></span></span>
          </a>
          <?php if ($isSeller): ?>
            <div class="row">
              <form method="post" action="/marketplace/item/<?= (int) $listing['id'] ?>/sold">
                <?= csrf_field() ?>
                <button class="btn" type="submit"><?= $listing['is_sold'] ? 'Mark available' : 'Mark as sold' ?></button>
              </form>
              <form method="post" action="/marketplace/item/<?= (int) $listing['id'] ?>/delete">
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit" data-confirm="Delete this listing?">Delete</button>
              </form>
            </div>
          <?php else: ?>
            <a class="btn btn-primary" href="/messages/new/<?= (int) $listing['seller_id'] ?>">
              <?= icon('messenger', 16) ?> Message seller
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php $others = array_filter($related, static fn($r) => (int) $r['id'] !== (int) $listing['id']); ?>
  <?php if ($others): ?>
    <h2 class="section-title mb-12">Similar listings</h2>
    <div class="listing-grid">
      <?php foreach ($others as $item): ?>
        <a class="card-tile listing-card" href="/marketplace/item/<?= (int) $item['id'] ?>" style="color:inherit">
          <div class="tile-media">
            <?php if (!empty($item['image'])): ?><img src="<?= e($item['image']) ?>" alt="" loading="lazy"><?php endif; ?>
          </div>
          <div class="tile-body">
            <span class="listing-price"><?= (float) $item['price'] > 0 ? '$' . number_format((float) $item['price'], 2) : 'Free' ?></span>
            <span class="small clamp-2"><?= e($item['title']) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
