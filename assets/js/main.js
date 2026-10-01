/* ==========================================================================
   Orivé – interacties
   - Winkelwagen (localStorage) die afrekent via een Shopify cart-permalink
   - Snel bekijken, inhoudskeuze, fly-to-cart
   - Speelse beweging: parallax, tilt, marquees, scroll-reveals
   ========================================================================== */
(() => {
  'use strict';

  const STORE = 'https://www.orivenature.com';
  const CDN = 'https://cdn.shopify.com/s/files/1/1059/6856/6611/files/';
  const img = (file, width = 600) => `${CDN}${file}&width=${width}`;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const money = (n) => '€' + n.toFixed(2).replace('.', ',');
  const clamp = (n, min, max) => Math.min(max, Math.max(min, n));

  /* ------------------------------------------------------------------
     Productdata (orivenature.com/products.json)
     ------------------------------------------------------------------ */
  const ESSENCE_PERKS = [
    ['i-truck', 'Gratis verzending in (NL/BE)'],
    ['i-clock', 'Voor 22:00 besteld, morgen in huis'],
    ['i-leaf', '100% Biologisch Gecertificeerd'],
    ['i-check', 'Gekeurd in Europa'],
  ];

  const CATALOG = {
    matcha: {
      name: 'Matcha Essence',
      tone: 'var(--sage)',
      desc: ['Ontdek 100% biologische ceremoniële matcha uit Japan. Perfect voor matcha thee, lattes en dagelijkse recepten.'],
      lifestyle: ['021.jpg?v=1782858254', 'image00002.jpg?v=1782858956'],
      variants: {
        50: { id: 53893904302419, price: 37.95, handle: 'matcha-essence-50-gram', front: 'Artboard1.png?v=1782300978', back: 'Artboard2.png?v=1782300957', bestseller: true },
        100: { id: 53953615626579, price: 62.95, handle: 'matcha-essence-100-gram', front: 'matcha100.png?v=1782301513', back: 'matcha100g2.png?v=1782301502' },
      },
    },
    ube: {
      name: 'Ube Essence',
      tone: 'var(--mauve)',
      desc: ['Ontdek 100% biologisch ube poeder uit de Filipijnen. Perfect voor lattes, desserts en authentieke Aziatische recepten.'],
      lifestyle: ['20_1b221b4b-24af-4123-a1a8-f8ab2beb2c3b.jpg?v=1782858151', 'image00008.jpg?v=1782858705'],
      variants: {
        50: { id: 53889257439571, price: 29.95, handle: 'ube-essence-50-gram', front: 'ube50g_38636372-efc2-4472-a349-af02958aeb65.png?v=1782301404', back: 'ube50g2.png?v=1782301385', bestseller: true },
        100: { id: 53953633911123, price: 49.95, handle: 'ube-essence-100-gram', front: 'ube100g.png?v=1782301599', back: 'ube100g2_adabfdb1-11fa-43d5-bd1a-574fe710abed.png?v=1782301627' },
      },
    },
    dragon: {
      name: 'Dragon Fruit Essence',
      tone: 'var(--mauve-soft)',
      desc: ['Ontdek 100% biologisch dragon fruit poeder met een levendige roze kleur en subtiel tropische smaak. Perfect voor lattes, smoothies, bowls en dagelijkse recepten.'],
      lifestyle: ['pv001.png?v=1788899648'],
      variants: {
        50: { id: 54668057379155, price: 17.95, handle: 'dragon-fruit-essence-50gr', front: 'Artboard1_5e94c422-75b7-4b68-8074-e51b2beaa4b1.png?v=1788721066', back: 'Artboard_2.png?v=1788721082', bestseller: true },
        100: { id: 54668065440083, price: 27.95, handle: 'dragon-fruit-essence-100-gram', front: 'Artboard1_b336aac3-de43-4b7c-a1f2-7c418edb8b35.png?v=1788721110', back: 'Artboard_2_3b77cd74-973d-458b-a814-00fab9fd8102.png?v=1788721117' },
      },
    },
    ritual: {
      name: 'Orivé Ritual box',
      tone: 'var(--sage-soft)',
      desc: [
        'Ontdek <strong>The Orivé Ritual Box</strong> ontworpen om van ieder Orivé moment een bijzonder ritueel te maken.',
        'Een zorgvuldig samengestelde set met de essentials om jouw favoriete Orivé producten eenvoudig en stijlvol te bereiden.',
        '<strong>In the box</strong><br>• Orivé bowl<br>• Bamboo whisk (chasen)<br>• Bamboo spoon (chashaku)<br>• Whisk holder',
        'De Ritual Box is ontworpen in de twee kenmerkende kleuren van Orivé: <strong>Black &amp; White</strong>. Iedere box wordt uitgevoerd in één van deze twee signature kleuren, met dezelfde zorgvuldig samengestelde inhoud en herkenbare Orivé-uitstraling.',
        'Geschikt voor <strong>Matcha, Ube, Dragon Fruit en andere Orivé creaties</strong>.',
        '<strong>Two colors. One ritual. Pure Orivé.</strong>',
      ],
      lifestyle: ['box-003.png?v=1789163914'],
      variants: {
        one: { id: 54874813825363, price: 54.95, handle: 'orive-ritual-box', front: 'Artboard1_07440316-18ce-4453-b7f0-8c5d9157142e.png?v=1789470423', back: 'Artboard4_49686089-73f9-48bd-9f63-a5088b97f583.png?v=1789470423' },
      },
    },
  };

  // Variant-ID → { key, size, product, variant }
  const VARIANTS = new Map();
  Object.entries(CATALOG).forEach(([key, product]) => {
    Object.entries(product.variants).forEach(([size, variant]) => {
      VARIANTS.set(String(variant.id), { key, size, product, variant });
    });
  });
  const variantTitle = ({ product, size }) => (size === 'one' ? product.name : `${product.name} ${size} gram`);
  const productUrl = (variant) => `${STORE}/products/${variant.handle}`;
  const saving = (product) => {
    const small = product.variants[50];
    const big = product.variants[100];
    return small && big ? small.price * 2 - big.price : 0;
  };

  /* ------------------------------------------------------------------
     Winkelwagen
     ------------------------------------------------------------------ */
  const CART_KEY = 'orive:cart';
  const Cart = {
    items: [],
    load() {
      try {
        const raw = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        this.items = Array.isArray(raw) ? raw.filter((i) => VARIANTS.has(String(i.id)) && i.qty > 0) : [];
      } catch (e) {
        this.items = [];
      }
    },
    save() {
      try { localStorage.setItem(CART_KEY, JSON.stringify(this.items)); } catch (e) { /* privémodus */ }
      renderCart();
    },
    add(id, qty = 1) {
      const line = this.items.find((i) => String(i.id) === String(id));
      if (line) line.qty = clamp(line.qty + qty, 1, 99);
      else this.items.push({ id: String(id), qty: clamp(qty, 1, 99) });
      this.save();
    },
    set(id, qty) {
      const line = this.items.find((i) => String(i.id) === String(id));
      if (qty <= 0) this.items = this.items.filter((i) => i !== line);
      else if (line) line.qty = clamp(qty, 1, 99);
      this.save();
    },
    count() { return this.items.reduce((n, i) => n + i.qty, 0); },
    subtotal() { return this.items.reduce((n, i) => n + VARIANTS.get(i.id).variant.price * i.qty, 0); },
    // Shopify cart-permalink: /cart/<variant>:<qty>,… gaat direct door naar de checkout
    checkoutUrl() {
      return this.items.length ? `${STORE}/cart/${this.items.map((i) => `${i.id}:${i.qty}`).join(',')}` : `${STORE}/cart`;
    },
  };

  const cartDialog = $('#cart');
  const cartBody = $('[data-cart-body]');
  const cartFoot = $('[data-cart-foot]');

  function renderCart() {
    const count = Cart.count();
    $$('[data-cart-count]').forEach((el) => { el.textContent = count; el.hidden = count === 0; });
    $('[data-cart-count-label]').textContent = count ? `(${count})` : '';
    $('[data-cart-subtotal]').textContent = money(Cart.subtotal());
    $('[data-checkout]').href = Cart.checkoutUrl();
    cartFoot.hidden = count === 0;

    if (!count) {
      cartBody.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty__pouches" aria-hidden="true">
            <img src="${img(CATALOG.matcha.variants[50].front, 200)}" alt="">
            <img src="${img(CATALOG.ube.variants[50].front, 200)}" alt="">
            <img src="${img(CATALOG.dragon.variants[50].front, 200)}" alt="">
          </div>
          <h3>Je winkelwagen is leeg</h3>
          <a class="btn btn--primary" href="#essence" data-close>Doorgaan met winkelen</a>
        </div>`;
      return;
    }

    const lines = Cart.items.map((item) => {
      const v = VARIANTS.get(item.id);
      return `
        <div class="line" style="--tone:${v.product.tone}">
          <a class="line__img" href="${productUrl(v.variant)}" data-quickview="${v.key}" data-size="${v.size}">
            <img src="${img(v.variant.front, 200)}" alt="" width="52" height="69">
          </a>
          <div>
            <p class="line__title">${variantTitle(v)}</p>
            <p class="line__price">${money(v.variant.price)}</p>
          </div>
          <div class="line__side">
            <div class="qty" role="group" aria-label="Aantal ${variantTitle(v)}">
              <button type="button" data-qty="-1" data-id="${item.id}" aria-label="Eén minder"><svg width="14" height="14"><use href="#i-minus"/></svg></button>
              <output>${item.qty}</output>
              <button type="button" data-qty="1" data-id="${item.id}" aria-label="Eén meer"><svg width="14" height="14"><use href="#i-plus"/></svg></button>
            </div>
            <button class="line__remove" type="button" data-remove="${item.id}" aria-label="Verwijder ${variantTitle(v)}"><svg width="18" height="18"><use href="#i-trash"/></svg></button>
          </div>
        </div>`;
    }).join('');

    // Aanvulling: Ritual box en een smaak die nog niet in de wagen zit
    const inCart = new Set(Cart.items.map((i) => VARIANTS.get(i.id).key));
    const suggestions = [];
    if (!inCart.has('ritual')) suggestions.push(['Maak het ritueel compleet', 'ritual', 'one']);
    const flavour = ['dragon', 'ube', 'matcha'].find((k) => !inCart.has(k));
    if (flavour) suggestions.push(['Ontdek ook', flavour, '50']);

    const upsell = suggestions.map(([label, key, size]) => {
      const p = CATALOG[key];
      const v = p.variants[size];
      return `
        <div class="upsell">
          <p class="upsell__title">${label}</p>
          <div class="upsell__item" style="--tone:${p.tone}">
            <img src="${img(v.front, 200)}" alt="" width="64" height="64" loading="lazy">
            <div>
              <p class="upsell__name">${variantTitle({ product: p, size })}</p>
              <p class="upsell__price">${money(v.price)}</p>
            </div>
            <button class="upsell__add" type="button" data-upsell="${v.id}" aria-label="${variantTitle({ product: p, size })} toevoegen"><svg width="18" height="18"><use href="#i-plus"/></svg></button>
          </div>
        </div>`;
    }).join('');

    cartBody.innerHTML = lines + upsell;
  }

  cartBody.addEventListener('click', (e) => {
    const qtyBtn = e.target.closest('[data-qty]');
    if (qtyBtn) {
      const line = Cart.items.find((i) => i.id === qtyBtn.dataset.id);
      if (line) Cart.set(line.id, line.qty + Number(qtyBtn.dataset.qty));
      return;
    }
    const removeBtn = e.target.closest('[data-remove]');
    if (removeBtn) { Cart.set(removeBtn.dataset.remove, 0); return; }
    const upsellBtn = e.target.closest('[data-upsell]');
    if (upsellBtn) {
      burst(upsellBtn);
      Cart.add(upsellBtn.dataset.upsell, 1);
      bumpCart();
    }
  });

  /* ------------------------------------------------------------------
     Dialogen (menu, winkelwagen, snel bekijken)
     ------------------------------------------------------------------ */
  function openDialog(dlg) {
    if (!dlg || dlg.open) return;
    dlg.showModal();
    document.body.classList.add('is-locked');
    requestAnimationFrame(() => requestAnimationFrame(() => dlg.classList.add('is-open')));
    if (dlg.id === 'menu') $('[data-menu-open]').setAttribute('aria-expanded', 'true');
  }

  function closeDialog(dlg) {
    return new Promise((resolve) => {
      if (!dlg || !dlg.open) { resolve(); return; }
      dlg.classList.remove('is-open');
      if (dlg.id === 'menu') $('[data-menu-open]').setAttribute('aria-expanded', 'false');
      setTimeout(() => {
        dlg.close();
        if (!$('dialog[open]')) document.body.classList.remove('is-locked');
        resolve();
      }, reduceMotion ? 0 : 420);
    });
  }

  $$('dialog').forEach((dlg) => {
    dlg.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(dlg); });
    dlg.addEventListener('click', (e) => {
      if (e.target === dlg) {
        const r = dlg.getBoundingClientRect();
        const outside = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
        if (outside) closeDialog(dlg);
      }
      const closer = e.target.closest('[data-close]');
      if (!closer) return;
      const href = closer.getAttribute('href');
      if (href && href.startsWith('#')) {
        e.preventDefault();
        closeDialog(dlg).then(() => scrollToId(href));
      } else if (!href) {
        closeDialog(dlg);
      }
    });
  });

  function scrollToId(hash) {
    const target = hash.length > 1 ? document.getElementById(hash.slice(1)) : null;
    if (target) target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
  }

  $('[data-menu-open]').addEventListener('click', () => openDialog($('#menu')));
  $$('[data-cart-open]').forEach((btn) => btn.addEventListener('click', () => openDialog(cartDialog)));

  /* ------------------------------------------------------------------
     Productkaarten: inhoud kiezen & toevoegen
     ------------------------------------------------------------------ */
  function updateSaveNote(el, product, size) {
    const amount = saving(product);
    el.hidden = !(size === '100' && amount > 0);
    el.textContent = `voordeliger: bespaar ${money(amount)} t.o.v. 2× 50 gram`;
  }

  function setCardSize(card, size) {
    const product = CATALOG[card.dataset.product];
    const variant = product.variants[size];
    if (!variant || card.dataset.size === size) return;
    card.dataset.size = size;

    const sizes = $('.sizes', card);
    sizes.dataset.active = size;
    $$('.sizes__opt', card).forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.size === size)));

    const price = $('[data-price]', card);
    price.textContent = money(variant.price);
    price.classList.remove('is-tick'); void price.offsetWidth; price.classList.add('is-tick');

    const image = $('[data-img]', card);
    const swap = () => { image.src = img(variant.front, 600); image.alt = `${product.name} ${size} gram`; };
    if (reduceMotion) swap();
    else {
      image.classList.remove('is-swapping'); void image.offsetWidth; image.classList.add('is-swapping');
      setTimeout(swap, 220);
      image.addEventListener('animationend', () => image.classList.remove('is-swapping'), { once: true });
    }

    $('[data-bestseller]', card).hidden = !variant.bestseller;
    $('.pcard__title a', card).href = productUrl(variant);
    updateSaveNote($('[data-save]', card), product, size);
  }

  $$('.pcard').forEach((card) => {
    card.dataset.size = '50';
    $('.sizes', card).dataset.active = '50';
    // Voorladen zodat het wisselen van inhoud direct is
    Object.values(CATALOG[card.dataset.product].variants).forEach((v) => { const i = new Image(); i.src = img(v.front, 600); });

    card.addEventListener('click', (e) => {
      const opt = e.target.closest('.sizes__opt');
      if (opt) { setCardSize(card, opt.dataset.size); return; }
      const add = e.target.closest('[data-add]');
      if (add) {
        const variant = CATALOG[card.dataset.product].variants[card.dataset.size];
        addToCart(variant.id, 1, { button: add, sourceImg: $('[data-img]', card) });
      }
    });
  });

  function addToCart(id, qty, { button, sourceImg, fromModal } = {}) {
    Cart.add(id, qty);
    if (button) {
      burst(button);
      const label = button.innerHTML;
      button.classList.add('is-added');
      button.innerHTML = '<svg width="18" height="18"><use href="#i-check"/></svg> toegevoegd!';
      setTimeout(() => { button.classList.remove('is-added'); button.innerHTML = label; }, 1600);
    }
    // Positie vastleggen vóór een eventuele modal sluit, daarna vliegt het zakje naar de winkelwagen
    const from = sourceImg ? { rect: sourceImg.getBoundingClientRect(), src: sourceImg.currentSrc || sourceImg.src } : null;
    (fromModal ? closeDialog($('#quickview')) : Promise.resolve())
      .then(() => (from && !reduceMotion ? flyToCart(from) : null))
      .then(() => { bumpCart(); openDialog(cartDialog); });
  }

  function bumpCart() {
    const btn = $('.cart-btn');
    btn.classList.remove('is-bump'); void btn.offsetWidth; btn.classList.add('is-bump');
  }

  function flyToCart({ rect: from, src }) {
    const to = $('.cart-btn').getBoundingClientRect();
    if (!from.width) return Promise.resolve();
    const clone = document.createElement('img');
    clone.src = src;
    clone.className = 'fly-img';
    const w = Math.min(from.width, 220);
    const h = (from.height / from.width) * w;
    const x0 = from.left + from.width / 2 - w / 2;
    const y0 = from.top + from.height / 2 - h / 2;
    Object.assign(clone.style, { left: `${x0}px`, top: `${y0}px`, width: `${w}px`, height: `${h}px` });
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
    const colors = ['#c9d6a9', '#ceb8ca', '#52572e', '#2d2e2d'];
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
     Snel bekijken
     ------------------------------------------------------------------ */
  const qvDialog = $('#quickview');
  const qvRoot = $('[data-qv]');
  let qvState = null;

  function openQuickView(key, size) {
    const product = CATALOG[key];
    if (!product) return;
    const sizes = Object.keys(product.variants);
    qvState = { key, size: sizes.includes(size) ? size : sizes[0], qty: 1, image: 0 };
    renderQuickView();
    openDialog(qvDialog);
  }

  function qvImages() {
    const product = CATALOG[qvState.key];
    const v = product.variants[qvState.size];
    return [
      { file: v.front, pack: true },
      ...product.lifestyle.map((f) => ({ file: f, pack: false })),
      { file: v.back, pack: true },
    ];
  }

  function renderQuickView() {
    const product = CATALOG[qvState.key];
    const v = product.variants[qvState.size];
    const images = qvImages();
    const current = images[qvState.image] || images[0];
    const hasSizes = qvState.size !== 'one';
    const amount = saving(product);

    qvRoot.innerHTML = `
      <div class="qv__gallery">
        <div class="qv__main" style="--tone:${product.tone}">
          <img class="${current.pack ? 'is-pack' : ''}" src="${img(current.file, 900)}" alt="${variantTitle({ product, size: qvState.size })}">
          ${v.bestseller ? '<div class="pcard__badges"><span class="badge">Best seller</span><span class="badge">Gekeurd in Europa</span></div>' : (hasSizes ? '<div class="pcard__badges"><span class="badge">Gekeurd in Europa</span></div>' : '')}
        </div>
        <div class="qv__thumbs" role="group" aria-label="Afbeeldingen">
          ${images.map((im, i) => `<button class="qv__thumb${im.pack ? ' is-pack' : ''}" style="--tone:${product.tone}" type="button" data-qv-image="${i}" aria-label="Afbeelding ${i + 1}" aria-current="${i === qvState.image}"><img src="${img(im.file, 160)}" alt=""></button>`).join('')}
        </div>
      </div>
      <div class="qv__info">
        <h2 class="qv__title" id="qv-title">${variantTitle({ product, size: qvState.size })}</h2>
        <p class="qv__price"><span class="visually-hidden">Aanbiedingsprijs</span>${money(v.price)}</p>
        <div class="qv__desc">${product.desc.map((p) => `<p>${p}</p>`).join('')}</div>
        ${hasSizes ? `
          <div class="sizes" role="group" aria-label="Kies inhoud" data-active="${qvState.size}">
            <span class="sizes__thumb" aria-hidden="true"></span>
            <button class="sizes__opt" type="button" data-qv-size="50" aria-pressed="${qvState.size === '50'}">50 gram</button>
            <button class="sizes__opt" type="button" data-qv-size="100" aria-pressed="${qvState.size === '100'}">100 gram</button>
          </div>
          ${qvState.size === '100' && amount > 0 ? `<p class="pcard__save">voordeliger: bespaar ${money(amount)} t.o.v. 2× 50 gram</p>` : ''}` : ''}
        <div class="qv__buy">
          <div class="qty" role="group" aria-label="Aantal">
            <button type="button" data-qv-qty="-1" aria-label="Eén minder"><svg width="14" height="14"><use href="#i-minus"/></svg></button>
            <output>${qvState.qty}</output>
            <button type="button" data-qv-qty="1" aria-label="Eén meer"><svg width="14" height="14"><use href="#i-plus"/></svg></button>
          </div>
          <button class="btn btn--primary btn--lg" type="button" data-qv-add><svg width="18" height="18"><use href="#i-bag"/></svg> toevoegen aan winkelwagen</button>
        </div>
        <ul class="qv__perks">
          ${(hasSizes ? ESSENCE_PERKS : ESSENCE_PERKS.slice(0, 2)).map(([icon, text]) => `<li><svg width="18" height="18"><use href="#${icon}"/></svg>${text}</li>`).join('')}
        </ul>
        <a class="qv__more" href="${productUrl(v)}">bekijk volledige productpagina</a>
      </div>`;
  }

  qvRoot.addEventListener('click', (e) => {
    const t = e.target;
    const imageBtn = t.closest('[data-qv-image]');
    if (imageBtn) { qvState.image = Number(imageBtn.dataset.qvImage); renderQuickView(); return; }
    const sizeBtn = t.closest('[data-qv-size]');
    if (sizeBtn) { qvState.size = sizeBtn.dataset.qvSize; qvState.image = 0; renderQuickView(); return; }
    const qtyBtn = t.closest('[data-qv-qty]');
    if (qtyBtn) { qvState.qty = clamp(qvState.qty + Number(qtyBtn.dataset.qvQty), 1, 99); renderQuickView(); return; }
    const addBtn = t.closest('[data-qv-add]');
    if (addBtn) {
      const v = CATALOG[qvState.key].variants[qvState.size];
      const source = $('.qv__main img', qvRoot);
      addToCart(v.id, qvState.qty, { button: addBtn, sourceImg: source, fromModal: true });
    }
  });

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-quickview]');
    if (!trigger || e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    const card = trigger.closest('.pcard');
    const size = trigger.dataset.size || (card ? card.dataset.size : '50');
    const fromCart = trigger.closest('#cart');
    (fromCart ? closeDialog(cartDialog) : Promise.resolve()).then(() => openQuickView(trigger.dataset.quickview, size));
  });

  /* ------------------------------------------------------------------
     Smaakkeuze in de hero → spring naar de kaart
     ------------------------------------------------------------------ */
  $$('[data-jump]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const card = $(`.pcard[data-product="${link.dataset.jump}"]`);
      if (!card) return;
      card.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
      card.classList.remove('is-flash');
      setTimeout(() => card.classList.add('is-flash'), reduceMotion ? 0 : 450);
      card.addEventListener('animationend', () => card.classList.remove('is-flash'), { once: true });
    });
  });

  /* ------------------------------------------------------------------
     Aankondiging: aftellen tot 22:00 (Nederlandse tijd)
     ------------------------------------------------------------------ */
  const timer = $('[data-cutoff]');
  const amsterdamClock = new Intl.DateTimeFormat('nl-NL', { timeZone: 'Europe/Amsterdam', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
  function tickCutoff() {
    const parts = Object.fromEntries(amsterdamClock.formatToParts(new Date()).map((p) => [p.type, p.value]));
    const now = Number(parts.hour) * 3600 + Number(parts.minute) * 60 + Number(parts.second);
    const left = 22 * 3600 - now;
    if (left <= 0) { timer.hidden = true; return; }
    const h = Math.floor(left / 3600);
    const m = Math.floor((left % 3600) / 60);
    const s = left % 60;
    timer.innerHTML = `nog ${h}u ${String(m).padStart(2, '0')}m<span class="announce__sec"> ${String(s).padStart(2, '0')}s</span>`;
    timer.hidden = false;
  }
  tickCutoff();
  setInterval(tickCutoff, 1000);

  // Op mobiel wisselen de twee berichten elkaar af in plaats van drie regels hoog te worden
  const announceMsgs = $$('[data-announce]');
  let announceIndex = 0;
  setInterval(() => {
    announceMsgs[announceIndex].classList.remove('is-active');
    announceIndex = (announceIndex + 1) % announceMsgs.length;
    announceMsgs[announceIndex].classList.add('is-active');
  }, 4500);

  /* ------------------------------------------------------------------
     Header schaduw bij scrollen
     ------------------------------------------------------------------ */
  const header = $('[data-header]');
  const onHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 10);
  onHeader();
  window.addEventListener('scroll', onHeader, { passive: true });

  /* ------------------------------------------------------------------
     Scroll-reveals
     ------------------------------------------------------------------ */
  const revealIO = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) { entry.target.classList.add('is-in'); revealIO.unobserve(entry.target); }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  $$('[data-reveal]').forEach((el) => revealIO.observe(el));

  /* ------------------------------------------------------------------
     Marquees: inhoud dupliceren voor een naadloze loop
     ------------------------------------------------------------------ */
  $$('[data-marquee]').forEach((track) => {
    const items = Array.from(track.children);
    for (let i = 0; i < 8 && track.scrollWidth > 0 && track.scrollWidth < window.innerWidth * 1.2; i++) {
      items.forEach((node) => { const c = node.cloneNode(true); c.setAttribute('aria-hidden', 'true'); track.appendChild(c); });
    }
    Array.from(track.children).forEach((node) => {
      const c = node.cloneNode(true);
      c.setAttribute('aria-hidden', 'true');
      track.appendChild(c);
    });
  });

  /* ------------------------------------------------------------------
     Verhaal: woorden lichten op tijdens het scrollen
     ------------------------------------------------------------------ */
  const story = $('[data-words]');
  let storyWords = [];
  if (story) {
    const words = story.textContent.trim().split(/\s+/);
    story.setAttribute('aria-label', story.textContent.trim());
    story.innerHTML = words.map((w) => `<span class="word" aria-hidden="true">${w}</span>`).join(' ');
    storyWords = $$('.word', story);
    if (reduceMotion) storyWords.forEach((w) => w.classList.add('is-on'));
  }

  /* ------------------------------------------------------------------
     Parallax (scroll) & muisbeweging in de hero
     ------------------------------------------------------------------ */
  const parallaxEls = $$('[data-parallax]');
  let ticking = false;
  function onScrollFrame() {
    ticking = false;
    const vh = window.innerHeight;
    parallaxEls.forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < -200 || r.top > vh + 200) return;
      const offset = r.top + r.height / 2 - vh / 2;
      el.style.transform = `translate3d(0, ${(-offset * Number(el.dataset.parallax)).toFixed(1)}px, 0)`;
    });
    if (storyWords.length && !reduceMotion) {
      const r = story.getBoundingClientRect();
      const progress = clamp((vh * 0.85 - r.top) / (r.height + vh * 0.35), 0, 1);
      const lit = Math.round(progress * storyWords.length);
      storyWords.forEach((w, i) => w.classList.toggle('is-on', i < lit));
    }
  }
  if (!reduceMotion) {
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScrollFrame); } }, { passive: true });
    window.addEventListener('resize', onScrollFrame);
    onScrollFrame();
  }

  const hero = $('[data-hero]');
  if (hero && finePointer && !reduceMotion) {
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

  /* ------------------------------------------------------------------
     3D-tilt op productkaarten
     ------------------------------------------------------------------ */
  if (finePointer && !reduceMotion) {
    $$('[data-tilt]').forEach((el) => {
      el.addEventListener('pointermove', (e) => {
        const r = el.getBoundingClientRect();
        el.style.setProperty('--tx', (((e.clientX - r.left) / r.width) * 2 - 1).toFixed(3));
        el.style.setProperty('--ty', (((e.clientY - r.top) / r.height) * 2 - 1).toFixed(3));
      });
      el.addEventListener('pointerleave', () => { el.style.setProperty('--tx', 0); el.style.setProperty('--ty', 0); });
    });
  }

  /* ------------------------------------------------------------------
     Video in de telefoon-mockup: pas laden als hij in beeld komt
     ------------------------------------------------------------------ */
  $$('[data-lazy-video]').forEach((video) => {
    const io = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        if (!video.src) video.src = window.innerWidth < 700 ? video.dataset.srcSmall : video.dataset.src;
        if (!reduceMotion) video.play().catch(() => {});
      } else if (!video.paused) {
        video.pause();
      }
    }, { threshold: 0.25 });
    io.observe(video);
  });

  /* ------------------------------------------------------------------
     Sticky CTA (mobiel) zodra de hero uit beeld is
     ------------------------------------------------------------------ */
  const sticky = $('[data-sticky-cta]');
  const stickyLinks = $$('a', sticky);
  const seen = { hero: true, essence: false, footer: false };
  const updateSticky = () => {
    const show = !seen.hero && !seen.essence && !seen.footer;
    sticky.classList.toggle('is-visible', show);
    sticky.setAttribute('aria-hidden', String(!show));
    stickyLinks.forEach((a) => { a.tabIndex = show ? 0 : -1; });
    document.body.classList.toggle('has-sticky', show);
    document.body.classList.toggle('at-footer', seen.footer);
  };
  const stickyIO = new IntersectionObserver((entries) => {
    entries.forEach((entry) => { seen[entry.target.dataset.stickyWatch] = entry.isIntersecting; });
    updateSticky();
  });
  [['hero', hero], ['essence', $('#essence .product-grid')], ['footer', $('.footer')]].forEach(([name, el]) => {
    if (!el) return;
    el.dataset.stickyWatch = name;
    stickyIO.observe(el);
  });

  /* ------------------------------------------------------------------
     FAQ: soepel open- en dichtklappen
     ------------------------------------------------------------------ */
  $$('.acc').forEach((details) => {
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
  });

  /* ------------------------------------------------------------------
     Nieuwsbrief: speels schudden bij een ongeldig e-mailadres
     ------------------------------------------------------------------ */
  $$('[data-subscribe]').forEach((form) => {
    const input = $('input[type="email"]', form);
    input.addEventListener('invalid', () => {
      form.classList.remove('is-shake'); void form.offsetWidth; form.classList.add('is-shake');
    });
  });

  /* ------------------------------------------------------------------
     WhatsApp: eenmalig een hulp-tip tonen
     ------------------------------------------------------------------ */
  const wa = $('[data-wa]');
  try {
    if (!sessionStorage.getItem('orive:wa-tip')) {
      setTimeout(() => {
        wa.classList.add('is-tip');
        setTimeout(() => wa.classList.remove('is-tip'), 4500);
        sessionStorage.setItem('orive:wa-tip', '1');
      }, 14000);
    }
  } catch (e) { /* geen sessionStorage */ }

  /* ------------------------------------------------------------------
     Start
     ------------------------------------------------------------------ */
  Cart.load();
  renderCart();

  const ready = () => document.documentElement.classList.add('is-ready');
  if (document.fonts && document.fonts.ready) {
    Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 700))]).then(ready);
  } else {
    ready();
  }
})();
