<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Members <span class="muted" style="font-weight:400">(<?= count($members) ?>)</span></h2>

      <?php foreach ($members as $member): ?>
        <?php $isSelfRow = (int) $member['id'] === (int) $viewer['id']; ?>
        <div class="spread" style="padding:10px 0;border-bottom:1px solid var(--divider-soft)">
          <a class="row" href="<?= e(profile_url($member)) ?>" style="color:inherit;min-width:0">
            <img class="avatar avatar-48" src="<?= e(avatar_url($member)) ?>" alt="">
            <span style="min-width:0">
              <span class="bold truncate" style="display:block"><?= e(full_name($member)) ?></span>
              <span class="small muted">
                <?= $member['role'] === 'admin' ? 'Admin' : ($member['role'] === 'moderator' ? 'Moderator' : 'Member') ?>
                &middot; joined <?= e(time_ago($member['joined_at'])) ?>
              </span>
            </span>
          </a>

          <?php if ($isAdmin && !$isSelfRow): ?>
            <div class="row">
              <form method="post" action="/g/<?= e($group['slug']) ?>/role/<?= (int) $member['id'] ?>">
                <?= csrf_field() ?>
                <select class="select btn-sm" name="role" style="width:auto;padding:6px 10px"
                        onchange="this.form.requestSubmit()" aria-label="Role for <?= e(full_name($member)) ?>">
                  <?php foreach (['member' => 'Member', 'moderator' => 'Moderator', 'admin' => 'Admin'] as $v => $label): ?>
                    <option value="<?= $v ?>" <?= $member['role'] === $v ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php if ((int) $member['id'] !== (int) $group['creator_id']): ?>
                <form method="post" action="/g/<?= e($group['slug']) ?>/remove/<?= (int) $member['id'] ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm" type="submit"
                          data-confirm="Remove <?= e(full_name($member)) ?> from the group?">Remove</button>
                </form>
              <?php endif; ?>
            </div>
          <?php elseif (!$isSelfRow): ?>
            <a class="btn btn-sm btn-soft" href="/messages/new/<?= (int) $member['id'] ?>">Message</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
