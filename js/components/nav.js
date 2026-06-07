document.addEventListener('DOMContentLoaded', () => {
  const navEl = document.getElementById('site-nav');
  if (!navEl) return;

  const isHome = window.location.pathname === '/' || window.location.pathname === '/index.html';
  const chevron = `<span class="nav-chevron"><svg viewBox="0 0 8 5" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 1l3 3 3-3"/></svg></span>`;

  navEl.innerHTML = `
<nav class="nav${isHome ? ' nav-transparent' : ''}" role="navigation" aria-label="Hoofdnavigatie">
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
        <a href="/pages/club/">Club ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/club/#over">Over Aegir Gent</a>
          <a href="/pages/club/#bestuur">Bestuur</a>
          <a href="/pages/club/#partners">Partners &amp; sponsors</a>
          <a href="/pages/club/#ereleden">Ereleden</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/trainingen/">Trainingen ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/trainingen/#uurrooster">Uurrooster</a>
          <a href="/pages/trainingen/#groepen">Niveaugroepen</a>
        </div>
      </li>

      <li><a href="/pages/shop/">Clubshop</a></li>

      <li class="has-dropdown">
        <a href="/pages/competitie/">Competitie ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/competitie/#reeks-a">Disciplines reeks A</a>
          <a href="/pages/competitie/#reeks-b">Disciplines reeks B</a>
          <a href="/pages/competitie/#klassement">Clubrecords</a>
          <div class="nav-dropdown-divider"></div>
          <a href="http://www.aegir-gent.be/LiveResults/" target="_blank" rel="noopener">Live uitslagen</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/opleiding/">Opleiding ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/opleiding/#bijscholing">Bijscholing 2026</a>
          <a href="/pages/opleiding/#brevet">Reddersbrevet</a>
          <a href="/pages/opleiding/#medisch">Medisch attest</a>
        </div>
      </li>

      <li class="has-dropdown">
        <a href="/pages/leden/">Leden ${chevron}</a>
        <div class="nav-dropdown">
          <a href="/pages/leden/inschrijven.html">Inschrijven</a>
          <a href="/pages/leden/#lidgeld">Lidgeld &amp; kortingen</a>
          <a href="/pages/leden/#verzekering">Verzekering</a>
        </div>
      </li>

      <li><a href="/pages/contact/">Contact</a></li>
    </ul>

    <button class="nav-burger" aria-label="Menu openen" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>`;

  const nav = navEl.querySelector('.nav');

  // Transparent homepage nav: toggle .scrolled on scroll
  if (isHome) {
    const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
});
