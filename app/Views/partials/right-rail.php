<?php
/**
 * Home right rail: friend requests, suggestions, events, contacts.
 * @var array $requests
 * @var array $suggestions
 * @var array $events
 * @var array $contacts
 */
?>
<?php if (!empty($requests)): ?>
  <div class="rail-title">Friend requests <a class="small" href="/friends/requests">See all</a></div>
  <?php foreach ($requests as $person): ?>
    <div class="row-top" style="padding:8px" data-person-card>
      <a href="<?= e(profile_url($person)) ?>"><img class="avatar avatar-60" src="<?= e(avatar_url($person)) ?>" alt=""></a>
      <div class="grow" style="min-width:0">
        <a class="bold" href="<?= e(profile_url($person)) ?>"><?= e(full_name($person)) ?></a>
        <div class="tiny muted"><?= e(time_ago($person['requested_at'])) ?></div>
        <div class="row mt-8">
          <button class="btn btn-primary btn-sm grow" type="button"
                  data-friend-action="/friends/<?= (int) $person['id'] ?>/accept"
                  data-remove-card="1" data-toast-text="Friend request accepted">Confirm</button>
          <button class="btn btn-sm grow" type="button"
                  data-friend-action="/friends/<?= (int) $person['id'] ?>/decline"
                  data-remove-card="1" data-toast-text="Request declined">Delete</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <hr class="rail-sep">
<?php endif; ?>

<?php if (!empty($events)): ?>
  <div class="rail-title">Upcoming events <a class="small" href="/events">See all</a></div>
  <?php foreach ($events as $event): ?>
    <a class="rail-link" href="/events/<?= (int) $event['id'] ?>">
      <span class="event-date">
        <span class="m"><?= date('M', strtotime($event['starts_at'])) ?></span>
        <span class="d"><?= date('j', strtotime($event['starts_at'])) ?></span>
      </span>
      <span class="grow" style="min-width:0">
        <span class="truncate bold" style="display:block"><?= e($event['title']) ?></span>
        <span class="tiny muted"><?= (int) $event['going_count'] ?> going</span>
      </span>
    </a>
  <?php endforeach; ?>
  <hr class="rail-sep">
<?php endif; ?>

<?php if (!empty($suggestions)): ?>
  <div class="rail-title">People you may know <a class="small" href="/friends/suggestions">See all</a></div>
  <?php foreach ($suggestions as $person): ?>
    <div class="row-top" style="padding:8px" data-person-card>
      <a href="<?= e(profile_url($person)) ?>"><img class="avatar avatar-48" src="<?= e(avatar_url($person)) ?>" alt=""></a>
      <div class="grow" style="min-width:0">
        <a class="bold truncate" style="display:block" href="<?= e(profile_url($person)) ?>"><?= e(full_name($person)) ?></a>
        <?php if ((int) ($person['mutuals'] ?? 0) > 0): ?>
          <div class="tiny muted"><?= (int) $person['mutuals'] ?> mutual friends</div>
        <?php endif; ?>
        <button class="btn btn-soft btn-sm mt-8" type="button"
                data-friend-action="/friends/<?= (int) $person['id'] ?>/request"
                data-replace-with='<span class="small muted">Request sent</span>'>
          <?= icon('plus', 14) ?> Add friend
        </button>
      </div>
    </div>
  <?php endforeach; ?>
  <hr class="rail-sep">
<?php endif; ?>

<div class="rail-title">Contacts</div>
<?php if (!$contacts): ?>
  <p class="small muted" style="padding:0 8px 8px">Add friends to start chatting.</p>
<?php else: ?>
  <?php foreach ($contacts as $contact): ?>
    <a class="rail-link" href="/messages/new/<?= (int) $contact['id'] ?>">
      <?php if (\App\Models\User::isOnline($contact)): ?>
        <span class="avatar-online"><img class="avatar avatar-36" src="<?= e(avatar_url($contact)) ?>" alt=""></span>
      <?php else: ?>
        <img class="avatar avatar-36" src="<?= e(avatar_url($contact)) ?>" alt="">
      <?php endif; ?>
      <span class="truncate"><?= e(full_name($contact)) ?></span>
    </a>
  <?php endforeach; ?>
<?php endif; ?>
