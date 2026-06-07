document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contact-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('[type="submit"]');
    const success = document.getElementById('contact-success');
    const error = document.getElementById('contact-error');
    success.classList.remove('visible');
    error.classList.remove('visible');
    btn.textContent = 'Versturen…';
    btn.disabled = true;
    form.classList.add('form-loading');

    const data = {
      voornaam: form.voornaam.value.trim(),
      naam: form.naam.value.trim(),
      email: form.email.value.trim(),
      onderwerp: form.onderwerp.value,
      bericht: form.bericht.value.trim(),
      ingediend_op: new Date().toISOString()
    };

    try {
      await window.aegirDB.insert('contact_berichten', data);
      form.reset();
      success.classList.add('visible');
    } catch (err) {
      console.error(err);
      error.textContent = 'Er ging iets mis. Mail ons direct op info@aegir-gent.be.';
      error.classList.add('visible');
    } finally {
      btn.textContent = 'Verstuur bericht';
      btn.disabled = false;
      form.classList.remove('form-loading');
    }
  });
});
