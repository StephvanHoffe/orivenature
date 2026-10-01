/* ==========================================================================
   Orivé – winkel-interacties
   - Winkelwagen-lade via /cart/add en /cart/change (JSON + server-HTML)
   - Snel bekijken via /products/{handle}/quick-view
   - Checkout: totalen live herberekenen
   - Speelse beweging: parallax, tilt, marquees, scroll-reveals
   ========================================================================== */
(() => {
  'use strict';

  const html = document.documentElement;
  const theme = window.shop || {};
  const routes = Object.assign({ cart: '/cart', cartAdd: '/cart/add', cartChange: '/cart/change', quote: '/checkout/quote', newsletter: '/newsletter' }, theme.routes);
  const strings = Object.assign({ added: 'Toegevoegd!', error: 'Er ging iets mis. Probeer het opnieuw.', soldOut: 'uitverkocht', addToCart: 'toevoegen aan winkelwagen' }, theme.strings);
  const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || html.classList.contains('no-motion');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const clamp = (n, min, max) => Math.min(max, Math.max(min, n));

  /* ------------------------------------------------------------------
     Dialogen (menu, winkelwagen, snel bekijken)
     ------------------------------------------------------------------ */
  function openDialog(dlg) {
    if (!dlg || dlg.open) return;
    dlg.showModal();
    document.body.classList.add('is-locked');
    requestAnimationFrame(() => requestAnimationFrame(() => dlg.classList.add('is-open')));
    if (dlg.id === 'menu') $$('[data-menu-open]').forEach((b) => b.setAttribute('aria-expanded', 'true'));
  }

  function closeDialog(dlg) {
    return new Promise((resolve) => {
      if (!dlg || !dlg.open) { resolve(); return; }
      dlg.classList.remove('is-open');
      if (dlg.id === 'menu') $$('[data-menu-open]').forEach((b) => b.setAttribute('aria-expanded', 'false'));
      setTimeout(() => {
        dlg.close();
        if (!$('dialog[open]')) document.body.classList.remove('is-locked');
        resolve();
      }, reduceMotion ? 0 : 420);
    });
  }

  function scrollToId(hash) {
    const target = hash.length > 1 ? document.getElementById(hash.slice(1)) : null;
    if (target) target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
  }

  document.addEventListener('cancel', (e) => {
    if (e.target.matches('dialog[data-drawer]')) { e.preventDefault(); closeDialog(e.target); }
  }, true);

  document.addEventListener('click', (e) => {
    const dlg = e.target.closest('dialog[data-drawer]');
    if (dlg && e.target === dlg) {
      const r = dlg.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) closeDialog(dlg);
    }
    const closer = e.target.closest('[data-close]');
    if (closer && dlg) {
      const href = closer.getAttribute('href');
      if (href && href.startsWith('#')) {
        e.preventDefault();
        closeDialog(dlg).then(() => scrollToId(href));
      } else if (!href) {
        closeDialog(dlg);
      }
    }
    if (e.target.closest('[data-menu-open]')) openDialog($('#menu'));
    const cartOpen = e.target.closest('[data-cart-open]');
    if (cartOpen && !document.body.classList.contains('template-cart')) {
      e.preventDefault();
      openDialog($('#cart'));
    }
  });

  /* ------------------------------------------------------------------
     Winkelwagen
     ------------------------------------------------------------------ */
  function renderDrawer(drawerHtml) {
    const host = $('[data-cart-host]');
    if (host && drawerHtml) host.innerHTML = drawerHtml;
  }

  function updateCartCount(count) {
    if (count === undefined) {
      const drawer = $('[data-cart-drawer]');
      count = drawer ? Number(drawer.dataset.count || 0) : 0;
    }
    $$('[data-cart-count]').forEach((el) => { el.textContent = count; el.hidden = Number(count) === 0; });
  }

  const firstError = (data) => (data && data.errors ? Object.values(data.errors).flat()[0] : data && data.message);

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(firstError(data) || strings.error);
    return data;
  }

  async function cartRequest(url, body) {
    const data = await postJson(url, body);
    renderDrawer(data.drawer);
    updateCartCount(data.count);
    return data;
  }

  const addItem = (variantId, quantity) => cartRequest(routes.cartAdd, { variant_id: Number(variantId), quantity: Number(quantity) || 1 });
  const changeLine = (variantId, quantity) => cartRequest(routes.cartChange, { variant_id: Number(variantId), quantity: Number(quantity) });

  document.addEventListener('click', async (e) => {
    const qtyBtn = e.target.closest('#cart [data-line-variant]');
    if (qtyBtn) {
      const drawer = $('[data-cart-drawer]');
      if (drawer) drawer.classList.add('is-loading');
      try { await changeLine(qtyBtn.dataset.lineVariant, Number(qtyBtn.dataset.lineQty)); } catch (err) { window.alert(err.message); }
      const fresh = $('[data-cart-drawer]');
      if (fresh) fresh.classList.remove('is-loading');
      return;
    }
    const upsellBtn = e.target.closest('[data-upsell]');
    if (upsellBtn) {
      burst(upsellBtn);
      try { await addItem(upsellBtn.dataset.upsell, 1); bumpCart(); } catch (err) { window.alert(err.message); }
    }
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-product-form]');
    if (!form) return;
    e.preventDefault();
    const button = $('[data-add]', form);
    const errorEl = $('[data-form-error]', form);
    const fd = new FormData(form);
    const variantId = Number(fd.get('variant_id'));
    const quantity = Math.max(1, Number(fd.get('quantity') || 1));
    const card = form.closest('.pcard');
    const modal = form.closest('#quickview');
    const sourceImg = card ? $('.pcard__img', card) : $('.qv__main-img', form.closest('.qv') || document);

    if (errorEl) errorEl.hidden = true;
    if (button) button.setAttribute('aria-busy', 'true');
    try {
      const from = sourceImg ? { rect: sourceImg.getBoundingClientRect(), src: sourceImg.currentSrc || sourceImg.src } : null;
      await addItem(variantId, quantity);
      if (button) celebrate(button);
      if (modal) await closeDialog(modal);
      if (from && !reduceMotion) await flyToCart(from);
      bumpCart();
      if (theme.openCartOnAdd !== false) openDialog($('#cart'));
    } catch (err) {
      if (errorEl) { errorEl.textContent = err.message; errorEl.hidden = false; } else { window.alert(err.message); }
    } finally {
      if (button) button.removeAttribute('aria-busy');
    }
  });

  function celebrate(button) {
    burst(button);
    const label = $('[data-add-label]', button);
    if (!label) return;
    const original = label.textContent;
    button.classList.add('is-added');
    label.textContent = strings.added;
    setTimeout(() => { button.classList.remove('is-added'); label.textContent = original; }, 1600);
  }

  function bumpCart() {
    $$('.cart-btn').forEach((btn) => { btn.classList.remove('is-bump'); void btn.offsetWidth; btn.classList.add('is-bump'); });
  }

  function flyToCart({ rect: from, src }) {
    const cartBtn = $('.cart-btn');
    if (!cartBtn || !from.width) return Promise.resolve();
    const to = cartBtn.getBoundingClientRect();
    const clone = document.createElement('img');
    clone.src = src;
    clone.className = 'fly-img';
    clone.alt = '';
    const w = Math.min(from.width, 220);
    const h = (from.height / from.width) * w;
    const x0 = from.left + from.width / 2 - w / 2;
    const y0 = from.top + from.height / 2 - h / 2;
    Object.assign(clone.style, { left: `${x0}px`, top: `${y0}px`, width: `${w}px`, height: `${h}px`, objectFit: 'contain' });
    document.body.appendChild(clone);
    const dx = to.left + to.width / 2 - (x0 + w / 2);
    const dy = to.top + to.height / 2 - (y0 + h / 2);
    const anim = clone.animate([
      { transform: 'translate(0,0) scale(1) rotate(0deg)', opacity: 1 },
      { transform: `translate(${dx * 0.35}px, ${dy * 0.35 - 120}px) scale(.7) rotate(-18deg)`, opacity: 1, offset: 0.45 },
      { transform: `translate(${dx}px, ${dy}px) scale(.08) rotate(25deg)`, opacity: 0.4 },
    ], { duration: 820, easing: 'cubic-bezier(.5,0,.3,1)' });
    return anim.finished.then(() => clone.remove()).catch(() => clone.remove());
  }

  function burst(el) {
    if (reduceMotion) return;
    const r = el.getBoundingClientRect();
    const cx = r.left + r.width / 2;
    const cy = r.top + r.height / 2;
    const styles = getComputedStyle(html);
    const colors = ['--sage', '--mauve', '--olive', '--ink'].map((v) => styles.getPropertyValue(v).trim() || '#52572e');
    // In een open dialoog tekenen, anders vallen de stipjes achter de top layer
    const host = el.closest('dialog[open]') || document.body;
    for (let i = 0; i < 18; i++) {
      const dot = document.createElement('span');
      dot.className = 'burst-dot';
      const size = 5 + Math.random() * 9;
      Object.assign(dot.style, { left: `${cx}px`, top: `${cy}px`, width: `${size}px`, height: `${size}px`, background: colors[i % colors.length] });
      host.appendChild(dot);
      const angle = Math.random() * Math.PI * 2;
      const dist = 50 + Math.random() * 90;
      dot.animate([
        { transform: 'translate(-50%,-50%) scale(1)', opacity: 1 },
        { transform: `translate(calc(-50% + ${Math.cos(angle) * dist}px), calc(-50% + ${Math.sin(angle) * dist - 30}px)) scale(.2)`, opacity: 0 },
      ], { duration: 650 + Math.random() * 350, easing: 'cubic-bezier(.2,.8,.3,1)' }).finished.then(() => dot.remove()).catch(() => dot.remove());
    }
  }

  /* ------------------------------------------------------------------
     Productkaarten met inhoudskeuze (sectie "Productkaarten")
     ------------------------------------------------------------------ */
  function initCards(root) {
    $$('[data-pcard]', root).forEach((card) => {
      if (card.dataset.ready) return;
      card.dataset.ready = '1';
      let options = [];
      try { options = JSON.parse($('[data-options]', card).textContent); } catch (err) { return; }
      card.dataset.index = '0';
      options.forEach((o) => { if (o.image) { const i = new Image(); i.src = o.image; } });

      card.addEventListener('click', (e) => {
        const opt = e.target.closest('.sizes__opt');
        if (opt) setCardOption(card, options, Number(opt.dataset.size));
      });
    });
  }

  function setCardOption(card, options, index) {
    const option = options[index];
    if (!option || card.dataset.index === String(index)) return;
    card.dataset.index = String(index);

    const sizes = $('.sizes', card);
    sizes.dataset.active = String(index);
    $$('.sizes__opt', card).forEach((b) => b.setAttribute('aria-pressed', String(Number(b.dataset.size) === index)));

    const price = $('[data-price]', card);
    price.textContent = option.price;
    price.classList.remove('is-tick'); void price.offsetWidth; price.classList.add('is-tick');

    const image = $('.pcard__img', card);
    if (image && option.image) {
      const swap = () => { image.removeAttribute('srcset'); image.src = option.image; image.alt = option.alt; };
      if (reduceMotion) swap();
      else {
        image.classList.remove('is-swapping'); void image.offsetWidth; image.classList.add('is-swapping');
        setTimeout(swap, 220);
        image.addEventListener('animationend', () => image.classList.remove('is-swapping'), { once: true });
      }
    }

    const badge = $('[data-bestseller]', card);
    if (badge) badge.hidden = !option.bestseller;
    $$('[data-quickview]', card).forEach((a) => { a.dataset.quickview = option.quick; if (a.tagName === 'A') a.href = option.url; });

    const save = $('[data-save]', card);
    if (save) { save.textContent = option.save; save.hidden = !option.save; }

    const input = $('[data-variant-input]', card);
    if (input) input.value = option.variantId;
    const button = $('[data-add]', card);
    if (button) {
      button.disabled = !option.available;
      const label = $('[data-add-label]', button);
      if (label) label.textContent = option.available ? strings.addToCart : strings.soldOut;
    }
  }

  // Smaakkeuze in de hero → naar de juiste kaart
  document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-jump]');
    if (!link) return;
    const card = $$('[data-pcard]').find((c) => (c.dataset.handles || '').split(' ').includes(link.dataset.jump));
    if (!card) return;
    e.preventDefault();
    card.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
    card.classList.remove('is-flash');
    setTimeout(() => card.classList.add('is-flash'), reduceMotion ? 0 : 450);
    card.addEventListener('animationend', () => card.classList.remove('is-flash'), { once: true });
  });

  // Bevestiging voor formulieren met data-confirm (bijv. adres verwijderen)
  document.addEventListener('submit', (e) => {
    const message = e.target.dataset && e.target.dataset.confirm;
    if (message && !window.confirm(message)) e.preventDefault();
  });

  /* ------------------------------------------------------------------
     Snel bekijken
     ------------------------------------------------------------------ */
  const qvDialog = $('#quickview');
  const qvHost = $('[data-qv]');

  async function loadQuickView(url) {
    qvHost.classList.add('is-loading');
    qvHost.innerHTML = '';
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error(strings.error);
      qvHost.innerHTML = await res.text();
      initProductUI(qvHost);
    } catch (err) {
      window.location.href = url.replace('/quick-view', '');
    } finally {
      qvHost.classList.remove('is-loading');
    }
  }

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-quickview]');
    if (!trigger || !qvDialog || e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    const fromCart = trigger.closest('#cart');
    (fromCart ? closeDialog($('#cart')) : Promise.resolve()).then(() => {
      openDialog(qvDialog);
      loadQuickView(trigger.dataset.quickview);
    });
  });

  /* Galerij, aantal en variant (productpagina én snel bekijken) */
  function initProductUI(root) {
    $$('[data-gallery]', root).forEach((gallery) => {
      const main = $('.qv__main-img', gallery);
      gallery.addEventListener('click', (e) => {
        const thumb = e.target.closest('[data-thumb]');
        if (!thumb || !main) return;
        $$('[data-thumb]', gallery).forEach((t) => t.setAttribute('aria-current', String(t === thumb)));
        main.classList.add('is-fading');
        setTimeout(() => {
          main.removeAttribute('srcset');
          main.src = thumb.dataset.thumb;
          main.alt = thumb.dataset.alt || '';
          main.classList.toggle('is-pack', thumb.dataset.pack === 'true');
          main.classList.remove('is-fading');
        }, reduceMotion ? 0 : 180);
      });
    });

    $$('[data-product]', root).forEach((product) => {
      if (product.dataset.ready) return;
      product.dataset.ready = '1';
      let options = [];
      try { options = JSON.parse($('[data-product-options]', product).textContent); } catch (err) { options = []; }
      const form = $('[data-product-form]', product);
      const qty = $('.qty__input', product);

      product.addEventListener('click', (e) => {
        const step = e.target.closest('[data-qty-step]');
        if (step && qty) qty.value = clamp(Number(qty.value || 1) + Number(step.dataset.qtyStep), 1, 99);

        const pill = e.target.closest('[data-variant-index]');
        if (!pill) return;
        const option = options[Number(pill.dataset.variantIndex)];
        if (!option) return;
        $$('[data-variant-index]', product).forEach((p) => p.setAttribute('aria-current', String(p === pill)));
        const input = $('[name="variant_id"]', form);
        if (input) input.value = option.variantId;
        const price = $('[data-product-price]', product);
        if (price) price.innerHTML = option.price + (option.compareAt ? ` <s>${option.compareAt}</s>` : '');
        const save = $('[data-save]', product);
        if (save) { save.textContent = option.save || ''; save.hidden = !option.save; }
        const points = $('[data-loyalty-text]', product);
        if (points && option.points) points.textContent = option.points;
        const badge = $('[data-bestseller]', product);
        if (badge) badge.hidden = !option.bestseller;
        const button = $('[data-add]', product);
        if (button) {
          button.disabled = !option.available;
          const label = $('[data-add-label]', button);
          if (label) label.textContent = option.available ? strings.addToCart : strings.soldOut;
        }
        const main = $('.qv__main-img', product);
        if (main && option.image) {
          main.classList.add('is-fading');
          setTimeout(() => { main.removeAttribute('srcset'); main.src = option.image; main.alt = option.alt; main.classList.add('is-pack'); main.classList.remove('is-fading'); }, reduceMotion ? 0 : 180);
        }
        if (!product.closest('#quickview') && window.history.replaceState) {
          window.history.replaceState({}, '', option.url);
        }
      });
    });
  }

  /* ------------------------------------------------------------------
     Aankondiging: aftellen tot de besteltijd (Nederlandse tijd)
     ------------------------------------------------------------------ */
  const amsterdamClock = new Intl.DateTimeFormat('nl-NL', { timeZone: 'Europe/Amsterdam', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
  function tickCutoff() {
    $$('[data-cutoff]').forEach((timer) => {
      const parts = Object.fromEntries(amsterdamClock.formatToParts(new Date()).map((p) => [p.type, p.value]));
      const now = Number(parts.hour) * 3600 + Number(parts.minute) * 60 + Number(parts.second);
      const left = Number(timer.dataset.cutoffHour || 22) * 3600 - now;
      if (left <= 0) { timer.hidden = true; return; }
      const h = Math.floor(left / 3600);
      const m = String(Math.floor((left % 3600) / 60)).padStart(2, '0');
      const s = String(left % 60).padStart(2, '0');
      timer.innerHTML = (timer.dataset.label || 'nog [h]u [m]m[sec]')
        .replace('[h]', h).replace('[m]', m)
        .replace('[sec]', `<span class="announce__sec"> ${s}s</span>`);
      timer.hidden = false;
    });
  }
  tickCutoff();
  setInterval(tickCutoff, 1000);

  // Op mobiel wisselen de berichten elkaar af
  let announceIndex = 0;
  setInterval(() => {
    const msgs = $$('[data-announce]');
    if (msgs.length < 2) return;
    msgs.forEach((m) => m.classList.remove('is-active'));
    announceIndex = (announceIndex + 1) % msgs.length;
    msgs[announceIndex].classList.add('is-active');
  }, 4500);

  /* ------------------------------------------------------------------
     Header schaduw bij scrollen
     ------------------------------------------------------------------ */
  const onHeader = () => { const header = $('[data-header]'); if (header) header.classList.toggle('is-scrolled', window.scrollY > 10); };
  onHeader();
  window.addEventListener('scroll', onHeader, { passive: true });

  /* ------------------------------------------------------------------
     Scroll-reveals, marquees, woorden, parallax, tilt, video, FAQ
     ------------------------------------------------------------------ */
  const revealIO = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) { entry.target.classList.add('is-in'); revealIO.unobserve(entry.target); }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

  const videoIO = new IntersectionObserver((entries) => {
    entries.forEach(({ target: video, isIntersecting }) => {
      if (isIntersecting) {
        if (!video.src) video.src = window.innerWidth < 700 ? (video.dataset.srcSmall || video.dataset.src) : video.dataset.src;
        if (!reduceMotion) video.play().catch(() => {});
      } else if (!video.paused) {
        video.pause();
      }
    });
  }, { threshold: 0.25 });

  let parallaxEls = [];
  let storyBlocks = [];

  function initMarquee(track) {
    if (track.dataset.ready) return;
    track.dataset.ready = '1';
    const items = Array.from(track.children);
    if (!items.length) return;
    for (let i = 0; i < 8 && track.scrollWidth > 0 && track.scrollWidth < window.innerWidth * 1.2; i++) {
      items.forEach((node) => { const c = node.cloneNode(true); c.setAttribute('aria-hidden', 'true'); c.removeAttribute('data-shopify-editor-block'); track.appendChild(c); });
    }
    Array.from(track.children).forEach((node) => {
      const c = node.cloneNode(true);
      c.setAttribute('aria-hidden', 'true');
      c.removeAttribute('data-shopify-editor-block');
      track.appendChild(c);
    });
  }

  function initStory(el) {
    if (el.dataset.ready) return;
    el.dataset.ready = '1';
    const text = el.textContent.trim();
    el.setAttribute('aria-label', text);
    el.innerHTML = text.split(/\s+/).map((w) => `<span class="word" aria-hidden="true">${w.replace(/</g, '&lt;')}</span>`).join(' ');
    const words = $$('.word', el);
    if (reduceMotion || html.classList.contains('design-mode')) words.forEach((w) => w.classList.add('is-on'));
    storyBlocks.push({ el, words });
  }

  function initTilt(el) {
    if (!finePointer || reduceMotion || el.dataset.tiltReady) return;
    el.dataset.tiltReady = '1';
    el.addEventListener('pointermove', (e) => {
      const r = el.getBoundingClientRect();
      el.style.setProperty('--tx', (((e.clientX - r.left) / r.width) * 2 - 1).toFixed(3));
      el.style.setProperty('--ty', (((e.clientY - r.top) / r.height) * 2 - 1).toFixed(3));
    });
    el.addEventListener('pointerleave', () => { el.style.setProperty('--tx', 0); el.style.setProperty('--ty', 0); });
  }

  function initHero(hero) {
    if (!finePointer || reduceMotion || hero.dataset.ready) return;
    hero.dataset.ready = '1';
    let raf = 0;
    hero.addEventListener('pointermove', (e) => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        const r = hero.getBoundingClientRect();
        hero.style.setProperty('--mx', (((e.clientX - r.left) / r.width) * 2 - 1).toFixed(3));
        hero.style.setProperty('--my', (((e.clientY - r.top) / r.height) * 2 - 1).toFixed(3));
      });
    });
    hero.addEventListener('pointerleave', () => { hero.style.setProperty('--mx', 0); hero.style.setProperty('--my', 0); });
  }

  function initFaq(details) {
    if (details.dataset.ready) return;
    details.dataset.ready = '1';
    const summary = $('summary', details);
    const body = $('.acc__body', details);
    summary.addEventListener('click', (e) => {
      if (reduceMotion) return;
      e.preventDefault();
      const pad = getComputedStyle(body).paddingBottom;
      if (details.open) {
        const anim = body.animate([{ height: `${body.offsetHeight}px`, paddingBottom: pad, opacity: 1 }, { height: '0px', paddingBottom: '0px', opacity: 0 }], { duration: 300, easing: 'ease-in' });
        anim.onfinish = () => { details.open = false; };
      } else {
        details.open = true;
        body.animate([{ height: '0px', paddingBottom: '0px', opacity: 0 }, { height: `${body.offsetHeight}px`, paddingBottom: pad, opacity: 1 }], { duration: 420, easing: 'cubic-bezier(.22,1,.36,1)' });
      }
    });
  }

  function initSubscribe(form) {
    if (form.dataset.ready) return;
    form.dataset.ready = '1';
    const input = $('input[type="email"]', form);
    if (input) input.addEventListener('invalid', () => { form.classList.remove('is-shake'); void form.offsetWidth; form.classList.add('is-shake'); });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      let note = $('[data-subscribe-note]', form.parentElement);
      if (!note) {
        note = document.createElement('p');
        note.className = 'subscribe__note';
        note.setAttribute('data-subscribe-note', '');
        note.setAttribute('role', 'status');
        form.after(note);
      }
      try {
        const data = await postJson(form.action, { email: input.value, source: form.dataset.source || 'website' });
        note.textContent = data.message;
        form.reset();
        burst($('button', form));
      } catch (err) {
        note.textContent = err.message;
        form.classList.remove('is-shake'); void form.offsetWidth; form.classList.add('is-shake');
      }
    });
  }

  /* ------------------------------------------------------------------
     Checkout: verzendkosten, kortingscode, cadeaubon en tegoed live herberekenen
     ------------------------------------------------------------------ */
  function initCheckout(form) {
    if (form.dataset.ready) return;
    form.dataset.ready = '1';
    const summary = $('[data-checkout-summary]');
    const ratesHost = $('[data-shipping-rates]', form);
    let pending = null;

    const renderRates = (pricing) => {
      if (!ratesHost) return;
      if (!pricing.shipping_available) {
        ratesHost.innerHTML = `<p class="form-error">${ratesHost.dataset.unavailable}</p>`;
        return;
      }
      ratesHost.innerHTML = pricing.shipping_rates.map((r) => `
        <label class="choice">
          <input type="radio" name="shipping_rate_id" value="${r.id}" ${r.id === pricing.shipping_rate_id ? 'checked' : ''}>
          <span class="choice__body"><strong>${r.name}</strong>${r.description ? `<small>${r.description}</small>` : ''}</span>
          <span class="choice__price">${r.price_formatted}</span>
        </label>`).join('');
    };

    const quote = async (extra = {}) => {
      if (pending) pending.abort?.();
      const fd = new FormData(form);
      const body = Object.assign({
        email: fd.get('email') || null,
        country_code: fd.get('country_code') || null,
        shipping_rate_id: fd.get('shipping_rate_id') ? Number(fd.get('shipping_rate_id')) : null,
      }, extra);
      summary && summary.classList.add('is-loading');
      try {
        const data = await postJson(routes.quote, body);
        if (summary) summary.innerHTML = data.summary;
        renderRates(data.pricing);
        const discountError = $('[data-discount-error]', form);
        if (discountError) { discountError.textContent = data.pricing.discount_error || ''; discountError.hidden = !data.pricing.discount_error; }
        const giftError = $('[data-gift-error]', form);
        if (giftError) { giftError.textContent = data.pricing.gift_card_error || ''; giftError.hidden = !data.pricing.gift_card_error; }
        const payLabel = $('[data-pay-total]', form);
        if (payLabel) payLabel.textContent = data.pricing.total_formatted || payLabel.textContent;
        const toggleTotal = $('[data-summary-total]');
        if (toggleTotal) toggleTotal.textContent = data.pricing.total_formatted || toggleTotal.textContent;
        return data;
      } catch (err) {
        // stil falen: de server rekent bij het afrekenen alles opnieuw na
      } finally {
        summary && summary.classList.remove('is-loading');
      }
    };

    // Mobiel: besteloverzicht in- en uitklappen
    const toggle = $('[data-summary-toggle]');
    if (toggle) {
      toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        toggle.closest('.checkout').classList.toggle('is-summary-open', open);
        const label = $('[data-toggle-label]', toggle);
        if (label) label.textContent = open ? label.dataset.open : label.dataset.closed;
      });
    }

    form.addEventListener('change', (e) => {
      const name = e.target.name;
      if (name === 'country_code' || name === 'shipping_rate_id') quote();
      if (name === 'use_credit') quote({ use_credit: e.target.checked });
      if (name === 'billing_same') $('[data-billing]', form).hidden = e.target.checked;
    });
    const email = $('[name="email"]', form);
    if (email) email.addEventListener('blur', () => { if (email.validity.valid && email.value) quote(); });

    document.addEventListener('click', async (e) => {
      const apply = e.target.closest('[data-apply]');
      if (apply) {
        e.preventDefault();
        const field = $(`[name="${apply.dataset.apply}"]`, form);
        await quote({ [apply.dataset.apply]: field ? field.value.trim() : '' });
        if (field) field.value = '';
      }
      const remove = e.target.closest('[data-remove-code]');
      if (remove) {
        e.preventDefault();
        await quote({ [remove.dataset.removeCode]: '' });
      }
    });

    form.addEventListener('submit', () => {
      const button = $('[data-pay]', form);
      if (button) { button.setAttribute('aria-busy', 'true'); button.disabled = true; }
    });
  }

  function initRoot(root) {
    $$('[data-reveal]', root).forEach((el) => revealIO.observe(el));
    $$('[data-marquee]', root).forEach(initMarquee);
    $$('[data-words]', root).forEach(initStory);
    $$('[data-tilt]', root).forEach(initTilt);
    $$('[data-hero]', root).forEach(initHero);
    $$('[data-lazy-video]', root).forEach((v) => videoIO.observe(v));
    $$('.acc', root).forEach(initFaq);
    $$('[data-subscribe]', root).forEach(initSubscribe);
    $$('[data-autosubmit]', root).forEach((s) => s.addEventListener('change', () => s.form.submit()));
    $$('[data-checkout-form]', root).forEach(initCheckout);
    initCards(root);
    initProductUI(root);
    parallaxEls = $$('[data-parallax]');
    initSticky();
  }

  function onScrollFrame() {
    ticking = false;
    const vh = window.innerHeight;
    parallaxEls.forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < -200 || r.top > vh + 200) return;
      const offset = r.top + r.height / 2 - vh / 2;
      el.style.transform = `translate3d(0, ${(-offset * Number(el.dataset.parallax)).toFixed(1)}px, 0)`;
    });
    storyBlocks.forEach(({ el, words }) => {
      const r = el.getBoundingClientRect();
      const progress = clamp((vh * 0.85 - r.top) / (r.height + vh * 0.35), 0, 1);
      const lit = Math.round(progress * words.length);
      words.forEach((w, i) => w.classList.toggle('is-on', i < lit));
    });
  }
  let ticking = false;
  if (!reduceMotion) {
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScrollFrame); } }, { passive: true });
    window.addEventListener('resize', onScrollFrame);
  }

  /* ------------------------------------------------------------------
     Sticky CTA (mobiel) en WhatsApp-knop
     ------------------------------------------------------------------ */
  let stickyIO = null;
  function initSticky() {
    const sticky = $('[data-sticky-cta]');
    if (stickyIO) stickyIO.disconnect();
    const seen = { hero: true, target: false, footer: false };
    const update = () => {
      const show = !!sticky && !seen.hero && !seen.target && !seen.footer;
      if (sticky) {
        sticky.classList.toggle('is-visible', show);
        sticky.setAttribute('aria-hidden', String(!show));
        $$('a', sticky).forEach((a) => { a.tabIndex = show ? 0 : -1; });
      }
      document.body.classList.toggle('has-sticky', show);
      document.body.classList.toggle('at-footer', seen.footer);
    };
    stickyIO = new IntersectionObserver((entries) => {
      entries.forEach((entry) => { seen[entry.target.dataset.stickyWatch] = entry.isIntersecting; });
      update();
    });
    const hero = $('[data-hero]');
    const target = sticky ? document.getElementById(sticky.dataset.stickyTarget) : null;
    if (!hero) seen.hero = false;
    [['hero', hero], ['target', target], ['footer', $('.footer')]].forEach(([name, el]) => {
      if (!el) return;
      el.dataset.stickyWatch = name;
      stickyIO.observe(el);
    });
    update();
  }

  const wa = $('[data-wa]');
  try {
    if (wa && !sessionStorage.getItem('orive:wa-tip')) {
      setTimeout(() => {
        wa.classList.add('is-tip');
        setTimeout(() => wa.classList.remove('is-tip'), 4500);
        sessionStorage.setItem('orive:wa-tip', '1');
      }, 14000);
    }
  } catch (err) { /* geen sessionStorage */ }

  /* ------------------------------------------------------------------
     Start + ondersteuning voor de thema-editor
     ------------------------------------------------------------------ */
  initRoot(document);
  updateCartCount();
  if (!reduceMotion) onScrollFrame();

  const ready = () => html.classList.add('is-ready');
  if (document.fonts && document.fonts.ready) {
    Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 700))]).then(ready);
  } else {
    ready();
  }
})();
