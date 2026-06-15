<?php
// pages/client/panier.php
$user = utilisateurConnecte();
$adresses = Database::query(
    "SELECT * FROM adresses WHERE utilisateur_id=? ORDER BY principale DESC", [$user['id']]);
?>

<div class="page-header">
  <div>
    <div class="page-title">Mon panier</div>
    <div class="page-sub" id="panier-sub">Chargement...</div>
  </div>
  <a href="index.php?page=accueil_client" class="btn btn-outline btn-sm">← Continuer mes achats</a>
</div>

<div id="panier-vide-bloc" class="card empty-state" style="display:none">
  <div class="empty-icon">🛒</div>
  <div class="empty-title">Votre panier est vide</div>
  <div class="empty-text">Parcourez nos services pour ajouter des articles</div>
  <a href="index.php?page=accueil_client" class="btn btn-primary">Voir les services</a>
</div>

<div id="panier-contenu" class="grid-2" style="display:none;align-items:start">
  <!-- Articles -->
  <div class="card">
    <div class="flex-between mb-16">
      <div class="section-title" style="margin-bottom:0">Articles</div>
      <button class="btn btn-outline btn-sm" onclick="viderTout()">🗑 Vider</button>
    </div>
    <div id="cart-items-list"></div>
  </div>

  <!-- Récapitulatif -->
  <div>
    <div class="card mb-16">
      <div class="section-title">Récapitulatif</div>
      <div class="flex-between mb-8">
        <span class="text-sm text-muted">Sous-total</span>
        <span class="fw-600" id="recap-sous-total">—</span>
      </div>
      <div class="flex-between mb-8">
        <span class="text-sm text-muted">Frais de livraison</span>
        <span class="fw-600" id="recap-frais"><?= formatMontant(FRAIS_LIVRAISON_BASE) ?></span>
      </div>
      <div class="section-sep"></div>
      <div class="flex-between mb-20">
        <span class="fw-700" style="font-size:16px">Total</span>
        <span class="fw-700 text-primary" id="recap-total" style="font-size:20px">—</span>
      </div>
      <button class="btn btn-primary btn-full" onclick="validerCommande()" id="btn-valider">
        Passer la commande
      </button>
    </div>

    <!-- Adresse -->
    <div class="card mb-16">
      <div class="section-title">Adresse de livraison</div>
      <?php if ($adresses): ?>
      <div style="margin-bottom:12px">
        <?php foreach ($adresses as $adr): ?>
        <label style="display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;margin-bottom:8px" id="adr-label-<?= $adr['id'] ?>">
          <input type="radio" name="adresse" value="<?= sanitize($adr['rue'].', '.$adr['quartier'].', '.$adr['ville']) ?>" onchange="selectAdresse('<?= $adr['id'] ?>')" style="accent-color:var(--primary)" <?= $adr['principale']?'checked':'' ?>>
          <div>
            <div class="text-sm fw-600"><?= sanitize($adr['label']) ?></div>
            <div class="text-xs text-muted"><?= sanitize($adr['rue'].', '.$adr['quartier']) ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="form-hint" style="margin-bottom:8px">Ou saisir une autre adresse :</div>
      <?php endif; ?>
      <input type="text" class="form-input" id="adresse-libre" placeholder="Ex: 45 Rue Vincens, Plateau, Dakar">
    </div>

    <!-- Paiement -->
    <div class="card">
      <div class="section-title">Mode de paiement</div>
      <?php foreach ([
        ['orange_money','📱','Orange Money'],
        ['wave','🌊','Wave'],
        ['cash','💵','Cash à la livraison'],
        ['carte','💳','Carte bancaire'],
      ] as [$val,$icon,$label]): ?>
      <label style="display:flex;align-items:center;gap:12px;padding:12px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;margin-bottom:8px;transition:.15s" id="pay-wrap-<?= $val ?>">
        <input type="radio" name="paiement" value="<?= $val ?>" style="accent-color:var(--primary)" onchange="selectPay('<?= $val ?>')">
        <span style="font-size:20px"><?= $icon ?></span>
        <span class="fw-600 text-sm"><?= $label ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
const FRAIS_LIV = <?= FRAIS_LIVRAISON_BASE ?>;

