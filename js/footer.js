document.addEventListener('DOMContentLoaded', () => {
  const footerEl = document.getElementById('site-footer');
  if (!footerEl) return;
  footerEl.innerHTML = `
<footer class="footer" role="contentinfo">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:.5rem">
          <img src="images/logo.png" alt="Aegir Gent" height="32" style="filter:brightness(0) invert(1);opacity:.7"
               onerror="this.style.display='none'">
        </div>
        <p>Reddingsclub Aegir Gent — reddingszwemmen, trainingen en competitie in Gent. Gevestigd in Wondelgem.</p>
      </div>
      <div class="footer-col">
        <h5>Navigatie</h5>
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="nieuws.html">Nieuws</a></li>
          <li><a href="agenda.html">Agenda</a></li>
          <li><a href="club.html">Club</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Club</h5>
        <ul>
          <li><a href="trainingen.html">Trainingen</a></li>
          <li><a href="competitie.html">Competitie</a></li>
          <li><a href="opleiding.html">Opleiding</a></li>
          <li><a href="leden.html">Leden</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Contact</h5>
        <ul>
          <li><a href="contact.html">Contactformulier</a></li>
          <li><a href="mailto:info@aegir-gent.be">info@aegir-gent.be</a></li>
          <li><a href="documents/privacy.pdf">Privacybeleid</a></li>
          <li><a href="documents/infobrochure.pdf">Clubfolder PDF</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2026 Reddingsclub Aegir Gent vzw &amp; Rescue Aegir Gent vzw — Poortbilk 5, 9032 Wondelgem</p>
      <a href="documents/privacy.pdf">Privacy</a>
    </div>
  </div>
</footer>`;
});
