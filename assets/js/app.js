// Menu mobile
const burger = document.getElementById('burger');
if (burger) burger.addEventListener('click', () => (document.getElementById('side') || document.getElementById('topnav')).classList.toggle('open'));
// Compteurs animés
document.querySelectorAll('[data-count]').forEach(el => {
  const end = parseFloat(el.dataset.count), dec = parseInt(el.dataset.dec || 0), suf = el.dataset.suffix || '';
  const t0 = performance.now(), dur = 900;
  const tick = t => { const p = Math.min(1, (t - t0) / dur); el.textContent = (end * p).toLocaleString('fr-FR', {minimumFractionDigits: dec, maximumFractionDigits: dec}) + suf; if (p < 1) requestAnimationFrame(tick); };
  requestAnimationFrame(tick);
});
// Calcul de prix côté client (le serveur recalcule toujours)
const calc = document.querySelector('[data-calc]');
if (calc) {
  const out = document.getElementById('total'), q = calc.querySelector('[name=quantite],[name=nb_personnes]'), sel = calc.querySelector('select[name=id_type]');
  const upd = () => { const unit = sel ? parseFloat(sel.selectedOptions[0].dataset.prix) : parseFloat(calc.dataset.unit); out.textContent = ((unit || 0) * (parseInt(q.value) || 0)).toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' MAD'; };
  calc.addEventListener('input', upd); upd();
}
// Confirmation sur les actions sensibles
document.querySelectorAll('[data-confirm]').forEach(f => f.addEventListener('submit', ev => { if (!confirm(f.dataset.confirm)) ev.preventDefault(); }));
// Chatbot FAQ
const chat = document.getElementById('chat');
if (chat) {
  chat.innerHTML = '<div class="box"><div class="h">Assistant du parc</div><div class="log"><div class="m b">Bonjour ! Posez-moi une question : horaires, tarifs, âge minimum, réservation…</div></div><form><input placeholder="Votre question" aria-label="Question" required><button class="btn sm">OK</button></form></div><button class="fab" aria-label="Ouvrir l\'assistant">💬</button>';
  const log = chat.querySelector('.log'), add = (t, c) => { const d = document.createElement('div'); d.className = 'm ' + c; d.textContent = t; log.appendChild(d); log.scrollTop = 1e6; };
  chat.querySelector('.fab').onclick = () => chat.classList.toggle('open');
  chat.querySelector('form').onsubmit = async ev => {
    ev.preventDefault(); const i = ev.target.querySelector('input'), q = i.value.trim(); if (!q) return; add(q, 'u'); i.value = '';
    try { const r = await fetch(document.body.dataset.base + '/chatbot.php?q=' + encodeURIComponent(q)); add((await r.json()).reponse, 'b'); } catch { add('Service indisponible pour le moment.', 'b'); }
  };
}
