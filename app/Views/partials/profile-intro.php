<?php
/**
 * The Intro card on a profile sidebar, with inline editing for the owner.
 * @var array $owner
 * @var bool  $isSelf
 */
$relationships = [
    'single'            => 'Single',
    'in_a_relationship' => 'In a relationship',
    'engaged'           => 'Engaged',
    'married'           => 'Married',
    'complicated'       => "It's complicated",
    'private'           => 'Not shown',
];
?>
<div class="card">
  <div class="card-body">
    <h2 class="section-title">Intro</h2>

    <?php if (!empty($owner['bio'])): ?>
      <p class="center" style="padding:4px 0 12px"><?= e($owner['bio']) ?></p>
      <hr class="divider">
    <?php endif; ?>

    <div class="mt-12">
      <?php if (!empty($owner['work'])): ?>
        <div class="intro-row"><?= icon('marketplace', 18) ?> <span><?= e($owner['work']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($owner['education'])): ?>
        <div class="intro-row"><?= icon('help', 18) ?> <span>Studied <?= e($owner['education']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($owner['city'])): ?>
        <div class="intro-row"><?= icon('location', 18) ?> <span>Lives in <?= e($owner['city']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($owner['hometown']) && $owner['hometown'] !== $owner['city']): ?>
        <div class="intro-row"><?= icon('pin', 18) ?> <span>From <?= e($owner['hometown']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($owner['relationship']) && $owner['relationship'] !== 'private'): ?>
        <div class="intro-row"><?= icon('like', 18) ?> <span><?= e($relationships[$owner['relationship']]) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($owner['website'])): ?>
        <div class="intro-row"><?= icon('globe', 18) ?>
          <a href="<?= e($owner['website']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="truncate"><?= e($owner['website']) ?></a>
        </div>
      <?php endif; ?>
      <div class="intro-row"><?= icon('calendar', 18) ?> <span>Joined <?= date('F Y', strtotime($owner['created_at'])) ?></span></div>
    </div>

    <?php if ($isSelf): ?>
      <button class="btn btn-block mt-12" type="button" data-modal-open="intro-modal">Edit details</button>
    <?php endif; ?>
  </div>
</div>

<?php if ($isSelf): ?>
<div class="modal-backdrop" id="intro-modal" role="dialog" aria-modal="true" aria-label="Edit intro">
  <div class="modal">
    <form method="post" action="/profile/intro">
      <?= csrf_field() ?>
      <div class="modal-head">
        <h2>Edit details</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label class="field-label" for="bio">Bio</label>
          <textarea class="textarea" id="bio" name="bio" rows="3" maxlength="255"
                    placeholder="Describe yourself in a line or two"><?= e($owner['bio']) ?></textarea>
        </div>
        <div class="field"><label class="field-label" for="work">Work</label>
          <input class="input" id="work" name="work" value="<?= e($owner['work']) ?>" maxlength="120"></div>
        <div class="field"><label class="field-label" for="education">Education</label>
          <input class="input" id="education" name="education" value="<?= e($owner['education']) ?>" maxlength="120"></div>
        <div class="field-row">
          <div class="field"><label class="field-label" for="city">Current city</label>
            <input class="input" id="city" name="city" value="<?= e($owner['city']) ?>" maxlength="120"></div>
          <div class="field"><label class="field-label" for="hometown">Hometown</label>
            <input class="input" id="hometown" name="hometown" value="<?= e($owner['hometown']) ?>" maxlength="120"></div>
        </div>
        <div class="field">
          <label class="field-label" for="relationship">Relationship status</label>
          <select class="select" id="relationship" name="relationship">
            <?php foreach ($relationships as $value => $label): ?>
              <option value="<?= $value ?>" <?= $owner['relationship'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="field-label" for="website">Website</label>
          <input class="input" id="website" name="website" type="url" value="<?= e($owner['website']) ?>"
                 placeholder="https://example.com" maxlength="190">
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-primary btn-block" type="submit">Save details</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
