<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/marketplace-nav', ['categories' => $categories, 'category' => '']) ?>
  </aside>

  <div class="column-main" style="max-width:1000px">
    <div class="spread mb-16">
      <h1 class="page-title" style="margin:0">Your listings</h1>
      <a class="btn btn-primary" href="/marketplace/create"><?= icon('plus', 16) ?> New listing</a>
    </div>

    <?php if (!$listings): ?>
      <div class="card"><div class="empty-state">
        <?= icon('tag', 40) ?><h3>You have not listed anything</h3>
        <p>Sell something you no longer need.</p>
        <a class="btn btn-primary mt-16" href="/marketplace/create">Create a listing</a>
      </div></div>
    <?php else: ?>
      <div class="listing-grid">
        <?php foreach ($listings as $listing): ?>
          <div class="card-tile listing-card">
            <a class="tile-media" href="/marketplace/item/<?= (int) $listing['id'] ?>">
              <?php if (!empty($listing['image'])): ?><img src="<?= e($listing['image']) ?>" alt="" loading="lazy"><?php endif; ?>
              <?php if ($listing['is_sold']): ?><span class="badge-sold">Sold</span><?php endif; ?>
            </a>
            <div class="tile-body">
              <span class="listing-price"><?= (float) $listing['price'] > 0 ? '$' . number_format((float) $listing['price'], 2) : 'Free' ?></span>
              <a class="small clamp-2" href="/marketplace/item/<?= (int) $listing['id'] ?>" style="color:inherit"><?= e($listing['title']) ?></a>
              <div class="row mt-8">
                <form method="post" action="/marketplace/item/<?= (int) $listing['id'] ?>/sold" class="grow">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-block" type="submit"><?= $listing['is_sold'] ? 'Available' : 'Sold' ?></button>
                </form>
                <form method="post" action="/marketplace/item/<?= (int) $listing['id'] ?>/delete">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm" type="submit" data-confirm="Delete this listing?"><?= icon('trash', 14) ?></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