function renderPanier() {
  const cart = getCart();
  const sub  = document.getElementById('panier-sub');
  const vide = document.getElementById('panier-vide-bloc');
  const cont = document.getElementById('panier-contenu');

  if (!cart.length) {
    sub.textContent  = 'Votre panier est vide';
    vide.style.display = '';
    cont.style.display = 'none';
    return;
  }

  const nb_items   = cart.reduce((s,i) => s+i.quantite, 0);
  const sous_total = cart.reduce((s,i) => s+i.prix*i.quantite, 0);
  const total      = sous_total + FRAIS_LIV;

  sub.textContent = nb_items + ' article' + (nb_items>1?'s':'') + ' · ' + total.toLocaleString('fr-FR') + ' FCFA';
  vide.style.display = 'none';
  cont.style.display = 'grid';

  // Grouper par service
  const parService = {};
  cart.forEach(i => {
    const k = i.service_id || 'inconnu';
    if (!parService[k]) parService[k] = [];
    parService[k].push(i);
  });

  let html = '';
  Object.values(parService).forEach(items => {
    items.forEach(item => {
      html += `
      <div class="product-row">
        ${renderProductThumb(item)}
        <div style="flex:1">
          <div class="product-name">${escHtml(item.nom)}</div>
          <div class="product-price">${item.prix.toLocaleString('fr-FR')} FCFA</div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="modifierQty(${item.id},-1)">−</button>
          <span class="qty-num">${item.quantite}</span>
          <button class="qty-btn" onclick="modifierQty(${item.id},1)">+</button>
          <button class="qty-btn" onclick="supprimerItem(${item.id})" title="Supprimer" style="color:var(--red);border-color:var(--red-light)">✕</button>
        </div>
      </div>`;
    });
  });
  document.getElementById('cart-items-list').innerHTML = html;

  document.getElementById('recap-sous-total').textContent = sous_total.toLocaleString('fr-FR') + ' FCFA';
  document.getElementById('recap-total').textContent      = total.toLocaleString('fr-FR') + ' FCFA';
  document.getElementById('btn-valider').disabled         = !cart.length;
}

function modifierQty(id, delta) {
  changerQuantite(id, delta);
  renderPanier();
}
function supprimerItem(id) {
  retirerDuPanier(id);
  renderPanier();
}
function viderTout() {
  if (confirm('Vider tout le panier ?')) { viderPanier(); renderPanier(); }
}

function selectAdresse(id) {
  document.querySelectorAll('[id^="adr-label-"]').forEach(l => l.style.borderColor='var(--border)');
  const lbl = document.getElementById('adr-label-'+id);
  if (lbl) lbl.style.borderColor = 'var(--primary)';
  document.getElementById('adresse-libre').value = '';
}

function selectPay(val) {
  document.querySelectorAll('[id^="pay-wrap-"]').forEach(l => l.style.borderColor='var(--border)');
  const w = document.getElementById('pay-wrap-'+val);
  if (w) w.style.borderColor = 'var(--primary)';
}

async function validerCommande() {
  const cart = getCart();
  if (!cart.length) return;

  // Vérifier qu'il n'y a qu'un seul service
  const services = [...new Set(cart.map(i=>i.service_id))];
  if (services.length > 1) {
    flash('Votre panier contient des articles de plusieurs services. Veuillez commander séparément.', 'error');
    return;
  }

  const adresse_radio = document.querySelector('input[name="adresse"]:checked')?.value || '';
  const adresse_libre = document.getElementById('adresse-libre').value.trim();
  const adresse = adresse_libre || adresse_radio;
  if (!adresse) { flash('Veuillez indiquer une adresse de livraison', 'error'); return; }

  const methode = document.querySelector('input[name="paiement"]:checked')?.value || 'cash';
  const items   = cart.map(i => ({ produit_id: i.id, quantite: i.quantite }));

  const btn = document.getElementById('btn-valider');
  btn.disabled = true; btn.textContent = 'Envoi...';

  try {
    const res = await api('passer_commande', {
      service_id: services[0],
      adresse_livraison: adresse,
      methode_paiement: methode,
      items
    });
    viderPanier();
    flash('✅ Commande passée ! Réf : ' + res.reference, 'success');
    setTimeout(() => { window.location.href = 'index.php?page=client_commandes'; }, 2000);
  } catch(e) {
    flash(e.message, 'error');
    btn.disabled=false; btn.textContent='Passer la commande';
  }
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return escHtml(s).replace(/"/g,'&quot;'); }
function renderProductThumb(item) {
  const src = item.image_url || `https://loremflickr.com/320/240/${encodeURIComponent((item.nom || 'produit') + ',product')}`;
  const fallback = escHtml(item.icone || '📦');
  return `<div class="product-img"><img src="${escAttr(src)}" alt="${escAttr(item.nom || 'Produit')}" loading="lazy" onerror="this.parentElement.textContent='${fallback}'"></div>`;
}

renderPanier();
// Pré-sélectionner la première adresse et Orange Money
const firstAdr = document.querySelector('input[name="adresse"]');
if (firstAdr) { firstAdr.checked=true; selectAdresse(firstAdr.closest('label').id.replace('adr-label-','')); }
selectPay('orange_money');
document.querySelector('input[value="orange_money"]').checked=true;
</script>
