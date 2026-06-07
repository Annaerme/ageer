document.addEventListener('DOMContentLoaded', () => {
  const navEl = document.getElementById('site-nav');
  if (!navEl) return;
  navEl.innerHTML = `
<nav class="nav" role="navigation" aria-label="Hoofdnavigatie">
  <div class="nav-inner">
    <a href="index.html" class="nav-logo" aria-label="Aegir Gent — home">
      <img src="images/logo.png" alt="Aegir Gent" height="36"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span style="display:none">
        <span class="nav-logo-text">Aegir Gent</span>
        <span class="nav-logo-sub">Reddingsclub</span>
      </span>
    </a>
    <ul class="nav-links" role="list">
      <li><a href="index.html">Home</a></li>
      <li><a href="nieuws.html">Nieuws</a></li>
      <li><a href="agenda.html">Agenda</a></li>
      <li><a href="club.html">Club</a></li>
      <li><a href="trainingen.html">Trainingen</a></li>
      <li><a href="competitie.html">Competitie</a></li>
      <li><a href="opleiding.html">Opleiding</a></li>
      <li><a href="leden.html">Leden</a></li>
      <li><a href="contact.html" class="nav-cta">Contact</a></li>
    </ul>
    <button class="nav-burger" aria-label="Menu openen" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>`;
});
