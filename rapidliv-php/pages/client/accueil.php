<?php
// pages/client/accueil.php
$categories = Database::query("SELECT * FROM categories_service ORDER BY ordre");
$services   = Database::query(
    "SELECT s.*, cs.nom AS cat_nom, cs.icone AS cat_icone
     FROM services s JOIN categories_service cs ON cs.id=s.categorie_id
     WHERE s.actif=1 ORDER BY s.note_moyenne DESC");
$produits   = Database::query(
    "SELECT p.*, s.nom AS service_nom, cs.nom AS service_cat_nom
     FROM produits p
     JOIN services s ON s.id=p.service_id
     JOIN categories_service cs ON cs.id=s.categorie_id
     WHERE p.disponible=1 AND s.actif=1
     ORDER BY p.cree_le DESC, p.nom");
$user = utilisateurConnecte();
// Commande en cours
$cmd_encours = Database::queryOne(
    "SELECT c.*, s.nom AS service_nom, l.eta_minutes, CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom
     FROM commandes c JOIN services s ON s.id=c.service_id
     LEFT JOIN livraisons l ON l.commande_id=c.id
     LEFT JOIN livreurs lv ON lv.id=l.livreur_id
     LEFT JOIN utilisateurs ul ON ul.id=lv.id
     WHERE c.client_id=? AND c.statut IN ('en_attente','confirmee','en_preparation','en_route')
     ORDER BY c.cree_le DESC LIMIT 1",
    [$user['id']]);

function serviceImageUrl(array $service): string {
    $logo = trim($service['logo_url'] ?? '');
    if ($logo !== '') return $logo;

    $cat = strtolower($service['cat_nom'] ?? '');
    if (str_contains($cat, 'restaurant')) return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=700&q=80';
    if (str_contains($cat, 'super') || str_contains($cat, 'épicer')) return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80';
    if (str_contains($cat, 'pharma')) return 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=700&q=80';
    if (str_contains($cat, 'électron') || str_contains($cat, 'electron')) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=700&q=80';
    if (str_contains($cat, 'boulanger')) return 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=700&q=80';
    return 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=700&q=80';
}

function accueilProduitImageUrl(array $produit): string {
    $image = trim($produit['image_url'] ?? '');
    if ($image !== '') return $image;
    return 'https://loremflickr.com/480/360/' . rawurlencode(($produit['nom'] ?? 'produit') . ',product');
}
?>

<div class="page-header">
  <div>
    <div class="page-title">Bonjour, <?= sanitize($user['nom']) ?> 👋</div>
    <div class="page-sub">Que voulez-vous commander aujourd'hui ?</div>
  </div>
</div>

<!-- Recherche -->
<div class="search-bar mb-20">
  <div class="search-wrap" style="max-width:100%;flex:1">
    <span class="search-icon">🔍</span>
    <input type="text" class="search-input" id="recherche" placeholder="Rechercher un service, un produit..." oninput="filtrerServices()">
  </div>
</div>

<!-- Filtres catégories -->
<div class="flex gap-8 mb-20" style="flex-wrap:wrap">
  <button class="btn btn-primary btn-sm" onclick="filtrerCategorie(this,'')">Tous</button>
  <?php foreach ($categories as $cat): ?>
  <button class="btn btn-outline btn-sm" onclick="filtrerCategorie(this,'<?= sanitize($cat['nom']) ?>')">
    <?= $cat['icone'] ?> <?= sanitize($cat['nom']) ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Services -->
<div class="section-title">Services disponibles</div>
<div class="grid-auto mb-24" id="services-grid">
  <?php foreach ($services as $s): ?>
  <a href="index.php?page=client_shop&id=<?= $s['id'] ?>" class="shop-card" data-cat="<?= sanitize($s['cat_nom']) ?>" data-nom="<?= strtolower(sanitize($s['nom'])) ?>">
    <div class="shop-img">
      <img src="<?= serviceImageUrl($s) ?>" alt="<?= sanitize($s['nom']) ?>" loading="lazy">
      <span><?= $s['cat_icone'] ?></span>
    </div>
    <div class="shop-body">
      <div class="shop-name"><?= sanitize($s['nom']) ?></div>
      <div class="shop-meta"><?= sanitize($s['cat_nom']) ?> · <?= $s['delai_min'] ?>–<?= $s['delai_max'] ?> min</div>
      <div class="shop-meta" style="margin-top:4px">
        <span class="stars">★</span> <?= number_format($s['note_moyenne'],1) ?>
        · Min. <?= formatMontant($s['commande_min']) ?>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Produits -->
<div class="section-title">Produits disponibles</div>
<?php if (empty($produits)): ?>
<div class="card empty-state mb-24" id="produits-empty">
  <div class="empty-icon">📭</div>
  <div class="empty-title">Aucun produit disponible</div>
  <div class="empty-text">Les nouveaux produits apparaîtront ici.</div>
</div>
<?php else: ?>
<div class="grid-auto mb-24" id="produits-grid">
  <?php foreach ($produits as $p): ?>
  <a href="index.php?page=client_shop&id=<?= (int)$p['service_id'] ?>"
     class="shop-card"
     data-nom="<?= sanitize(strtolower($p['nom'] . ' ' . $p['service_nom'])) ?>">
    <div class="shop-img">
      <img src="<?= sanitize(accueilProduitImageUrl($p)) ?>"
           alt="<?= sanitize($p['nom']) ?>"
           loading="lazy"
           onerror="this.parentElement.textContent=<?= htmlspecialchars(json_encode($p['icone'] ?: '📦'), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="shop-body">
      <div class="shop-name"><?= sanitize($p['nom']) ?></div>
      <div class="shop-meta"><?= sanitize($p['service_nom']) ?> · <?= sanitize($p['service_cat_nom']) ?></div>
      <div class="product-price">
        <?php if ($p['prix_promo'] && $p['prix_promo'] < $p['prix']): ?>
          <span style="text-decoration:line-through;color:var(--text3);font-weight:400"><?= formatMontant($p['prix']) ?></span>
          <span style="color:var(--accent)"><?= formatMontant($p['prix_promo']) ?></span>
        <?php else: ?>
          <?= formatMontant($p['prix']) ?>
        <?php endif; ?>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<div class="card empty-state mb-24" id="produits-recherche-vide" style="display:none">
  <div class="empty-title">Aucun produit trouvé</div>
  <div class="empty-text">Essayez un autre terme de recherche.</div>
</div>
<?php endif; ?>

<!-- Commande en cours -->
<?php if ($cmd_encours): ?>
<div class="section-title">Commande en cours</div>
<div class="card">
  <div class="flex-between mb-16">
    <div>
      <div class="fw-700"><?= sanitize($cmd_encours['reference']) ?> — <?= sanitize($cmd_encours['service_nom']) ?></div>
      <div class="text-sm text-muted">Total : <?= formatMontant($cmd_encours['total']) ?></div>
    </div>
    <?= statutBadgeHTML($cmd_encours['statut']) ?>
  </div>

  <!-- Tracker -->
  <div class="tracker">
    <?php
    $etapes = [
      ['en_attente',     'Commande reçue',    '📝'],
      ['confirmee',      'Confirmée',          '✅'],
      ['en_preparation', 'En préparation',     '👨‍🍳'],
      ['en_route',       'En route',           '🏍️'],
      ['livree',         'Livrée',             '🎉'],
    ];
    $statutActuel = $cmd_encours['statut'];
    $indexActuel  = array_search($statutActuel, array_column($etapes, 0));
    foreach ($etapes as $i => [$stCode, $stLabel, $stIcon]):
      $classe = $i < $indexActuel ? 'step-done' : ($i === $indexActuel ? 'step-active' : 'step-pending');
    ?>
    <div class="tracker-step <?= $classe ?>">
      <div class="tracker-col">
        <div class="tracker-dot"><?= $i <= $indexActuel ? $stIcon : '○' ?></div>
        <?php if ($i < count($etapes)-1): ?><div class="tracker-line"></div><?php endif; ?>
      </div>
      <div class="tracker-content">
        <div class="tracker-label"><?= $stLabel ?></div>
        <?php if ($i === $indexActuel && $cmd_encours['livreur_nom']): ?>
          <div class="tracker-time">Livreur : <?= sanitize($cmd_encours['livreur_nom']) ?> · ETA : <?= $cmd_encours['eta_minutes'] ?? '~' ?> min</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($cmd_encours['statut'] === 'en_route'): ?>
  <div class="map-box mt-16">
    <div class="map-pin">🏍️</div>
    <div>Livreur en route · <?= $cmd_encours['eta_minutes'] ?? '~' ?> min estimées</div>
  </div>
  <?php endif; ?>

  <div class="mt-16">
    <a href="index.php?page=client_suivi&id=<?= $cmd_encours['id'] ?>" class="btn btn-outline btn-sm">Voir le suivi complet</a>
  </div>
</div>
<?php endif; ?>

<script>
let catActive = '';
function filtrerCategorie(btn, cat) {
  catActive = cat;
  document.querySelectorAll('.flex.gap-8 .btn').forEach(b => { b.className = 'btn btn-outline btn-sm'; });
  btn.className = 'btn btn-primary btn-sm';
  filtrerServices();
}
function filtrerServices() {
  const rech = document.getElementById('recherche').value.toLowerCase();
  document.querySelectorAll('#services-grid .shop-card').forEach(card => {
    const matchCat = !catActive || card.dataset.cat === catActive;
    const matchNom = !rech || card.dataset.nom.includes(rech);
    card.style.display = matchCat && matchNom ? '' : 'none';
  });

  let produitsVisibles = 0;
  document.querySelectorAll('#produits-grid .shop-card').forEach(card => {
    const matchNom = !rech || card.dataset.nom.includes(rech);
    card.style.display = matchNom ? '' : 'none';
    if (matchNom) produitsVisibles++;
  });

  const produitsVide = document.getElementById('produits-recherche-vide');
  if (produitsVide) {
    produitsVide.style.display = rech && produitsVisibles === 0 ? '' : 'none';
  }
}
</script>
