document.addEventListener('DOMContentLoaded', () => {
  const navEl = document.getElementById('site-nav');
  if (!navEl) return;

  const isHome = window.location.pathname === '/' || window.location.pathname === '/index.html';
  const chevron = `<span class="nav-chevron"><svg viewBox="0 0 8 5" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 1l3 3 3-3"/></svg></span>`;

  navEl.innerHTML = `
<nav class="nav nav-transparent" role="navigation" aria-label="Hoofdnavigatie">
  <div class="nav-inner">

    <a href="/" class="nav-logo" aria-label="Aegir Gent — home">
      <img src="/images/logo/logo.png" alt="Aegir Gent" height="38"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span style="display:none;font-family:var(--font-head);font-weight:700;font-size:1.05rem;color:var(--navy)">AEGIR GENT</span>
    </a>

    <ul class="nav-links" role="list">

      <li><a href="/">Home</a></li>

      <li><a href="/pages/nieuws/">Nieuws</a></li>

      <li><a href="/pages/agenda/">Agenda</a></li>

      <li class="has-dropdown">
        <a href="/pages/club/" class="nav-parent">Club ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/club/#over">Over Aegir Gent</a>
          <a href="/pages/club/#bestuur">Bestuur</a>
          <a href="/pages/club/#partners">Partners &amp; sponsors</a>
          <a href="/pages/club/#ereleden">Ereleden</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/trainingen/" class="nav-parent">Trainingen ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/trainingen/#uurrooster">Uurrooster</a>
          <a href="/pages/trainingen/#groepen">Niveaugroepen</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/competitie/" class="nav-parent">Competitie ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/competitie/#reeks-a">Disciplines reeks A</a>
          <a href="/pages/competitie/#reeks-b">Disciplines reeks B</a>
          <a href="/pages/competitie/#klassement">Clubrecords</a>
          <div class="nav-dropdown-divider"></div>
          <a href="http://www.aegir-gent.be/LiveResults/" target="_blank" rel="noopener">Live uitslagen</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/opleiding/" class="nav-parent">Opleiding ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/opleiding/#bijscholing">Bijscholing 2026</a>
          <a href="/pages/opleiding/#brevet">Reddersbrevet</a>
          <a href="/pages/opleiding/#medisch">Medisch attest</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/leden/" class="nav-parent">Leden ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/leden/inschrijven.html">Inschrijven</a>
          <a href="/pages/leden/#lidgeld">Lidgeld &amp; kortingen</a>
          <a href="/pages/leden/#verzekering">Verzekering</a>
        </div>
      </li>

      <li><a href="/pages/shop/">Shop</a></li>

      <li><a href="/pages/contact/">Contact</a></li>
    </ul>

    <button class="nav-burger" aria-label="Menu openen" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>`;

  const nav    = navEl.querySelector('.nav');
  const burger = navEl.querySelector('.nav-burger');
  const links  = navEl.querySelector('.nav-links');

  // Transparent nav scroll behaviour (all pages)
  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 40);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Burger open/close
  if (burger && links) {
    burger.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
      burger.classList.toggle('is-open', open);
    });
  }

  // Mobile accordion: toggle sub-menu on parent click
  navEl.querySelectorAll('.has-dropdown .nav-parent').forEach(a => {
    a.addEventListener('click', e => {
      if (window.innerWidth > 768) return;
      e.preventDefault();
      const li = a.closest('.has-dropdown');
      const wasOpen = li.classList.contains('mob-open');
      navEl.querySelectorAll('.has-dropdown.mob-open').forEach(el => el.classList.remove('mob-open'));
      if (!wasOpen) li.classList.add('mob-open');
    });
  });

  // Close menu when a sub-link or leaf link is clicked
  navEl.querySelectorAll('.nav-dropdown a, .nav-links > li:not(.has-dropdown) a').forEach(a => {
    a.addEventListener('click', () => {
      links.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
      burger.classList.remove('is-open');
      navEl.querySelectorAll('.has-dropdown.mob-open').forEach(el => el.classList.remove('mob-open'));
    });
  });

  // Close on outside click
  document.addEventListener('click', e => {
    if (!navEl.contains(e.target)) {
      links.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
      burger.classList.remove('is-open');
      navEl.querySelectorAll('.has-dropdown.mob-open').forEach(el => el.classList.remove('mob-open'));
    }
  });

  // Active nav link
  const path = window.location.pathname;
  navEl.querySelectorAll('.nav-links a').forEach(a => {
    const href = a.getAttribute('href');
    if (href === '/' && path === '/') a.classList.add('active');
    else if (href && href !== '/' && !href.startsWith('http') && path.startsWith(href.split('#')[0])) a.classList.add('active');
  });
});
