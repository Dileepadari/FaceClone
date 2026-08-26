<p align="center">
  <img src="./public/assets/img/logo-mark.png" width="96" alt="ADK DEV">
</p>

# FaceClone

A social network you can run on your own machine: posts, stories, reactions, comments,
friends, groups, messaging, marketplace and events, built to look and behave like Facebook.

It exists as a full-stack reference application. Every feature is wired end to end against a
real database, with real file uploads, real privacy rules and no mocked data.
For architecture, data model and setup, see **[DEVDOC.md](./DEVDOC.md)**.

## Features

### Feed and posts
- Write a post with text, up to eight photos or videos, a feeling and a location
- Put a short text post on one of eight gradient backgrounds, the way Facebook does
- Pick an audience per post: Public, Friends, or Only me
- React with any of the seven reactions; hover the Like button to open the reaction picker
- Comment, reply to a comment, react to a comment, edit and delete your own
- Share a post to your own timeline with your own commentary attached
- Save a post to read later, and delete or edit anything you wrote
- The feed loads more posts as you scroll, without a page change

### Stories
- Post a photo story, or a text story on a gradient background
- Stories expire 24 hours after posting and disappear from every tray automatically
- The story viewer runs timed progress bars, advances on its own, and chains from one
  person to the next; tap either side to step back and forward
- Your own stories show how many people have seen them

### Profile
- Cover photo and profile photo, both replaceable in place
- Intro panel: bio, work, education, current city, hometown, relationship, website
- Tabs for posts, about, friends, photos and groups
- Followers and following lists

### Friends
- Send, cancel, accept and decline friend requests
- Unfriend, follow without friending, and block
- People You May Know, ranked by how many friends you have in common
- Blocking is symmetric: a blocked person disappears from your feed, search and profile,
  and any existing friendship is removed

### Groups
- Create a public or private group with a cover photo and description
- Public groups let anyone join instantly; private groups queue a request for an admin
- Post inside a group, invite friends, review join requests
- Admin, moderator and member roles, with an admin able to promote, demote or remove
- The last admin cannot leave without the group being handed to someone else

### Messenger
- One conversation per pair, created the first time you message someone
- Send text and photo attachments; new messages arrive without a reload
- Unread badges in the top bar and per conversation, and an active-now indicator

### Notifications
- Reactions, comments, replies, friend requests, shares, group activity and messages
- A dropdown in the top bar and a full page, both with read/unread state
- Every notification type can be switched off in settings, which stops it being created

### Search
- Typeahead in the top bar, plus a results page split into people, posts and groups
- Hashtags and @mentions in post text are linked and searchable

### Marketplace and events
- List an item with a price, category, location and photo; mark it sold or delete it
- Message a seller directly from a listing
- Create events, RSVP as going or interested, and see who else is attending

### Settings
- Account: name, username, email, password, deactivate, delete
- Privacy: default post audience, who can friend you, who can message you, activity status
- Notifications: a switch per notification type
- Display: light, dark, or follow the system
- Blocking: see and undo everyone you have blocked

## The post lifecycle

A post moves through a small set of states rather than a workflow:

1. **Composed** in the dialog, with an audience chosen up front
2. **Published**, visible to whoever the audience allows, and to nobody else
3. **Edited**, which keeps the original timestamp and marks the post as edited
4. **Shared** by someone else, which creates a new post pointing at the original
5. **Deleted**, which removes its media, comments and reactions with it

Visibility is decided at read time, never cached. Changing a post's audience takes effect
for every reader immediately, and a post in a group is visible to that group's members
regardless of the author's default audience.

## Tech stack

PHP 8.1+ with no framework and no Composer dependencies, MariaDB or MySQL, and hand-written
CSS and vanilla JavaScript with no build step. Details in [DEVDOC.md](./DEVDOC.md).

## Getting started

```bash
php bin/console.php doctor          # check PHP extensions, permissions and the database
php bin/console.php install --seed  # create the schema and load demo data
php -S localhost:8000 -t public server.php
```

Then sign in as `dileep@faceclone.test` with password `password123`. Every seeded account
uses the same password. Full setup, including the database user, is in
[DEVDOC.md](./DEVDOC.md#local-development).
