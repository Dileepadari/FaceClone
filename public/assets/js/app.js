/* =========================================================================
   FaceClone front-end. Vanilla ES2020, no build step.
   Everything is delegated from document so markup injected by fetch()
   (extra feed pages, new comments, polled messages) works without rebinding.
   ========================================================================= */
(function () {
  'use strict';

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const CSRF = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  /* ---------------------------------------------------------------- fetch */

  async function api(url, options = {}) {
    const opts = Object.assign({ method: 'GET', headers: {} }, options);
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    opts.headers['Accept'] = 'application/json';

    if (opts.body instanceof FormData) {
      opts.body.append('csrf', CSRF());
    } else if (opts.json) {
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF-Token'] = CSRF();
      opts.body = JSON.stringify(opts.json);
      delete opts.json;
    }

    const res = await fetch(url, opts);
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('The server returned an unexpected response.');
    }
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Something went wrong. Please try again.');
    }
    return data;
  }

  function post(url, fields = {}) {
    const body = new FormData();
    Object.entries(fields).forEach(([k, v]) => body.append(k, v));
    return api(url, { method: 'POST', body });
  }

  /* ---------------------------------------------------------------- toast */

  function toast(message, type = 'info') {
    let stack = $('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = 'toast is-' + type;
    el.setAttribute('role', 'status');
    el.innerHTML = '<div class="grow"></div><button type="button" aria-label="Dismiss">&times;</button>';
    el.firstChild.textContent = message;
    el.querySelector('button').addEventListener('click', () => el.remove());
    stack.appendChild(el);
    setTimeout(() => el.remove(), 5000);
  }
  window.fcToast = toast;

  /* ----------------------------------------------------------------- theme */

  function applyTheme(theme) {
    const resolved = theme === 'system'
      ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : theme;
    document.documentElement.setAttribute('data-theme', resolved);
    try { localStorage.setItem('fc-theme', theme); } catch (e) { /* private mode */ }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-set]');
    if (!btn) return;
    e.preventDefault();
    const theme = btn.dataset.themeSet;
    applyTheme(theme);
    post('/settings/appearance', { theme }).catch(() => {});
    $$('[data-theme-set]').forEach((b) => b.classList.toggle('is-active', b.dataset.themeSet === theme));
  });

  document.addEventListener('change', (e) => {
    const radio = e.target.closest('input[name="theme"]');
    if (radio && radio.checked) applyTheme(radio.value);
  });

  /* -------------------------------------------------------------- dropdown */

  function closeDropdowns(except) {
    $$('.dropdown-panel.is-open').forEach((panel) => {
      if (panel === except) return;
      panel.classList.remove('is-open');
      const trigger = document.querySelector('[aria-controls="' + panel.id + '"]');
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
        trigger.classList.remove('is-open');
      }
    });
  }

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-dropdown]');

    if (!trigger) {
      if (!e.target.closest('.dropdown-panel')) closeDropdowns(null);
      return;
    }

    e.preventDefault();
    const panel = document.getElementById(trigger.dataset.dropdown);
    if (!panel) return;

    const willOpen = !panel.classList.contains('is-open');
    closeDropdowns(panel);
    panel.classList.toggle('is-open', willOpen);
    trigger.setAttribute('aria-expanded', String(willOpen));
    trigger.classList.toggle('is-open', willOpen);

    if (willOpen && trigger.dataset.load && !panel.dataset.loaded) {
      panel.innerHTML = '<div class="spinner"></div>';
      api(trigger.dataset.load)
        .then((data) => {
          panel.innerHTML = data.html;
          panel.dataset.loaded = '1';
          const badge = trigger.querySelector('.badge');
          if (badge && data.count === 0) badge.remove();
        })
        .catch((err) => { panel.innerHTML = '<p class="muted card-pad">' + err.message + '</p>'; });
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeDropdowns(null);
    const modal = $('.modal-backdrop.is-open');
    if (modal) closeModal(modal);
    const lightbox = $('.lightbox.is-open');
    if (lightbox) lightbox.classList.remove('is-open');
  });

  /* ----------------------------------------------------------------- modal */

  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    const focusable = modal.querySelector('[data-autofocus], input, textarea, button');
    if (focusable) setTimeout(() => focusable.focus(), 30);
  }

  function closeModal(modal) {
    modal.classList.remove('is-open');
    if (!$('.modal-backdrop.is-open')) document.body.style.overflow = '';
  }

  window.fcModal = openModal;

  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      e.preventDefault();
      openModal(opener.dataset.modalOpen);
      return;
    }
    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
      e.preventDefault();
      const modal = closer.closest('.modal-backdrop');
      if (modal) closeModal(modal);
      return;
    }
    // Clicking the scrim (but not the dialog) closes it.
    if (e.target.classList.contains('modal-backdrop')) closeModal(e.target);
  });

  /* -------------------------------------------------------------- lightbox */

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-lightbox]');
    if (trigger) {
      e.preventDefault();
      let box = $('.lightbox');
      if (!box) {
        box = document.createElement('div');
        box.className = 'lightbox';
        box.innerHTML = '<img alt="">';
        document.body.appendChild(box);
      }
      box.querySelector('img').src = trigger.dataset.lightbox;
      box.classList.add('is-open');
      return;
    }
    if (e.target.classList.contains('lightbox')) e.target.classList.remove('is-open');
  });

  /* ------------------------------------------------------------- reactions */

  const REACTIONS = {
    like:  { emoji: '👍', label: 'Like' },
    love:  { emoji: '❤️', label: 'Love' },
    care:  { emoji: '🥰', label: 'Care' },
    haha:  { emoji: '😆', label: 'Haha' },
    wow:   { emoji: '😮', label: 'Wow' },
    sad:   { emoji: '😢', label: 'Sad' },
    angry: { emoji: '😡', label: 'Angry' }
  };

  let flyoutTimer = null;

  function showFlyout(bar) {
    clearTimeout(flyoutTimer);
    $$('.reaction-bar.is-open').forEach((b) => { if (b !== bar) b.classList.remove('is-open'); });
    bar.classList.add('is-open');
  }

  function hideFlyout(bar) {
    flyoutTimer = setTimeout(() => bar.classList.remove('is-open'), 260);
  }

  document.addEventListener('mouseover', (e) => {
    const holder = e.target.closest('[data-reaction-holder]');
    if (!holder) return;
    const bar = holder.querySelector('.reaction-bar');
    if (bar) showFlyout(bar);
  });

  document.addEventListener('mouseout', (e) => {
    const holder = e.target.closest('[data-reaction-holder]');
    if (!holder) return;
    if (holder.contains(e.relatedTarget)) return;
    const bar = holder.querySelector('.reaction-bar');
    if (bar) hideFlyout(bar);
  });

  // Touch devices have no hover, so a long press opens the flyout instead.
  let pressTimer = null;
  document.addEventListener('touchstart', (e) => {
    const holder = e.target.closest('[data-reaction-holder]');
    if (!holder) return;
    pressTimer = setTimeout(() => {
      const bar = holder.querySelector('.reaction-bar');
      if (bar) showFlyout(bar);
    }, 400);
  }, { passive: true });
  document.addEventListener('touchend', () => clearTimeout(pressTimer));

  function renderReactionSummary(root, summary) {
    const chips = root.querySelector('[data-reaction-chips]');
    const count = root.querySelector('[data-reaction-count]');
    if (chips) {
      chips.innerHTML = (summary.top || [])
        .map((t) => '<span class="reaction-chip">' + (REACTIONS[t] ? REACTIONS[t].emoji : '') + '</span>')
        .join('');
    }
    if (count) {
      count.textContent = summary.total > 0 ? String(summary.total) : '';
      count.closest('[data-reaction-summary]')?.classList.toggle('hidden', summary.total === 0);
    }
  }

  function paintReactionButton(btn, type) {
    const glyph = btn.querySelector('[data-reaction-glyph]');
    const label = btn.querySelector('[data-reaction-label]');
    if (type) {
      btn.classList.add('is-reacted');
      btn.dataset.reaction = type;
      if (glyph) glyph.outerHTML = '<span class="emoji" data-reaction-glyph>' + REACTIONS[type].emoji + '</span>';
      if (label) label.textContent = REACTIONS[type].label;
    } else {
      btn.classList.remove('is-reacted');
      btn.dataset.reaction = '';
      if (glyph) glyph.outerHTML = btn.dataset.defaultGlyph || '<span class="emoji" data-reaction-glyph>👍</span>';
      if (label) label.textContent = 'Like';
    }
  }

  document.addEventListener('click', async (e) => {
    const pick = e.target.closest('[data-react]');
    if (!pick) return;
    e.preventDefault();

    const holder = pick.closest('[data-reaction-holder]');
    const url    = holder.dataset.reactUrl;
    const type   = pick.dataset.react;
    const bar    = holder.querySelector('.reaction-bar');
    if (bar) bar.classList.remove('is-open');

    const button = holder.querySelector('[data-reaction-button]');
    try {
      const data = await post(url, { type });
      paintReactionButton(button, data.reaction);
      const scope = holder.closest('[data-post-id], [data-comment-id]') || holder;
      renderReactionSummary(scope, data.summary);
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  /* --------------------------------------------------------- who reacted */

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-reactors]');
    if (!btn) return;
    e.preventDefault();

    const modal = document.getElementById('reactors-modal');
    if (!modal) return;
    const body = modal.querySelector('.modal-body');
    body.innerHTML = '<div class="spinner"></div>';
    openModal('reactors-modal');

    try {
      const data = await api(btn.dataset.reactors);
      body.innerHTML = data.html;
    } catch (err) {
      body.innerHTML = '<p class="muted">' + err.message + '</p>';
    }
  });

  document.addEventListener('click', async (e) => {
    const tab = e.target.closest('[data-reactor-filter]');
    if (!tab) return;
    e.preventDefault();
    const body = tab.closest('.modal-body');
    body.innerHTML = '<div class="spinner"></div>';
    try {
      const data = await api(tab.dataset.reactorFilter);
      body.innerHTML = data.html;
    } catch (err) {
      body.innerHTML = '<p class="muted">' + err.message + '</p>';
    }
  });

  /* -------------------------------------------------------------- comments */

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-load-comments]');
    if (!btn) return;
    e.preventDefault();

    const post = btn.closest('[data-post-id]');
    const area = post.querySelector('[data-comment-list]');
    const form = post.querySelector('[data-comment-form]');

    if (form) form.classList.remove('hidden');

    if (area.dataset.loaded) {
      const input = form && form.querySelector('.comment-input');
      if (input) input.focus();
      return;
    }

    area.innerHTML = '<div class="spinner"></div>';
    try {
      const data = await api('/posts/' + post.dataset.postId + '/comments');
      area.innerHTML = data.html;
      area.dataset.loaded = '1';
      const input = form && form.querySelector('.comment-input');
      if (input) input.focus();
    } catch (err) {
      area.innerHTML = '<p class="muted small">' + err.message + '</p>';
    }
  });

  // Enter sends, Shift+Enter adds a newline - the Facebook behaviour.
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.shiftKey) return;
    const input = e.target.closest('.comment-input, .thread-input');
    if (!input) return;
    e.preventDefault();
    const form = input.closest('form');
    if (form) form.requestSubmit();
  });

  document.addEventListener('input', (e) => {
    const el = e.target.closest('.comment-input, .thread-input, .composer-textarea');
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 200) + 'px';
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-comment-form]');
    if (!form) return;
    e.preventDefault();

    const input = form.querySelector('.comment-input');
    const file  = form.querySelector('input[type="file"]');
    if (!input.value.trim() && (!file || !file.files.length)) return;

    const body = new FormData(form);
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const data = await api('/comments', { method: 'POST', body });
      const postEl = form.closest('[data-post-id]');

      if (data.parentId) {
        const parent = postEl.querySelector('[data-comment-id="' + data.parentId + '"]');
        let replies = parent && parent.querySelector('[data-replies]');
        if (replies) replies.insertAdjacentHTML('beforeend', data.html);
      } else {
        const list = postEl.querySelector('[data-comment-list]');
        list.insertAdjacentHTML('beforeend', data.html);
        list.dataset.loaded = '1';
      }

      const counter = postEl.querySelector('[data-comment-count]');
      if (counter) counter.textContent = data.count + (data.count === 1 ? ' comment' : ' comments');

      form.reset();
      input.style.height = 'auto';
      const preview = form.querySelector('[data-comment-preview]');
      if (preview) { preview.innerHTML = ''; preview.classList.add('hidden'); }
      if (form.dataset.dismissAfter) form.classList.add('hidden');
    } catch (err) {
      toast(err.message, 'error');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  // Reply button reveals an inline reply box under the comment.
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-reply-to]');
    if (!btn) return;
    e.preventDefault();
    const comment = btn.closest('[data-comment-id]');
    const box = comment.querySelector('[data-reply-form]');
    if (!box) return;
    box.classList.toggle('hidden');
    if (!box.classList.contains('hidden')) box.querySelector('.comment-input').focus();
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-delete-comment]');
    if (!btn) return;
    e.preventDefault();
    if (!confirm('Delete this comment? This cannot be undone.')) return;

    try {
      const data = await post('/comments/' + btn.dataset.deleteComment + '/delete');
      const comment = btn.closest('[data-comment-id]');
      const postEl = btn.closest('[data-post-id]');
      comment.remove();
      const counter = postEl && postEl.querySelector('[data-comment-count]');
      if (counter) counter.textContent = data.count + (data.count === 1 ? ' comment' : ' comments');
      toast('Comment deleted', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-edit-comment]');
    if (!btn) return;
    e.preventDefault();

    const comment = btn.closest('[data-comment-id]');
    const bodyEl  = comment.querySelector('[data-comment-body]');
    if (comment.querySelector('[data-edit-box]')) return;

    const original = bodyEl.dataset.raw || bodyEl.textContent.trim();
    const box = document.createElement('div');
    box.dataset.editBox = '1';
    box.className = 'mt-8';
    box.innerHTML =
      '<textarea class="input" rows="2"></textarea>' +
      '<div class="row mt-8"><button class="btn btn-primary btn-sm" data-save-edit>Save</button>' +
      '<button class="btn btn-sm" data-cancel-edit>Cancel</button></div>';
    box.querySelector('textarea').value = original;
    bodyEl.classList.add('hidden');
    bodyEl.after(box);
    box.querySelector('textarea').focus();
  });

  document.addEventListener('click', async (e) => {
    if (e.target.closest('[data-cancel-edit]')) {
      e.preventDefault();
      const box = e.target.closest('[data-edit-box]');
      box.previousElementSibling.classList.remove('hidden');
      box.remove();
      return;
    }

    const save = e.target.closest('[data-save-edit]');
    if (!save) return;
    e.preventDefault();

    const box     = save.closest('[data-edit-box]');
    const comment = save.closest('[data-comment-id]');
    const bodyEl  = box.previousElementSibling;
    const value   = box.querySelector('textarea').value.trim();
    if (!value) return;

    try {
      const data = await post('/comments/' + comment.dataset.commentId + '/update', { content: value });
      bodyEl.innerHTML = data.content;
      bodyEl.dataset.raw = value;
      bodyEl.classList.remove('hidden');
      box.remove();
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  /* ------------------------------------------------------------ post menus */

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-delete-post]');
    if (!btn) return;
    e.preventDefault();
    if (!confirm('Delete this post? This cannot be undone.')) return;

    try {
      await post('/posts/' + btn.dataset.deletePost + '/delete');
      const card = btn.closest('[data-post-id]');
      if (card) card.remove();
      toast('Post deleted', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-save-post]');
    if (!btn) return;
    e.preventDefault();

    try {
      const data = await post('/posts/' + btn.dataset.savePost + '/save');
      btn.classList.toggle('is-saved', data.saved);
      const label = btn.querySelector('[data-save-label]');
      if (label) label.textContent = data.saved ? 'Remove from saved' : 'Save post';
      toast(data.message, 'success');
      closeDropdowns(null);
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  /* -------------------------------------------------------- friend actions */

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-friend-action]');
    if (!btn) return;
    e.preventDefault();

    const card = btn.closest('[data-person-card]');
    btn.disabled = true;

    try {
      const data = await post(btn.dataset.friendAction);

      if (btn.dataset.removeCard && card) {
        card.style.transition = 'opacity .2s';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 200);
      }
      if (btn.dataset.replaceWith) {
        btn.outerHTML = btn.dataset.replaceWith;
      }
      if (data.following !== undefined) {
        btn.querySelector('[data-follow-label]')?.replaceChildren(
          document.createTextNode(data.following ? 'Following' : 'Follow')
        );
      }
      if (btn.dataset.toastText) toast(btn.dataset.toastText, 'success');
    } catch (err) {
      toast(err.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  /* ------------------------------------------------------ composer (modal) */

  function initComposer(root) {
    const textarea  = root.querySelector('[data-composer-text]');
    const fileInput = root.querySelector('[data-composer-files]');
    const previews  = root.querySelector('[data-composer-previews]');
    const tray      = root.querySelector('[data-composer-tray]');
    const submit    = root.querySelector('[data-composer-submit]');
    const bgPicker  = root.querySelector('[data-bg-picker]');
    const bgField   = root.querySelector('input[name="background"]');

    // DataTransfer lets us remove individual files from the input's FileList.
    let files = new DataTransfer();

    function refresh() {
      const hasText = textarea && textarea.value.trim().length > 0;
      if (submit) submit.disabled = !hasText && files.files.length === 0;
      if (bgPicker) bgPicker.classList.toggle('hidden', files.files.length > 0 || (textarea && textarea.value.length > 200));
    }

    function renderPreviews() {
      if (!previews) return;
      previews.innerHTML = '';
      Array.from(files.files).forEach((file, index) => {
        const cell = document.createElement('div');
        cell.className = 'composer-preview';
        const url = URL.createObjectURL(file);
        cell.innerHTML = file.type.startsWith('video/')
          ? '<video src="' + url + '" muted></video>'
          : '<img src="' + url + '" alt="">';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove';
        remove.setAttribute('aria-label', 'Remove attachment');
        remove.textContent = '×';
        remove.addEventListener('click', () => {
          const next = new DataTransfer();
          Array.from(files.files).forEach((f, i) => { if (i !== index) next.items.add(f); });
          files = next;
          fileInput.files = files.files;
          renderPreviews();
          refresh();
        });
        cell.appendChild(remove);
        previews.appendChild(cell);
      });
      if (tray) tray.classList.toggle('hidden', files.files.length === 0);
    }

    if (fileInput) {
      fileInput.addEventListener('change', () => {
        Array.from(fileInput.files).forEach((f) => {
          if (files.files.length < 8) files.items.add(f);
        });
        fileInput.files = files.files;
        renderPreviews();
        refresh();
        if (bgField) bgField.value = '';
        if (textarea) textarea.classList.remove('has-bg');
        if (textarea) textarea.style.background = '';
      });
    }

    if (textarea) textarea.addEventListener('input', refresh);

    if (bgPicker) {
      bgPicker.addEventListener('click', (e) => {
        const swatch = e.target.closest('.bg-swatch');
        if (!swatch) return;
        e.preventDefault();
        bgPicker.querySelectorAll('.bg-swatch').forEach((s) => s.classList.remove('is-active'));
        swatch.classList.add('is-active');
        const value = swatch.dataset.bg || '';
        if (bgField) bgField.value = value;
        if (textarea) {
          textarea.classList.toggle('has-bg', value !== '');
          textarea.style.background = value ? swatch.dataset.css : '';
        }
      });
    }

    refresh();
  }

  $$('[data-composer]').forEach(initComposer);

  /* --------------------------------------------------------- infinite feed */

  const feed = $('[data-feed]');
  if (feed) {
    const sentinel = $('[data-feed-sentinel]');
    let offset  = parseInt(feed.dataset.nextOffset || '0', 10);
    let loading = false;
    let done    = feed.dataset.hasMore === '0';

    async function loadMore() {
      if (loading || done) return;
      loading = true;
      sentinel.innerHTML = '<div class="spinner"></div>';

      try {
        const data = await api('/feed/more?offset=' + offset);
        feed.insertAdjacentHTML('beforeend', data.html);
        offset = data.nextOffset;
        done   = !data.hasMore;
        sentinel.innerHTML = done
          ? '<p class="muted center small" style="padding:24px">You are all caught up.</p>'
          : '';
      } catch (err) {
        sentinel.innerHTML = '<p class="muted center small" style="padding:16px">' + err.message + '</p>';
        done = true;
      } finally {
        loading = false;
      }
    }

    if (sentinel && 'IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) loadMore();
      }, { rootMargin: '600px' }).observe(sentinel);
    }
  }

  /* ------------------------------------------------------ search typeahead */

  const searchInput = $('[data-typeahead]');
  if (searchInput) {
    const panel = $('#typeahead-panel');
    let timer = null;

    searchInput.addEventListener('input', () => {
      clearTimeout(timer);
      const term = searchInput.value.trim();
      if (term.length < 2) { panel.classList.remove('is-open'); return; }

      timer = setTimeout(async () => {
        try {
          const data = await api('/api/search?q=' + encodeURIComponent(term));
          if (!data.results.length) {
            panel.innerHTML = '<p class="muted small card-pad">No matches for "' + term.replace(/</g, '&lt;') + '"</p>';
          } else {
            panel.innerHTML = data.results.map((r) =>
              '<a class="menu-item" href="' + r.url + '">' +
              '<img class="avatar avatar-36" style="' + (r.rounded ? '' : 'border-radius:8px') + '" src="' +
              (r.image || '/avatar/0?n=' + encodeURIComponent(r.label)) + '" alt="">' +
              '<span class="grow truncate"><span class="bold">' + r.label + '</span>' +
              '<br><span class="menu-sub">' + r.sub + '</span></span></a>'
            ).join('') +
            '<a class="menu-item" href="/search?q=' + encodeURIComponent(term) + '">' +
            '<span class="menu-glyph">🔍</span><span class="grow">See all results</span></a>';
          }
          panel.classList.add('is-open');
        } catch (e) { /* typeahead failures stay silent */ }
      }, 220);
    });

    searchInput.addEventListener('focus', () => {
      if (searchInput.value.trim().length >= 2 && panel.innerHTML) panel.classList.add('is-open');
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-pill') && !e.target.closest('#typeahead-panel')) {
        panel.classList.remove('is-open');
      }
    });
  }

  /* ------------------------------------------------------------- messenger */

  const thread = $('[data-thread]');
  if (thread) {
    const body   = $('[data-thread-body]');
    const form   = $('[data-thread-form]');
    const input  = form && form.querySelector('.thread-input');
    const convId = thread.dataset.thread;
    let lastId   = parseInt(thread.dataset.lastId || '0', 10);

    const scrollDown = () => { body.scrollTop = body.scrollHeight; };
    scrollDown();

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = form.querySelector('input[type="file"]');
        if (!input.value.trim() && (!file || !file.files.length)) return;

        const data = new FormData(form);
        input.value = '';
        input.style.height = 'auto';

        try {
          const res = await api('/messages/' + convId, { method: 'POST', body: data });
          body.insertAdjacentHTML('beforeend', res.html);
          lastId = res.id;
          scrollDown();
          form.reset();
          const preview = form.querySelector('[data-attachment-preview]');
          if (preview) { preview.innerHTML = ''; preview.classList.add('hidden'); }
        } catch (err) {
          toast(err.message, 'error');
        }
      });
    }

    // Poll for incoming messages while the tab is visible.
    setInterval(async () => {
      if (document.hidden) return;
      try {
        const data = await api('/messages/' + convId + '/poll?after=' + lastId);
        if (data.count > 0) {
          const nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 160;
          body.insertAdjacentHTML('beforeend', data.html);
          lastId = data.lastId;
          if (nearBottom) scrollDown();
        }
      } catch (e) { /* transient network errors are ignored */ }
    }, 4000);
  }

  /* Keep the top-bar message badge fresh. */
  const msgBadgeHolder = $('[data-message-badge]');
  if (msgBadgeHolder) {
    setInterval(async () => {
      if (document.hidden) return;
      try {
        const data = await api('/api/messages/unread');
        let badge = msgBadgeHolder.querySelector('.badge');
        if (data.count > 0) {
          if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge';
            msgBadgeHolder.appendChild(badge);
          }
          badge.textContent = data.count > 99 ? '99+' : data.count;
        } else if (badge) {
          badge.remove();
        }
      } catch (e) { /* ignore */ }
    }, 20000);
  }

  /* ---------------------------------------------------------- attachments */

  document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-file-preview]');
    if (!input || !input.files.length) return;

    const target = document.querySelector(input.dataset.filePreview);
    if (!target) return;

    const file = input.files[0];
    const url  = URL.createObjectURL(file);
    target.innerHTML = file.type.startsWith('video/')
      ? '<video src="' + url + '" controls style="max-height:220px;border-radius:8px"></video>'
      : '<img src="' + url + '" alt="" style="max-height:220px;border-radius:8px">';
    target.classList.remove('hidden');
  });

  // "Choose a photo" buttons that proxy to a hidden file input.
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-trigger-file]');
    if (!btn) return;
    e.preventDefault();
    const input = document.querySelector(btn.dataset.triggerFile);
    if (input) input.click();
  });

  // Auto-submit the picker forms for avatar / cover so there is no extra step.
  document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-auto-submit]');
    if (input && input.files.length) input.closest('form').submit();
  });

  /* ---------------------------------------------------------- story viewer */

  const viewer = $('[data-story-viewer]');
  if (viewer) {
    const slides = $$('.slide', viewer);
    const bars   = $$('.story-progress span', viewer);
    const DURATION = 5000;
    let index = 0;
    let timer = null;
    let start = 0;
    let paused = false;

    function mark(storyId) {
      post('/stories/' + storyId + '/seen').catch(() => {});
    }

    function show(i) {
      if (i < 0) { goPrevUser(); return; }
      if (i >= slides.length) { goNextUser(); return; }

      index = i;
      slides.forEach((s, n) => s.classList.toggle('is-active', n === i));
      bars.forEach((b, n) => {
        b.classList.toggle('is-done', n < i);
        b.querySelector('i').style.width = n < i ? '100%' : '0';
      });
      mark(slides[i].dataset.storyId);
      run();
    }

    function run() {
      clearInterval(timer);
      start = Date.now();
      const fill = bars[index] && bars[index].querySelector('i');
      timer = setInterval(() => {
        if (paused) { start += 40; return; }
        const pct = Math.min(100, ((Date.now() - start) / DURATION) * 100);
        if (fill) fill.style.width = pct + '%';
        if (pct >= 100) { clearInterval(timer); show(index + 1); }
      }, 40);
    }

    function goNextUser() {
      const next = viewer.dataset.nextUser;
      window.location.href = next ? '/stories/user/' + next : '/stories';
    }
    function goPrevUser() {
      const prev = viewer.dataset.prevUser;
      if (prev) window.location.href = '/stories/user/' + prev;
    }

    $('.story-tap.next', viewer)?.addEventListener('click', () => show(index + 1));
    $('.story-tap.prev', viewer)?.addEventListener('click', () => show(index - 1));

    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') show(index + 1);
      if (e.key === 'ArrowLeft') show(index - 1);
      if (e.key === ' ') { e.preventDefault(); paused = !paused; }
    });

    viewer.addEventListener('mousedown', () => { paused = true; });
    viewer.addEventListener('mouseup', () => { paused = false; });

    show(0);
  }

  /* --------------------------------------------------------- form guards */

  // Stop double submits on any regular POST form.
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.commentForm !== undefined || form.dataset.threadForm !== undefined) return;
    if (form.method.toLowerCase() !== 'post') return;

    const btn = form.querySelector('[type="submit"]');
    if (!btn || btn.disabled) return;
    setTimeout(() => {
      btn.disabled = true;
      btn.dataset.originalText = btn.textContent;
      if (!btn.querySelector('svg')) btn.textContent = 'Working…';
    }, 0);
    // Re-enable if the browser restores the page from bfcache.
    window.addEventListener('pageshow', () => {
      btn.disabled = false;
      if (btn.dataset.originalText) btn.textContent = btn.dataset.originalText;
    }, { once: true });
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });

  /* ------------------------------------------------------------ boot ---- */

  try {
    const stored = localStorage.getItem('fc-theme');
    if (stored && stored !== document.documentElement.dataset.themePref) applyTheme(stored);
  } catch (e) { /* ignore */ }
})();
