// Formulieren (contact, inschrijven, shop) sturen hun gegevens naar de eigen
// API op /api. De server bepaalt zelf id, datum en status en controleert alles.

async function aegirInsert(table, data) {
  const res = await fetch(`/api/rest/v1/${table}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(body.message || `HTTP ${res.status}`);
  return body;
}

window.aegirDB = { insert: aegirInsert };
