# not_for_you.md

A personal working log. Not documentation, and nothing here is needed to use or contribute to FaceClone. Everything a newcomer actually needs is in [README.md](./README.md) and [DEVDOC.md](./DEVDOC.md).

---

## Three real bugs, all found by poking at the running app rather than reading it

### Marketplace and message search were unusable below 900px

```css
@media (max-width: 899px) { .search-pill { width: 40px; } .search-pill input { display: none; } }
```

That rule was written for the **topbar**, where collapsing search to an icon is right: space is contested and there is a separate mobile entry point. But `.search-pill` is reused by `marketplace/index.php` and `messages/index.php`, where it is the **only** way to search that page.

So on any tablet or phone both became a 40px stub with the input `display: none` and nothing anywhere to reveal it. Not degraded, gone.

Found it by measuring the element during a tablet capture rather than looking at the screenshot, where it just reads as a small round button. Scoped the rule to `.topbar .search-pill` and confirmed afterwards that the marketplace input is back at 192px while the topbar one still collapses.

The lesson is about naming: `.search-pill` describes what it looks like, so it got reused, and then a rule about *where it lives* was written against *what it looks like*.

### HEAD requests answered 404

`dispatch()` looks up `$this->routes[$method]`, nothing registers HEAD routes, so every HEAD fell through to the "wrong verb" branch. HTTP requires HEAD to work wherever GET does, and health checks, link checkers and monitoring all probe with it. Now mapped to GET before lookup.

### The 405 branch shipped a 404

Worse, and adjacent:

```php
http_response_code(405);
header('Allow: ' . $otherMethod);
Response::notFound('That action uses a different request method.');
```

`Response::notFound()` calls `http_response_code(404)` unconditionally, so the 405 was overwritten two lines later. The comment above it says "report that rather than a bare 404" and it reported a bare 404 with a confusing `Allow` header attached. Added `Response::methodNotAllowed()` that actually sends 405.

Both of these are the kind of thing a browser never shows you. `curl -I` did.

## No security headers at all

The session cookie was already `HttpOnly; SameSite=Lax`, which is the important one and gives real CSRF protection. But nothing sent `X-Frame-Options`, `X-Content-Type-Options` or `Referrer-Policy`.

A social site is a clickjacking target in a way a brochure page is not: the whole surface is one-click actions (react, follow, confirm, delete) behind an already-authenticated session, which is exactly what a transparent iframe over a decoy page is for. Added all three at the front controller.

**A Content-Security-Policy is deliberately not added.** The views use inline styles and inline handlers in enough places that adding one blind would break pages rather than protect them. It needs a pass over the templates first. Recorded rather than half-done.

Note the trade this makes: `X-Frame-Options: DENY` breaks the iframe capture harness, so the screenshots for this pass went through a throwaway proxy that strips it. Correct priority, and worth writing down so nobody removes the header to make captures easier.

## The emoji question, answered twice

The house rule is no literal emoji. Two of the four hits were real and got fixed: a magnifying glass used as a decorative glyph in a JS-built menu row (now the same inline SVG the server renders through `icon('search')`, so it takes the menu's colour), and check marks in the CLI runner (now `ok` and `FAIL`, which survive a terminal without the font).

The other two are the **reaction set**, and they stay. A reaction set is emoji by definition, the same way a Like button is a thumb; swapping them for an icon pack would change the product rather than tidy it. Both copies, `reaction_types()` in `app/Core/helpers.php` and `REACTIONS` in `app.js`, now carry a comment saying so, because otherwise the next sweep deletes them.

## Things checked that turned out fine

- **`config/config.php` is tracked**, and `.gitignore` only covers `config.local.php`. Looked like a committed-credentials bug. It is not: `config.php` is a loader that layers `config.example.php`, then `config.local.php` if present, then `FACECLONE_*` environment variables. Nothing secret is in it. Good design, momentarily alarming file naming.
- **The seeded login is `dileep@faceclone.test`.** `.test` is reserved by RFC 6761 and can never resolve, so unlike the real address found in BlogNest this binds no inbox to a published password. Left alone.

## Licensing

This repo is **GPL-3.0** and stays that way. Nothing forces it (no dependencies, no vendored code, single author), so the author could relicense, but that is their call and not a tidy-up. What was actually wrong is that **neither the README nor DEVDOC mentioned the licence at all**, so a reader had to open `LICENSE` to discover the project is copyleft. Now stated, with a note that it differs from the MIT projects in this account.

## Open threads

- **No tests.** None at all. CI now proves the app installs, seeds, reseeds and serves a page, plus the three router and header behaviours fixed above, which is a floor rather than a suite. `app/Models` privacy logic is where a silent wrong answer would actually hurt and none of it is covered.
- **No CSP**, as above; needs a template pass first.
- **`/watch` is empty on a fresh install** because the seed creates no video posts. Same class of gap as the missing orders in CanteenX: the screen works, there is just nothing to look at, which reads as broken.
- The tablet breakpoint was only audited on the pages that were captured. The `.search-pill` bug suggests looking for other rules written against a shared class.
