// ============================================================
//  RAPIDLIV — JavaScript global
//  Fichier : public/js/app.js
// ============================================================

const API_URL = '/rapidliv-php/api/api.php';

// ---- API Helper ----
async function api(action, data = {}, method = 'POST', params = {}) {
  let url = `${API_URL}?action=${action}`;
  Object.entries(params).forEach(([k, v]) => { url += `&${k}=${encodeURIComponent(v)}`; });

  const isFormData = typeof FormData !== 'undefined' && data instanceof FormData;
  const options = {
    method,
    credentials: 'same-origin',
    headers: isFormData ? {} : { 'Content-Type': 'application/json' }
  };
  if (method === 'POST') options.body = isFormData ? data : JSON.stringify(data);
  if (method === 'GET' && Object.keys(data).length) {
    Object.entries(data).forEach(([k, v]) => { url += `&${k}=${encodeURIComponent(v)}`; });
    delete options.body;
  }

  const res = await fetch(url, options);
  const text = await res.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    const extrait = text
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .slice(0, 220);
    throw new Error(extrait || 'Réponse serveur invalide');
  }
  if (!res.ok || json.erreur) throw new Error(json.erreur || 'Erreur réseau');
  return json;
}

// ---- Flash messages ----
function flash(message, type = 'success') {
  const el = document.createElement('div');
  el.className = `flash flash-${type}`;
  el.innerHTML = `${message} <button onclick="this.parentElement.remove()">✕</button>`;
  el.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;max-width:380px;animation:slideIn .3s ease';
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

// ---- Modal helpers ----
function fermerModal(id) {
  document.getElementById(id).style.display = 'none';
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
  }
});

// ---- Notifications ----
function toggleNotifs() {
  const dd = document.getElementById('notif-dropdown');
  if (!dd) return;
  const visible = dd.style.display !== 'none';
  document.querySelectorAll('.notif-dropdown,.user-dropdown').forEach(d => d.style.display = 'none');
  if (!visible) {
    dd.style.display = 'block';
    chargerNotifs();
  }
}

async function chargerNotifs() {
  const list = document.getElementById('notif-list');
  if (!list) return;
  try {
    const notifs = await api('notifs_liste', {}, 'GET');
    if (!notifs.length) { list.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text3);font-size:13px">Aucune notification</div>'; return; }
    const icones = {commande:'📦',livraison:'🏍️',paiement:'💳',promo:'🎁',systeme:'⚙️'};
    list.innerHTML = notifs.map(n => `
      <div class="notif-item ${n.lu?'':'unread'}" onclick="lireNotif('${n.id}',this)">
        <div class="notif-icon">${icones[n.type]||'🔔'}</div>
        <div style="flex:1">
          <div class="notif-title">${escHtml(n.titre)}</div>
          <div class="notif-msg">${escHtml(n.message)}</div>
          <div class="notif-time">${dateRelative(n.cree_le)}</div>
        </div>
      </div>`).join('');
  } catch { list.innerHTML = '<div style="padding:16px;color:var(--text3);font-size:13px">Erreur chargement</div>'; }
}

async function lireNotif(id, el) {
  el.classList.remove('unread');
  try { await api('notif_lire', {id}); actualiseBadgeNotif(-1); } catch {}
}

async function marquerToutLu() {
  try {
    await api('notifs_tout_lure', {});
    document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('unread'));
    const dot = document.querySelector('.notif-dot');
    if (dot) dot.remove();
  } catch (e) {
    await api('notifs_tout_lire', {});
    location.reload();
  }
}

function actualiseBadgeNotif(delta) {
  const dot = document.querySelector('.notif-dot');
  if (!dot) return;
  const nb = parseInt(dot.textContent||'0') + delta;
  if (nb <= 0) dot.remove();
  else dot.textContent = nb;
}

// ---- User menu ----
function toggleUserMenu() {
  const dd = document.getElementById('user-dropdown');
  if (!dd) return;
  const visible = dd.style.display !== 'none';
  document.querySelectorAll('.notif-dropdown,.user-dropdown').forEach(d => d.style.display = 'none');
  if (!visible) dd.style.display = 'block';
}

// Fermer dropdowns au clic extérieur
document.addEventListener('click', e => {
  if (!e.target.closest('.notif-wrap') && !e.target.closest('.user-menu-wrap')) {
    document.querySelectorAll('.notif-dropdown,.user-dropdown').forEach(d => d.style.display = 'none');
  }
});

// ---- Cart (localStorage) ----
const CART_KEY = 'rapidliv_cart';

function getCart() {
  try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch { return []; }
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartBadge();
}

function updateCartBadge() {
  const badge = document.getElementById('cart-count');
  if (!badge) return;
  const total = getCart().reduce((s, i) => s + i.quantite, 0);
  badge.textContent = total;
  badge.style.display = total > 0 ? 'inline-flex' : 'none';
}

function ajouterAuPanier(produit) {
  const cart = getCart();
  const idx  = cart.findIndex(i => i.id === produit.id);
  if (idx >= 0) cart[idx].quantite += 1;
  else cart.push({ ...produit, quantite: 1 });
  saveCart(cart);
}

function retirerDuPanier(produitId) {
  const cart = getCart().filter(i => i.id !== produitId);
  saveCart(cart);
}

function changerQuantite(produitId, delta) {
  const cart = getCart();
  const idx  = cart.findIndex(i => i.id === produitId);
  if (idx < 0) return;
  cart[idx].quantite += delta;
  if (cart[idx].quantite <= 0) cart.splice(idx, 1);
  saveCart(cart);
}

function viderPanier() {
  saveCart([]);
}

// ---- Utilitaires ----
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function dateRelative(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60)    return 'à l\'instant';
  if (diff < 3600)  return Math.floor(diff/60) + ' min';
  if (diff < 86400) return Math.floor(diff/3600) + 'h';
  return new Date(dateStr).toLocaleDateString('fr-FR');
}

function formatMontant(n) {
  return parseInt(n).toLocaleString('fr-FR') + ' FCFA';
}

// ---- Initialisation ----
document.addEventListener('DOMContentLoaded', () => {
  updateCartBadge();

  // Animation flash
  const style = document.createElement('style');
  style.textContent = `@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}`;
  document.head.appendChild(style);
});
