const SUPABASE_URL = 'https://wlmehcbxeseigxwougwd.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndsbWVoY2J4ZXNlaWd4d291Z3dkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODA3NTUwMzQsImV4cCI6MjA5NjMzMTAzNH0.hzplILom0POZIbIuTIv6op9QSUl8PQdKdYg5Q0dh4_8';

async function supabaseInsert(table, data) {
  const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'apikey': SUPABASE_ANON_KEY,
      'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
      'Prefer': 'return=minimal'
    },
    body: JSON.stringify(data)
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || `HTTP ${res.status}`);
  }
  const text = await res.text();
  return text ? JSON.parse(text) : {};
}

window.aegirDB = { insert: supabaseInsert };
