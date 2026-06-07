document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('membership-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('[type="submit"]');
    const success = document.getElementById('membership-success');
    const error = document.getElementById('membership-error');
    success.classList.remove('visible');
    error.classList.remove('visible');
    btn.textContent = 'Indienen…';
    btn.disabled = true;

    const data = {
      voornaam: form.voornaam.value.trim(),
      naam: form.naam.value.trim(),
      geboortedatum: form.geboortedatum.value,
      email: form.email.value.trim(),
      telefoon: form.telefoon.value.trim(),
      adres: form.adres.value.trim(),
      type_lid: form.type_lid.value,
      niveau: form.niveau.value,
      opmerking: form.opmerking.value.trim(),
      ingediend_op: new Date().toISOString(),
      status: 'nieuw'
    };

    try {
      await window.aegirDB.insert('inschrijvingen', data);
      form.reset();
      success.classList.add('visible');
      success.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (err) {
      console.error(err);
      error.textContent = 'Er ging iets mis. Probeer opnieuw of mail info@aegir-gent.be.';
      error.classList.add('visible');
    } finally {
      btn.textContent = 'Inschrijving indienen';
      btn.disabled = false;
    }
  });
});
