<?php
// pages/client/suivi.php
$user = utilisateurConnecte();
$id   = $_GET['id'] ?? '';
$commande = Database::queryOne(
    "SELECT c.*, s.nom AS service_nom, cs.icone AS service_icone,
            l.id AS livr_id, l.statut AS livr_statut, l.eta_minutes, l.code_confirmation,
            l.position_lat AS livr_lat, l.position_lng AS livr_lng,
            CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom, ul.telephone AS livreur_tel,
            lv.type_vehicule, lv.note_moyenne AS livreur_note
     FROM commandes c
     JOIN services s ON s.id=c.service_id
     JOIN categories_service cs ON cs.id=s.categorie_id
     LEFT JOIN livraisons l ON l.commande_id=c.id
     LEFT JOIN livreurs lv ON lv.id=l.livreur_id
     LEFT JOIN utilisateurs ul ON ul.id=lv.id
     WHERE c.id=? AND c.client_id=?", [$id, $user['id']]);
if (!$commande) rediriger('/rapidliv-php/public/index.php?page=client_commandes');

$imageSelect = Database::columnExists('produits', 'image_url') ? ', p.image_url' : '';
$items = Database::query(
    "SELECT ci.*$imageSelect, p.icone
     FROM commande_items ci
     LEFT JOIN produits p ON p.id=ci.produit_id
     WHERE ci.commande_id=?", [$id]);

function suiviProduitImageUrl(array $item): string {
    $image = trim($item['image_url'] ?? '');
    if ($image !== '') return $image;
    return 'https://loremflickr.com/320/240/' . rawurlencode($item['nom_produit'] . ',product');
}

function suiviProduitImageHtml(array $item): string {
    $src = htmlspecialchars(suiviProduitImageUrl($item), ENT_QUOTES, 'UTF-8');
    $alt = htmlspecialchars($item['nom_produit'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
    $fallback = htmlspecialchars($item['icone'] ?? '📦', ENT_QUOTES, 'UTF-8');
    return '<div class="product-img"><img src="' . $src . '" alt="' . $alt . '" loading="lazy" onerror="this.parentElement.textContent=\'' . $fallback . '\'"></div>';
}
$etapes = [
  ['en_attente',     'Commande reçue',     '📝', 'Votre commande a été enregistrée.'],
  ['confirmee',      'Confirmée',           '✅', 'Le service a confirmé votre commande.'],
  ['en_preparation', 'En préparation',      '👨‍🍳', 'Votre commande est en cours de préparation.'],
  ['en_route',       'En route',            '🏍️', 'Le livreur est en route vers vous.'],
  ['livree',         'Livrée',              '🎉', 'Commande livrée avec succès !'],
];
$idx_actuel = array_search($commande['statut'], array_column($etapes, 0));
if ($idx_actuel === false) $idx_actuel = 0;
?>

<div class="mb-16">
  <a href="index.php?page=client_commandes" class="btn btn-outline btn-sm">← Mes commandes</a>
</div>

<div class="page-header">
  <div>
    <div class="page-title"><?= sanitize($commande['reference']) ?></div>
    <div class="page-sub"><?= sanitize($commande['service_nom']) ?> · <?= formatDate($commande['cree_le']) ?></div>
  </div>
  <?= statutBadgeHTML($commande['statut']) ?>
</div>

<div class="grid-2" style="align-items:start">
  <!-- Tracker -->
  <div class="card">
    <div class="section-title">Suivi en temps réel</div>
    <div class="tracker">
    <?php foreach ($etapes as $i => [$code, $label, $icon, $desc]): ?>
      <?php
      $is_done   = $i < $idx_actuel;
      $is_active = $i === $idx_actuel;
      $is_pending= $i > $idx_actuel;
      $classe    = $is_done?'step-done':($is_active?'step-active':'step-pending');
      ?>
      <div class="tracker-step <?= $classe ?>">
        <div class="tracker-col">
          <div class="tracker-dot" style="font-size:13px"><?= $is_done?'✓':($is_active?$icon:'○') ?></div>
          <?php if ($i < count($etapes)-1): ?><div class="tracker-line" style="<?= $is_done?'background:var(--primary)':'' ?>"></div><?php endif; ?>
        </div>
        <div class="tracker-content" style="padding-bottom:8px">
          <div class="tracker-label" style="<?= $is_active?'color:var(--amber);font-weight:700':'' ?>"><?= $label ?></div>
          <div class="tracker-time"><?= $is_active||$is_done?$desc:'' ?></div>
          <?php if ($is_active && $commande['statut']==='en_route' && $commande['eta_minutes']): ?>
          <div class="badge badge-amber" style="margin-top:4px">ETA : <?= $commande['eta_minutes'] ?> min</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <?php if ($commande['statut']==='en_route'): ?>
    <div class="map-box mt-16" id="map-live">
      <div class="map-pin">🏍️</div>
      <div>Livreur en route · <strong id="eta-display"><?= $commande['eta_minutes']??'~' ?> min</strong></div>
      <div class="text-xs text-muted">La carte se met à jour automatiquement</div>
    </div>
    <?php endif; ?>

    <?php if ($commande['statut']==='livree'): ?>
    <div class="card-sm mt-16" style="background:var(--green-light);border-color:#c8e6c9;text-align:center;padding:20px">
      <div style="font-size:36px">🎉</div>
      <div class="fw-700 text-green">Livraison effectuée !</div>
      <div class="text-sm text-muted">Livrée le <?= $commande['livree_le']?formatDate($commande['livree_le']):'—' ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Récap + livreur -->
  <div>
    <?php if ($commande['livreur_nom']): ?>
    <div class="card mb-16">
      <div class="section-title">Votre livreur</div>
      <div class="flex gap-12">
        <div class="avatar-sm" style="width:44px;height:44px;font-size:16px">
          <?= strtoupper(substr($commande['livreur_nom'],0,2)) ?>
        </div>
        <div style="flex:1">
          <div class="fw-700"><?= sanitize($commande['livreur_nom']) ?></div>
          <div class="text-sm text-muted"><?= sanitize($commande['type_vehicule']??'Moto') ?></div>
          <div class="stars text-sm">★ <?= $commande['livreur_note']??'5.0' ?></div>
        </div>
        <?php if ($commande['livreur_tel']): ?>
        <a href="tel:<?= $commande['livreur_tel'] ?>" class="btn btn-primary btn-sm">📞 Appeler</a>
        <?php endif; ?>
      </div>
      <?php if ($commande['code_confirmation'] && !in_array($commande['statut'], ['livree','annulee'], true)): ?>
      <div class="card-sm mt-12" style="background:var(--primary-light);border-color:var(--primary);text-align:center">
        <div class="text-xs text-muted">Code de confirmation de livraison</div>
        <strong style="display:block;font-size:28px;letter-spacing:6px;color:var(--primary);margin:6px 0">
          <?= sanitize($commande['code_confirmation']) ?>
        </strong>
        <div class="text-xs text-muted">Gardez ce code secret. Donnez-le au livreur uniquement après réception de la commande.</div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card mb-16">
      <div class="section-title">Articles commandés</div>
      <?php foreach ($items as $item): ?>
      <div class="product-row">
        <?= suiviProduitImageHtml($item) ?>
        <div style="flex:1">
          <div class="product-name"><?= sanitize($item['nom_produit']) ?></div>
          <div class="product-price"><?= formatMontant($item['prix_unitaire']) ?> × <?= $item['quantite'] ?></div>
        </div>
        <div class="fw-600"><?= formatMontant($item['sous_total']) ?></div>
      </div>
      <?php endforeach; ?>
      <div class="section-sep"></div>
      <div class="flex-between mb-8"><span class="text-sm text-muted">Sous-total</span><span class="fw-600"><?= formatMontant($commande['sous_total']) ?></span></div>
      <div class="flex-between mb-8"><span class="text-sm text-muted">Livraison</span><span class="fw-600"><?= formatMontant($commande['frais_livraison']) ?></span></div>
      <div class="flex-between"><span class="fw-700">Total</span><span class="fw-700 text-primary" style="font-size:16px"><?= formatMontant($commande['total']) ?></span></div>
    </div>

    <div class="card">
      <div class="section-title">Adresse de livraison</div>
      <div class="text-sm">📍 <?= sanitize($commande['adresse_livraison']) ?></div>
      <?php if ($commande['instructions']): ?>
      <div class="text-sm text-muted mt-8">💬 <?= sanitize($commande['instructions']) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($commande['statut']==='en_route'): ?>
<script>
// Rafraîchir automatiquement toutes les 30 secondes
setInterval(() => {
  fetch('/rapidliv-php/api/api.php?action=commande_detail&id=<?= $id ?>')
    .then(r=>r.json()).then(c => {
      if (c.eta_minutes) document.getElementById('eta-display').textContent = c.eta_minutes+' min';
      if (c.statut === 'livree') location.reload();
    });
}, 30000);
</script>
<?php endif; ?>
