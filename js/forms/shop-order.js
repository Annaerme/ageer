document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('shop-container');
  if (!container) return;

  let producten = [];
  let bestelling = {};

  // Load products from data/products.json
  try {
    const res = await fetch('/data/products.json');
    producten = await res.json();
  } catch {
    container.innerHTML = '<p>Producten konden niet geladen worden.</p>';
    return;
  }

  renderShop();

  function renderShop() {
    container.innerHTML = `
      <div class="product-grid">
        ${producten.map(p => `
          <div class="product-card" data-id="${p.id}">
            <div class="product-emoji">${p.emoji}</div>
            <div class="product-name">${p.naam}</div>
            <div class="product-price">${p.prijs.toFixed(2).replace('.',',')}€</div>
            ${p.maten ? `<div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">Maten: ${p.maten.join(', ')}</div>` : ''}
          </div>
        `).join('')}
      </div>
      <div id="order-lines" style="margin:2rem 0"></div>
      <div class="order-total" id="order-total-bar" style="display:none">
        <span class="order-total-label">Totaal</span>
        <span class="order-total-amount" id="order-total-amount">0,00€</span>
      </div>
    `;

    container.querySelectorAll('.product-card').forEach(card => {
      card.addEventListener('click', () => toggleProduct(card.dataset.id));
    });
  }

  function toggleProduct(id) {
    const product = producten.find(p => p.id === id);
    if (!product) return;
    if (bestelling[id]) {
      delete bestelling[id];
    } else {
      bestelling[id] = { product, qty: 1, maat: product.maten ? product.maten[0] : null };
    }
    updateSelectedCards();
    renderOrderLines();
    updateTotal();
  }

  function updateSelectedCards() {
    container.querySelectorAll('.product-card').forEach(card => {
      card.classList.toggle('selected', !!bestelling[card.dataset.id]);
    });
  }

  function renderOrderLines() {
    const linesEl = document.getElementById('order-lines');
    const ids = Object.keys(bestelling);
    if (!ids.length) { linesEl.innerHTML = ''; return; }
    linesEl.innerHTML = `
      <h3 style="margin-bottom:1rem">Jouw bestelling</h3>
      ${ids.map(id => {
        const item = bestelling[id];
        return `
          <div class="order-line">
            <div>
              <strong>${item.product.naam}</strong>
              ${item.product.maten ? `
                <select class="maat-select" data-id="${id}" style="margin-left:.5rem;padding:3px 8px;border:1px solid var(--border);border-radius:4px;font-size:.8rem">
                  ${item.product.maten.map(m => `<option ${m===item.maat?'selected':''}>${m}</option>`).join('')}
                </select>` : ''}
            </div>
            <div class="order-qty">
              <button class="qty-btn" data-id="${id}" data-delta="-1">−</button>
              <span>${item.qty}</span>
              <button class="qty-btn" data-id="${id}" data-delta="1">+</button>
            </div>
            <div style="font-weight:700;font-family:var(--font-head);color:var(--gold)">
              ${(item.product.prijs * item.qty).toFixed(2).replace('.',',')}€
            </div>
          </div>`;
      }).join('')}
    `;

    linesEl.querySelectorAll('.qty-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const delta = parseInt(btn.dataset.delta);
        bestelling[id].qty = Math.max(1, bestelling[id].qty + delta);
        renderOrderLines();
        updateTotal();
      });
    });

    linesEl.querySelectorAll('.maat-select').forEach(sel => {
      sel.addEventListener('change', () => {
        bestelling[sel.dataset.id].maat = sel.value;
      });
    });
  }

  function updateTotal() {
    const totalBar = document.getElementById('order-total-bar');
    const totalAmount = document.getElementById('order-total-amount');
    const ids = Object.keys(bestelling);
    if (!ids.length) { totalBar.style.display = 'none'; return; }
    const total = ids.reduce((sum, id) => sum + bestelling[id].product.prijs * bestelling[id].qty, 0);
    totalAmount.textContent = total.toFixed(2).replace('.',',') + '€';
    totalBar.style.display = 'flex';
  }

  // Order form submission
  const orderForm = document.getElementById('order-form');
  if (!orderForm) return;

  orderForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const ids = Object.keys(bestelling);
    if (!ids.length) {
      alert('Selecteer minstens één product.');
      return;
    }
    const btn = orderForm.querySelector('[type="submit"]');
    const success = document.getElementById('order-success');
    const errorEl = document.getElementById('order-error');
    success.classList.remove('visible');
    errorEl.classList.remove('visible');
    btn.textContent = 'Bestelling indienen…';
    btn.disabled = true;

    const regels = ids.map(id => ({
      product_id: id,
      naam: bestelling[id].product.naam,
      maat: bestelling[id].maat,
      aantal: bestelling[id].qty,
      eenheidsprijs: bestelling[id].product.prijs,
      totaal: bestelling[id].product.prijs * bestelling[id].qty
    }));

    const totaalBedrag = regels.reduce((s, r) => s + r.totaal, 0);

    const data = {
      voornaam: orderForm.voornaam.value.trim(),
      naam: orderForm.naam.value.trim(),
      email: orderForm.email.value.trim(),
      telefoon: orderForm.telefoon.value.trim(),
      opmerking: orderForm.opmerking.value.trim(),
      regels: JSON.stringify(regels),
      totaal: totaalBedrag,
      status: 'nieuw',
      ingediend_op: new Date().toISOString()
    };

    try {
      await window.aegirDB.insert('shop_bestellingen', data);
      bestelling = {};
      renderShop();
      orderForm.reset();
      success.classList.add('visible');
      success.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (err) {
      console.error(err);
      errorEl.textContent = 'Er ging iets mis. Mail ons direct op info@aegir-gent.be.';
      errorEl.classList.add('visible');
    } finally {
      btn.textContent = 'Bestelling plaatsen';
      btn.disabled = false;
    }
  });
});
