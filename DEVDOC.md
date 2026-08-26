# FaceClone - Developer Documentation

Technical reference for the FaceClone codebase: architecture, auth model, data model, route
surface, and setup. For what the app does from a user's point of view, see
[README.md](./README.md).

## Table of contents

- [Tech stack](#tech-stack)
- [Architecture overview](#architecture-overview)
- [Request lifecycle](#request-lifecycle)
- [Auth model](#auth-model)
- [Authorisation and privacy](#authorisation-and-privacy)
- [Route surface](#route-surface)
- [Data model](#data-model)
- [File storage and uploads](#file-storage-and-uploads)
- [Theming](#theming)
- [Frontend structure](#frontend-structure)
- [Directory layout](#directory-layout)
- [CLI](#cli)
- [Testing](#testing)
- [Configuration](#configuration)
- [Local development](#local-development)
- [Deployment](#deployment)
- [Known constraints and gotchas](#known-constraints-and-gotchas)

## Tech stack

PHP 8.1 or newer, MariaDB 10.4+ / MySQL 8, and plain CSS and JavaScript served as static
files. There is no framework, no Composer dependency and no build step: `git clone`, load the
schema, point a web server at `public/`, and the application runs. The only required PHP
extensions are `pdo_mysql`, `gd`, `mbstring` and `fileinfo`.

The absence of a framework is deliberate for a reference application, but it means a few
things a framework would give you are implemented here by hand and worth knowing about: the
autoloader (`app/Core/Autoloader.php`), the router (`app/Core/Router.php`), CSRF
(`app/Core/Csrf.php`) and the validator (`app/Core/Validator.php`).

## Architecture overview

```
Browser
  |  HTML page loads, then fetch() for reactions, comments, feed pages, messages
  v
public/index.php                 front controller: boot, session, dispatch
  |
  +-- app/Core/Router            pattern match -> [Controller, method]
  |
  +-- app/Controllers/*          request handling, authorisation, redirect or JSON
  |     |
  |     +-- app/Models/*         all SQL lives here, one class per aggregate
  |     |     |
  |     |     v
  |     |   MariaDB (PDO, prepared statements only)
  |     |
  |     +-- app/Core/Upload      GD re-encode -> public/uploads/<folder>/
  |     |
  |     +-- app/Core/Notifier    notification fan-out, respects user preferences
  |
  +-- app/Core/View              renders app/Views/**, returns markup
```

Controllers never write SQL and models never emit HTML. Views receive plain arrays and use
the helpers in `app/Core/helpers.php`; they never call a model directly except for a few
read-only presentation lookups (`User::isOnline`, `Story::viewCount`).

## Request lifecycle

1. `public/index.php` registers the autoloader, loads `app/Core/helpers.php` and calls
   `App::boot()`, which reads config, sets the timezone and opens the PDO connection.
   A connection failure short-circuits to `app/Views/errors/boot.php` with setup instructions
   rather than a stack trace.
2. `Session::start()` opens an HttpOnly, SameSite=Lax session cookie named `faceclone_session`.
3. Presence is refreshed at most once a minute per session, not on every hit.
4. `app/routes.php` declares the route table; `Router::dispatch()` matches and invokes.
5. The controller calls `$this->auth($request)` (redirects guests) and `$this->csrf($request)`
   for anything that writes.
6. The response is either `View::render()` into a layout, or `Response::json()`.

An unmatched path that exists under a different HTTP verb returns 405 with an `Allow` header
before falling through to the 404 page.

## Auth model

Sessions are server-side. `Auth::attempt()` verifies with `password_verify()` against a
`PASSWORD_DEFAULT` hash, regenerates the session id, and stores only the user id in the
session. Hashes are transparently upgraded on login when `password_needs_rehash()` says so.

"Keep me logged in" issues a split selector/validator token: the selector is stored in clear
so it can be looked up by index, the validator is stored as a SHA-256 hash and compared with
`hash_equals()`. The cookie is `faceclone_remember`, HttpOnly, 30 days. Logging out deletes
the row and clears the cookie.

Password reset uses `password_resets`, storing only the SHA-256 of the token, with a one hour
expiry and a `used_at` stamp so a link cannot be replayed. **There is no mail transport
configured**: `AuthController::forgot()` puts the reset link in the session and the page
renders it on screen. Wire that to a mailer before this goes anywhere real.

CSRF: every non-GET request must carry `csrf` in the body or `X-CSRF-Token` in the header.
Failures return 419 (JSON) or bounce back with a flash (form posts).

## Authorisation and privacy

Two layers, both enforced server-side on every read:

**Post visibility** (`Post::canView`) resolves at read time, never cached:

| Audience | Who can read it |
|---|---|
| `public` | anyone not blocked |
| `friends` | the author and accepted friends |
| `only_me` | the author only |
| any, inside a group | group members; plus anyone if the group is public |

**Blocks** are symmetric. `Block::hiddenIds()` returns both directions, and every feed,
search and suggestion query excludes them. Blocking also deletes the friendship row and both
follow edges, so it cannot be undone by the other party re-adding you.

Editing and deleting is checked by `Post::canEdit`: the author always, plus group moderators
and admins for posts inside their group. Comments can be removed by their author or by the
post's author.

## Route surface

Declared in `app/routes.php`. Everything except the auth routes requires a session.

| Area | Routes |
|---|---|
| Auth | `GET/POST /login`, `/register`, `/forgot-password`, `/reset-password`, `/logout` |
| Feed | `GET /`, `/feed/more` (JSON), `/watch`, `/saved`, `/memories` |
| Posts | `POST /posts`, `GET /posts/{id}`, `POST /posts/{id}/{update,delete,react,save,share}`, `GET /posts/{id}/{reactions,comments}` |
| Comments | `POST /comments`, `POST /comments/{id}/{update,delete,react}` |
| Stories | `GET /stories`, `/stories/create`, `/stories/user/{id}`, `POST /stories`, `/stories/{id}/{seen,delete}` |
| Profile | `GET /u/{handle}[/about|/friends|/photos|/groups|/followers|/following]`, `POST /profile/{avatar,cover,intro,details}`, `GET /avatar/{id}` |
| Friends | `GET /friends[/requests|/suggestions|/all]`, `POST /friends/{id}/{request,accept,decline,cancel,remove,follow,block,unblock}` |
| Groups | `GET /groups[/discover|/create]`, `GET /g/{slug}[/about|/members|/photos|/requests|/invite|/settings]`, `POST /groups`, `/g/{slug}/{settings,cover,join,leave,delete}`, `/g/{slug}/{invite,approve,reject,remove,role}/{userId}` |
| Messenger | `GET /messages[/{id}]`, `/messages/new/{userId}`, `POST /messages/{id}`, `GET /messages/{id}/poll`, `/api/messages/{unread,recent}` |
| Notifications | `GET /notifications`, `/api/notifications`, `/notifications/{id}/open`, `POST /notifications/{id}/delete`, `/notifications/read-all` |
| Search | `GET /search`, `/api/search` |
| Settings | `GET/POST /settings/{account,privacy,notifications,appearance}`, `GET /settings/blocking`, `POST /settings/{password,deactivate,delete}` |
| Events | `GET /events[/create|/{id}]`, `POST /events`, `/events/{id}/{rsvp,delete}` |
| Marketplace | `GET /marketplace[/create|/selling|/item/{id}]`, `POST /marketplace`, `/marketplace/item/{id}/{sold,delete}` |

Endpoints under `/api/` and the `react`, `save`, `comments`, `poll` actions return JSON of the
shape `{"ok": true, ...}` or `{"ok": false, "error": "..."}`.

`{handle}` accepts a username or a numeric id. `{slug}` accepts a group slug or id.

## Data model

23 tables, InnoDB, `utf8mb4_unicode_ci`. Full DDL in `database/schema.sql`.

**Timezone convention:** every `DATETIME` is stored in the timezone set by
`config.app.timezone`, which defaults to `UTC`. Columns use `CURRENT_TIMESTAMP` defaults, so
the value comes from the database server, not PHP. Change the app timezone and existing rows
are not rewritten.

| Table | Purpose and notable columns |
|---|---|
| `users` | Identity and profile. Unique `email` and `username`. `is_active = 0` means deactivated, not deleted. `last_seen` drives the online dot. |
| `user_settings` | One row per user: `theme`, `default_privacy`, `who_can_friend`, `who_can_message`, `show_online`, and five `notify_*` switches. |
| `remember_tokens` | `selector` (unique, plain) + `validator` (SHA-256) + `expires_at`. |
| `password_resets` | `token_hash` unique, `expires_at`, `used_at`. |
| `friendships` | One row per pair with `requester_id` on the left. `status` is `pending`/`accepted`/`declined`. Unique on `(requester_id, addressee_id)`. |
| `follows` | Directed edge. Accepting a friendship creates both directions. |
| `blocks` | Directed, but read symmetrically everywhere. |
| `groups` | `slug` unique, `privacy` is `public`/`private`, `creator_id`. |
| `group_members` | Composite PK `(group_id, user_id)`. `role` admin/moderator/member, `status` member/pending/invited. |
| `posts` | `group_id` and `shared_post_id` are nullable. `background` names a gradient in `post_backgrounds()`. `privacy` is the audience. `edited_at` set on update. |
| `post_media` | One row per attachment, `type` image/video, `sort` for ordering. |
| `comments` | `parent_id` self-reference; replies are flattened to one level in `Comment::create()`. |
| `reactions` | Polymorphic on `(target_type, target_id)`, unique per user per target, seven `type` values. |
| `saved_posts` | Composite PK `(user_id, post_id)`. |
| `stories` | `expires_at` set to `NOW() + 24h` on insert. Every read filters on it. |
| `story_views` | Composite PK `(story_id, user_id)`. |
| `conversations`, `conversation_participants`, `messages` | `last_read_at` per participant drives unread counts. `is_group` supports group chats. |
| `notifications` | `type` is a string key, `url` is the click target, `is_read` flag. |
| `events`, `event_attendees` | `status` going/interested. |
| `listings` | Marketplace items, `category` constrained by `Listing::CATEGORIES`. |

Foreign keys cascade on delete, so removing a user removes their posts, comments, reactions,
memberships and messages. `posts.shared_post_id` is `ON DELETE SET NULL`, so deleting an
original leaves the share in place with an empty embed rather than deleting other people's posts.

## File storage and uploads

Uploads land under `public/uploads/<folder>/` where folder is one of `avatars`, `covers`,
`posts`, `stories`, `messages`, `groups`. Filenames are 20 hex characters plus an extension;
the original name is discarded.

`app/Core/Upload.php` does the work:

- MIME type comes from `finfo`, never from the browser's `Content-Type` or the extension
- Images are decoded and **re-encoded through GD**, which strips anything that is not image
  data (this is what stops a PHP payload with a `.jpg` name from surviving)
- EXIF orientation is applied for JPEGs, then the longest edge is capped per folder
  (720px avatars, 1280px stories and message attachments, 1600px everything else)
- PNG, GIF and WebP keep their alpha and are written as PNG; everything else becomes JPEG at
  quality 86
- Videos are not re-encoded, only MIME-checked and moved
- `Upload::delete()` refuses any path that does not resolve inside `public/uploads`

Files are served directly by the web server. `public/uploads/.htaccess` disables the PHP
engine in that tree as a second line of defence; **replicate that rule if you serve with
nginx**, which does not read `.htaccess`.

Profile pictures fall back to `/avatar/{id}`, which renders a deterministic initials SVG. No
placeholder image files are shipped.

## Theming

Everything is CSS custom properties on `:root`, redefined under `[data-theme="dark"]`, defined
once in `public/assets/css/app.css`. The palette mirrors Facebook's: `#1877F2` accent,
`#F0F2F5` / `#FFFFFF` in light, `#18191A` / `#242526` in dark.

The theme is resolved before first paint by an inline script in `app/Views/layouts/head.php`,
which reads the server-rendered preference and then `localStorage`, so there is no flash of
the wrong theme. `system` follows `prefers-color-scheme`. Changing the radio in
Display settings applies immediately and persists via `POST /settings/appearance`.

Scrollbars are themed globally through `scrollbar-color` (Firefox) and `::-webkit-scrollbar`
rules (Chrome, Safari), with rails hiding their thumb until hovered.

## Frontend structure

No build step. Two files:

- `public/assets/css/app.css` - tokens, then components in the order the page uses them
- `public/assets/js/app.js` - one IIFE, everything delegated from `document`

Delegation matters: feed pages, comments and polled messages are injected as HTML by
`fetch()`, so any handler bound to a specific element would stop working after the first
append. Adding a new interactive control means adding a `data-*` hook, not a new listener.

Icons are inline SVG from `app/Core/icons.php`, rendered by `icon($name, $size, $class)`.
They inherit `currentColor`. `svg` is `display: block` globally; use the `text-icon` class for
an icon that sits inside a run of text.

## Directory layout

```
app/
  Core/          framework: App, Router, Request, Response, Database, Auth, Session,
                 Csrf, Validator, View, Upload, Notifier, Chrome, helpers, icons
  Controllers/   one per feature area
  Models/        one per aggregate; all SQL lives here
  Views/
    layouts/     app, auth, bare, immersive, messenger, head
    partials/    post card, comment, composer, rails, dropdowns, person card
    <area>/      one directory per feature
  routes.php     the route table
bin/console.php  install, seed, doctor, prune-stories, make-user
config/          config.example.php (committed), config.local.php (ignored)
database/        schema.sql, seed.php
public/          web root: index.php, assets, uploads, .htaccess
server.php       router for PHP's built-in server
storage/         logs and cache, git-ignored
```

## CLI

```bash
php bin/console.php doctor                       # extensions, permissions, DB connection
php bin/console.php install [--seed] [--force]   # load schema, optionally demo data
php bin/console.php seed [--force]               # demo data into an existing schema
php bin/console.php prune-stories                # delete stories past their expiry
php bin/console.php make-user <email> <pass> "First Last"
```

`--force` on install or seed **truncates every table**. `doctor` runs before the database is
booted, so it is the right tool when the app will not start.

Expired stories are also pruned opportunistically whenever the feed or stories page is
loaded, so the cron job is optional; add it if traffic is low enough that pages are rarely hit.

## Testing

There is no automated test suite. Verification for this build was done by driving the running
application: every GET route was swept for PHP errors, every write endpoint was exercised over
HTTP with real multipart uploads, and the full UI was walked in Chrome at desktop, tablet and
phone widths in both themes.

The security behaviours that were explicitly checked, and that any change here should preserve:

- CSRF rejection on JSON (419) and form (redirect with flash) endpoints
- Guests redirected to `/login`; AJAX guests get 401
- `only_me` and `friends` posts invisible to non-recipients (404, not 403, so the post's
  existence is not disclosed)
- Editing or deleting another user's post or comment returns 403
- A conversation is unreadable by a non-participant (403)
- A `.php` file with an image MIME header is rejected and never written to `uploads/`
- Post text is HTML-escaped; URLs, `#tags` and `@mentions` are linked after escaping
- Search input is parameterised and cannot break the query

## Configuration

`config/config.php` layers three sources, later winning: `config.example.php`, then
`config.local.php` if present, then environment variables.

| Variable | Purpose |
|---|---|
| `FACECLONE_DB_HOST` | Database host, default `127.0.0.1` |
| `FACECLONE_DB_PORT` | Database port, default `3306` |
| `FACECLONE_DB_NAME` | Database name, default `faceclone` |
| `FACECLONE_DB_USER` | Database user |
| `FACECLONE_DB_PASSWORD` | Database password |
| `FACECLONE_URL` | Public base URL |
| `FACECLONE_DEBUG` | `true` shows exceptions; set `false` in production |

Credentials belong in `config/config.local.php` (git-ignored) or the environment, never in
`config.example.php`, which is committed.

## Local development

From a fresh clone:

1. Create the database and a user:

   ```bash
   sudo mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS faceclone
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER IF NOT EXISTS 'faceclone'@'localhost' IDENTIFIED BY 'faceclone';
   GRANT ALL ON faceclone.* TO 'faceclone'@'localhost'; FLUSH PRIVILEGES;"
   ```

2. Copy the config if your credentials differ from the defaults:

   ```bash
   cp config/config.example.php config/config.local.php
   ```

3. Check the environment, then install:

   ```bash
   php bin/console.php doctor
   php bin/console.php install --seed
   ```

4. Run it:

   ```bash
   php -S localhost:8000 -t public server.php
   ```

5. Sign in as `dileep@faceclone.test` / `password123`. Every seeded account shares that password.

The `server.php` argument is required, see the gotchas below.

## Deployment

Point the document root at `public/` and never at the project root. `app/`, `config/`,
`database/`, `storage/` and `bin/` must not be web-reachable.

Apache: enable `mod_rewrite`; `public/.htaccess` handles the front-controller rewrite,
security headers and disabling PHP in `uploads/`.

nginx: `.htaccess` is ignored, so both rules need translating:

```nginx
root /path/to/FaceClone/public;
index index.php;

location / { try_files $uri $uri/ /index.php?$query_string; }

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

# Uploads are data, never code.
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
```

Before going live: set `FACECLONE_DEBUG=false`, change the database password, serve over
HTTPS and add `'secure' => true` to the session and remember-me cookie parameters, and wire
`AuthController::forgot()` to a real mailer.

## Known constraints and gotchas

**PHP's built-in server drops any URL with a dot in the last path segment.** `/u/dileep.adari`
returns the server's own 404 because it treats the segment as a static file. This is why
`server.php` exists and why the run command is
`php -S localhost:8000 -t public server.php` rather than the usual `-t public`. Apache and
nginx are unaffected.

**`use App\Core\App;` shadows the whole `App\` namespace root.** In any file with that import,
`App\Models\User` resolves to `App\Core\App\Models\User` and fails at runtime, not at parse
time, so it only shows up when the line executes. Always write `\App\Models\User` with a
leading backslash in those files. This bit `public/index.php`, `bin/console.php` and
`database/seed.php` during the build.

**`groups` is a reserved word in MySQL 8.** It is not reserved in MariaDB, but every query
against that table backticks it so the schema works on both. Keep doing that.

**A CSS rule placed before the declaration it means to override loses.** The mobile
`.topbar-nav a { display: none }` media query originally sat above the `.topbar-nav a` block
that sets `display: grid`, so it silently did nothing. Same specificity means source order
decides; put media queries after the base rule.

**`aspect-ratio` does nothing on an inline element.** `.person-photo` is an `<a>`, which is
inline by default, so the ratio was ignored and the image stretched to the grid-stretched card
height, pushing the name and buttons out of an `overflow: hidden` card. Any element carrying
`aspect-ratio` needs `display: block` or similar.

**Hiding a grid child with `display: none` reflows its siblings into the wrong columns.** The
top bar is a three-column grid; hiding `.topbar-nav` outright moved `.topbar-right` into the
centre column and left a large gap at the right edge. Hide the nav's links instead and keep
the nav itself in the grid.

**A count element that is absent cannot be updated by script.** The post stats row is now
always rendered and hidden with a class while empty, because rendering it only when a post
already had activity meant a fresh reaction had nowhere to appear until reload.

**Absolutely positioned badges swallow clicks.** The notification and message badges sit over
their button's corner; they carry `pointer-events: none` so a click at that spot still reaches
the button.

**Messenger polls, it does not push.** `/messages/{id}/poll?after={id}` runs every four
seconds while the tab is visible, and the top-bar unread badge refreshes every twenty. It is
adequate for a demo and the wrong choice for real traffic; replace with SSE or WebSockets
before scaling.

**Reaction and comment counts are computed per request.** `Post::hydrate()` keeps a page of
posts to a fixed number of queries regardless of page size, but there are no denormalised
counters. A feed of thousands would want them.

**Stories are pruned lazily.** Nothing deletes an expired story on a timer; reads filter on
`expires_at` and `Story::prune()` cleans up when the feed or stories page is loaded. A story
row can outlive its 24 hours on a quiet instance until someone visits.
