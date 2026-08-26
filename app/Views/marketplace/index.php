<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/marketplace-nav', ['categories' => $categories, 'category' => $category]) ?>
  </aside>

  <div class="column-main" style="max-width:1000px">
    <div class="spread mb-16">
      <h1 class="page-title" style="margin:0">
        <?= $category !== '' ? e($categories[$category] ?? 'Marketplace') : "Today's picks" ?>
      </h1>
      <a class="btn btn-primary" href="/marketplace/create"><?= icon('plus', 16) ?> Sell something</a>
    </div>

    <form class="search-pill mb-16" method="get" action="/marketplace" style="max-width:none">
      <?= icon('search', 16) ?>
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search Marketplace" aria-label="Search Marketplace">
      <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
    </form>

    <?php if (!$listings): ?>
      <div class="card"><div class="empty-state">
        <?= icon('marketplace', 40) ?><h3>Nothing for sale here yet</h3>
        <p>Be the first to list something in this category.</p>
        <a class="btn btn-primary mt-16" href="/marketplace/create">Create a listing</a>
      </div></div>
    <?php else: ?>
      <div class="listing-grid">
        <?php foreach ($listings as $listing): ?>
          <a class="card-tile listing-card" href="/marketplace/item/<?= (int) $listing['id'] ?>" style="color:inherit">
            <div class="tile-media">
              <?php if (!empty($listing['image'])): ?><img src="<?= e($listing['image']) ?>" alt="" loading="lazy"><?php endif; ?>
            </div>
            <div class="tile-body">
              <span class="listing-price"><?= (float) $listing['price'] > 0 ? '$' . number_format((float) $listing['price'], 2) : 'Free' ?></span>
              <span class="small clamp-2"><?= e($listing['title']) ?></span>
              <?php if (!empty($listing['location'])): ?>
                <span class="tiny muted truncate"><?= e($listing['location']) ?></span>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
