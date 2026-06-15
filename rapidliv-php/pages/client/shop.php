<?php
// pages/client/shop.php
$service_id = (int)($_GET['id'] ?? 0);
if (!$service_id) rediriger('/rapidliv-php/public/index.php?page=accueil_client');

$service = Database::queryOne(
    "SELECT s.*, cs.nom AS cat_nom, cs.icone AS cat_icone
     FROM services s JOIN categories_service cs ON cs.id=s.categorie_id
     WHERE s.id=? AND s.actif=1", [$service_id]);
if (!$service) rediriger('/rapidliv-php/public/index.php?page=accueil_client');

$categories_produit = Database::query(
    "SELECT * FROM categories_produit WHERE service_id=? ORDER BY ordre", [$service_id]);
$produits = Database::query(
    "SELECT p.*, cp.nom AS cat_nom FROM produits p
     LEFT JOIN categories_produit cp ON cp.id=p.categorie_id
     WHERE p.service_id=? AND p.disponible=1 ORDER BY cp.ordre, p.nom", [$service_id]);

function produitImageUrl(array $produit): string {
    $image = trim($produit['image_url'] ?? '');
    if ($image !== '') return $image;
    return 'https://loremflickr.com/320/240/' . rawurlencode($produit['nom'] . ',product');
}

function produitImageHtml(array $produit): string {
    $src = htmlspecialchars(produitImageUrl($produit), ENT_QUOTES, 'UTF-8');
    $alt = htmlspecialchars($produit['nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
    $fallback = htmlspecialchars($produit['icone'] ?? '📦', ENT_QUOTES, 'UTF-8');
    return '<div class="product-img"><img src="' . $src . '" alt="' . $alt . '" loading="lazy" onerror="this.parentElement.textContent=\'' . $fallback . '\'"></div>';
}

function serviceHeroImageUrl(array $service): string {
    $logo = trim($service['logo_url'] ?? '');
    if ($logo !== '') return $logo;

    $cat = strtolower($service['cat_nom'] ?? '');
    if (str_contains($cat, 'restaurant')) return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80';
    if (str_contains($cat, 'super') || str_contains($cat, 'épicer')) return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80';
    if (str_contains($cat, 'pharma')) return 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=900&q=80';
    if (str_contains($cat, 'électron') || str_contains($cat, 'electron')) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80';
    return 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=900&q=80';
}

// Grouper par catégorie
$produits_par_cat = [];
foreach ($produits as $p) {
    $key = $p['cat_nom'] ?? 'Autres';
    $produits_par_cat[$key][] = $p;
}
?>

<div class="mb-16">
  <a href="index.php?page=accueil_client" class="btn btn-outline btn-sm">← Retour</a>
</div>

<!-- En-tête service -->
<div class="card mb-20" style="padding:0;overflow:hidden">
  <div class="media-shell" style="height:190px">
    <img src="<?= serviceHeroImageUrl($service) ?>" alt="<?= sanitize($service['nom']) ?>" class="real-photo">
  </div>
  <div class="flex gap-16" style="padding:20px 24px">
    <div style="font-size:34px;width:54px;height:54px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= $service['cat_icone'] ?></div>
    <div style="flex:1;min-width:0">
      <div class="page-title"><?= sanitize($service['nom']) ?></div>
      <div class="text-sm text-muted"><?= sanitize($service['cat_nom']) ?></div>
      <div class="flex gap-12 mt-8">
        <span class="badge badge-primary">
          <span class="stars">★</span>&nbsp;<?= number_format($service['note_moyenne'],1) ?>
        </span>
        <span class="text-sm text-muted">⏱ <?= $service['delai_min'] ?>–<?= $service['delai_max'] ?> min</span>
        <span class="text-sm text-muted">📦 Min. <?= formatMontant($service['commande_min']) ?></span>
        <?php if ($service['telephone']): ?>
        <span class="text-sm text-muted">📞 <?= sanitize($service['telephone']) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($service['description']): ?>
      <div class="text-sm text-muted mt-8"><?= sanitize($service['description']) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid-2" style="align-items:start">

  <!-- Catalogue -->
  <div>
    <?php if (empty($produits)): ?>
    <div class="card empty-state">
      <div class="empty-icon">📭</div>
      <div class="empty-title">Catalogue vide</div>
      <div class="empty-text">Aucun produit disponible pour le moment</div>
    </div>
    <?php else: ?>
    <?php foreach ($produits_par_cat as $cat_nom => $items): ?>
    <div class="card mb-16">
      <div class="section-title"><?= sanitize($cat_nom) ?></div>
      <?php foreach ($items as $p): ?>
      <div class="product-row" id="prod-row-<?= $p['id'] ?>">
        <?= produitImageHtml($p) ?>
        <div style="flex:1">
          <div class="product-name"><?= sanitize($p['nom']) ?></div>
          <?php if ($p['description']): ?>
          <div class="text-xs text-muted"><?= sanitize($p['description']) ?></div>
          <?php endif; ?>
          <div class="product-price">
            <?php if ($p['prix_promo'] && $p['prix_promo'] < $p['prix']): ?>
              <span style="text-decoration:line-through;color:var(--text3);font-weight:400"><?= formatMontant($p['prix']) ?></span>
              &nbsp;<span style="color:var(--accent)"><?= formatMontant($p['prix_promo']) ?></span>
            <?php else: ?>
              <?= formatMontant($p['prix']) ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changerQty(<?= $p['id'] ?>, -1)" id="btn-moins-<?= $p['id'] ?>" style="display:none">−</button>
          <span class="qty-num" id="qty-<?= $p['id'] ?>">0</span>
          <button class="qty-btn" onclick="changerQty(<?= $p['id'] ?>, 1)">+</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Panier latéral -->
  <div style="position:sticky;top:80px">
    <div class="card">
      <div class="flex-between mb-16">
        <div class="section-title" style="margin-bottom:0">Mon panier</div>
        <span class="badge badge-primary" id="panier-count">0 article</span>
      </div>

      <div id="panier-vide" class="text-center text-muted text-sm" style="padding:20px 0">
        Ajoutez des articles pour commencer
      </div>

      <div id="panier-items" style="display:none">
        <div id="panier-liste"></div>
        <div class="section-sep"></div>
        <div class="flex-between mb-8">
          <span class="text-sm text-muted">Sous-total</span>
          <span class="fw-600" id="panier-sous-total">0 FCFA</span>
        </div>
        <div class="flex-between mb-8">
          <span class="text-sm text-muted">Frais de livraison</span>
          <span class="fw-600"><?= formatMontant(FRAIS_LIVRAISON_BASE) ?></span>
        </div>
        <div class="flex-between mb-16" style="font-size:16px">
          <span class="fw-700">Total</span>
          <span class="fw-700 text-primary" id="panier-total">0 FCFA</span>
        </div>

        <div class="form-group">
          <label class="form-label">Adresse de livraison</label>
          <select class="form-select" id="adresse-select">
            <option value="">-- Choisir une adresse --</option>
          </select>
          <input type="text" class="form-input mt-8" id="adresse-manuelle" placeholder="Ou saisir une adresse..." style="margin-top:8px">
        </div>

        <div class="form-group">
          <label class="form-label">Mode de paiement</label>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ([['orange_money','📱 Orange Money'],['wave','🌊 Wave'],['cash','💵 Cash à la livraison'],['carte','💳 Carte bancaire']] as [$val,$label]): ?>
            <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer" id="pay-label-<?= $val ?>">
              <input type="radio" name="paiement" value="<?= $val ?>" onchange="selectPaiement('<?= $val ?>')" style="accent-color:var(--primary)">
              <span class="text-sm"><?= $label ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="btn btn-primary btn-full" onclick="passerCommande()" id="btn-commander">
          Passer la commande
        </button>
        <button class="btn btn-outline btn-full mt-8" onclick="viderPanierUI()" style="margin-top:8px">
          Vider le panier
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const SERVICE_ID   = <?= $service_id ?>;
const FRAIS        = <?= FRAIS_LIVRAISON_BASE ?>;
const COMMANDE_MIN = <?= $service['commande_min'] ?>;

// Produits disponibles (pour les prix)
const PRODUITS = {
  <?php foreach ($produits as $p): ?>
  <?= $p['id'] ?>: { id: <?= $p['id'] ?>, nom: <?= json_encode($p['nom']) ?>, prix: <?= $p['prix_promo'] && $p['prix_promo'] < $p['prix'] ? $p['prix_promo'] : $p['prix'] ?>, icone: <?= json_encode($p['icone']) ?>, image_url: <?= json_encode(produitImageUrl($p)) ?> },
  <?php endforeach; ?>
};

// Initialiser depuis localStorage
function init() {
  const cart = getCart().filter(i => Object.keys(PRODUITS).includes(String(i.id)));
  // Restaurer les quantités dans l'UI
  cart.forEach(item => {
    const el = document.getElementById('qty-'+item.id);
    const btn = document.getElementById('btn-moins-'+item.id);
    if (el) el.textContent = item.quantite;
    if (btn && item.quantite > 0) btn.style.display = '';
  });
  updatePanierUI();
}

function changerQty(id, delta) {
  const cart = getCart();
  const prod = PRODUITS[id];
  if (!prod) return;
  const idx = cart.findIndex(i => i.id === id);
  if (idx >= 0) {
    cart[idx].quantite += delta;
    if (cart[idx].quantite <= 0) cart.splice(idx, 1);
  } else if (delta > 0) {
    cart.push({ id: prod.id, nom: prod.nom, prix: prod.prix, icone: prod.icone, image_url: prod.image_url, service_id: SERVICE_ID, quantite: 1 });
  }
  saveCart(cart);

  // Mettre à jour UI produit
  const cur = cart.find(i => i.id === id);
  const qty = cur ? cur.quantite : 0;
  const elQty = document.getElementById('qty-'+id);
  const btnMoins = document.getElementById('btn-moins-'+id);
  if (elQty) elQty.textContent = qty;
  if (btnMoins) btnMoins.style.display = qty > 0 ? '' : 'none';

  updatePanierUI();
}

function updatePanierUI() {
  const cart = getCart().filter(i => i.service_id === SERVICE_ID);
  const total_items = cart.reduce((s,i) => s+i.quantite, 0);
  const sous_total  = cart.reduce((s,i) => s+i.prix*i.quantite, 0);
  const total       = sous_total + FRAIS;

  document.getElementById('panier-count').textContent = total_items + (total_items<=1?' article':' articles');

  if (!cart.length) {
    document.getElementById('panier-vide').style.display = '';
    document.getElementById('panier-items').style.display = 'none';
    return;
  }
  document.getElementById('panier-vide').style.display = 'none';
  document.getElementById('panier-items').style.display = '';

  document.getElementById('panier-liste').innerHTML = cart.map(i => `
    <div class="product-row">
      ${renderProductThumb(i)}
      <div style="flex:1">
        <div class="product-name">${escHtml(i.nom)}</div>
        <div class="product-price">${(i.prix).toLocaleString('fr-FR')} × ${i.quantite}</div>
      </div>
      <div class="fw-600">${(i.prix*i.quantite).toLocaleString('fr-FR')} F</div>
    </div>`).join('');

  document.getElementById('panier-sous-total').textContent = sous_total.toLocaleString('fr-FR') + ' FCFA';
  document.getElementById('panier-total').textContent      = total.toLocaleString('fr-FR') + ' FCFA';

  const btn = document.getElementById('btn-commander');
  btn.disabled = sous_total < COMMANDE_MIN;
  if (sous_total < COMMANDE_MIN) btn.title = `Minimum : ${COMMANDE_MIN.toLocaleString('fr-FR')} FCFA`;
}

function viderPanierUI() {
  if (!confirm('Vider le panier ?')) return;
  const cart = getCart().filter(i => i.service_id !== SERVICE_ID);
  saveCart(cart);
  // Reset quantités UI
  document.querySelectorAll('[id^="qty-"]').forEach(el => { el.textContent = '0'; });
  document.querySelectorAll('[id^="btn-moins-"]').forEach(el => { el.style.display = 'none'; });
  updatePanierUI();
}

function selectPaiement(val) {
  document.querySelectorAll('[id^="pay-label-"]').forEach(l => {
    l.style.borderColor = 'var(--border)';
  });
  const lbl = document.getElementById('pay-label-'+val);
  if (lbl) lbl.style.borderColor = 'var(--primary)';
}

async function passerCommande() {
  const cart = getCart().filter(i => i.service_id === SERVICE_ID);
  if (!cart.length) { flash('Votre panier est vide', 'error'); return; }

  const selEl  = document.getElementById('adresse-select');
  const manEl  = document.getElementById('adresse-manuelle');
  const adresse_sel = selEl ? selEl.value.trim() : '';
  const adresse_man = manEl ? manEl.value.trim() : '';
  const adresse = adresse_man || adresse_sel;
  if (!adresse) {
    flash('⚠️ Veuillez indiquer une adresse de livraison avant de commander', 'error');
    if (manEl) manEl.focus();
    return;
  }

  const methode = document.querySelector('input[name="paiement"]:checked')?.value || 'cash';
  const items   = cart.map(i => ({ produit_id: i.id, quantite: i.quantite }));

  const btn = document.getElementById('btn-commander');
  btn.disabled = true; btn.textContent = 'Envoi en cours...';

  try {
    const res = await api('passer_commande', {
      service_id: SERVICE_ID,
      adresse_livraison: adresse,
      methode_paiement: methode,
      items
    });
    // Vider le panier de ce service
    const cartRestant = getCart().filter(i => i.service_id !== SERVICE_ID);
    saveCart(cartRestant);
    flash('Commande passée avec succès ! Réf : ' + res.reference, 'success');
    setTimeout(() => { window.location.href = 'index.php?page=client_commandes'; }, 2000);
  } catch (e) {
    flash(e.message, 'error');
    btn.disabled = false; btn.textContent = 'Passer la commande';
  }
}

function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return escHtml(s).replace(/"/g,'&quot;'); }
function renderProductThumb(item) {
  const src = item.image_url || `https://loremflickr.com/320/240/${encodeURIComponent((item.nom || 'produit') + ',product')}`;
  const fallback = escHtml(item.icone || '📦');
  return `<div class="product-img"><img src="${escAttr(src)}" alt="${escAttr(item.nom || 'Produit')}" loading="lazy" onerror="this.parentElement.textContent='${fallback}'"></div>`;
}

// Attendre app.js avant d'initialiser
function demarrerShop() {
  if (typeof getCart !== 'function') { setTimeout(demarrerShop, 80); return; }
  init();
  // Sélectionner Orange Money par défaut
  const omRadio = document.querySelector('input[name="paiement"][value="orange_money"]');
  if (omRadio) { omRadio.checked = true; selectPaiement('orange_money'); }
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => setTimeout(demarrerShop, 50));
} else {
  setTimeout(demarrerShop, 50);
}
</script>
