<?php // pages/livreur/missions.php
$user = utilisateurConnecte();
$dossier_livreur = Database::queryOne(
    "SELECT documents_valides, documents_statut, documents_motif FROM livreurs WHERE id=?",
    [$user['id']]
);
$missions = $dossier_livreur['documents_valides'] ? Database::query(
    "SELECT c.*, s.nom AS service_nom, cs.icone AS service_icone,
            CONCAT(u.prenom,' ',u.nom) AS client_nom, u.telephone AS client_tel
     FROM commandes c
     JOIN services s ON s.id=c.service_id
     JOIN categories_service cs ON cs.id=s.categorie_id
     JOIN utilisateurs u ON u.id=c.client_id
     WHERE c.statut='en_attente'
     ORDER BY c.cree_le ASC") : [];
?>
<div class="page-header">
  <div><div class="page-title">Missions disponibles</div><div class="page-sub"><?= count($missions) ?> mission<?= count($missions)>1?'s':'' ?> en attente</div></div>
  <button class="btn btn-outline btn-sm" onclick="location.reload()">⟳ Actualiser</button>
</div>
<?php if (!$dossier_livreur['documents_valides']): ?>
<div class="card empty-state">
  <div class="empty-title">Documents non validés</div>
  <div class="empty-text">
    <?= ($dossier_livreur['documents_statut'] ?? '') === 'rejetes'
      ? 'Votre dossier a été rejeté : ' . sanitize($dossier_livreur['documents_motif'] ?? '')
      : 'Attendez la validation de votre photo et de votre carte d’identité par un administrateur.' ?>
  </div>
  <a href="index.php?page=livreur_profil" class="btn btn-primary">Voir mon dossier</a>
</div>
<?php elseif (!$missions): ?>
<div class="card empty-state">
  <div class="empty-icon">😴</div>
  <div class="empty-title">Aucune mission disponible</div>
  <div class="empty-text">Revenez dans quelques instants</div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($missions as $m): ?>
<div class="card" style="border-left:4px solid var(--amber)">
  <div class="flex-between mb-12">
    <div class="flex gap-12">
      <div style="font-size:28px"><?= $m['service_icone'] ?></div>
      <div><div class="fw-700"><?= sanitize($m['reference']) ?></div><div class="text-sm text-muted"><?= sanitize($m['service_nom']) ?></div></div>
    </div>
    <div class="text-right"><div class="fw-700 text-primary"><?= formatMontant($m['total']) ?></div><div class="text-xs text-muted">Gain estimé : <?= formatMontant(round($m['frais_livraison']*COMMISSION_LIVREUR/100)) ?></div></div>
  </div>
  <div class="text-sm mb-8">📍 <?= sanitize($m['adresse_livraison']) ?></div>
  <div class="text-sm text-muted mb-12">👤 <?= sanitize($m['client_nom']) ?> · <?= dateRelative($m['cree_le']) ?></div>
  <div class="flex gap-8">
    <button class="btn btn-primary" style="flex:1" onclick="accepterMission('<?= $m['id'] ?>')">✓ Accepter la mission</button>
    <button class="btn btn-outline" onclick="voirItems('<?= $m['id'] ?>')">Détail</button>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div id="modal-items" class="modal-overlay" style="display:none" onclick="if(event.target===this)fermerModal('modal-items')">
  <div class="modal"><div class="modal-header"><div class="modal-title">Détail commande</div><button class="modal-close" onclick="fermerModal('modal-items')">✕</button></div><div class="modal-body" id="modal-items-body">Chargement...</div></div>
</div>
<script>
function accepterMission(id) {
  if (!confirm('Accepter cette mission ?')) return;
  // L'admin assigne via le dashboard admin. Ici on simule une auto-assignation
  flash('Mission acceptée ! L\'admin va vous confirmer.', 'success');
}
function voirItems(id) {
  document.getElementById('modal-items-body').innerHTML = '<div style="text-align:center;padding:20px">Chargement...</div>';
  document.getElementById('modal-items').style.display = 'flex';
  api('commande_detail',{},'GET',{id}).then(c=>{
    let h = `<div class="fw-700 mb-8">${c.service_nom}</div>`;
    (c.items||[]).forEach(i=>{ h+=`<div class="product-row">${renderProductThumb(i)}<div style="flex:1"><div class="product-name">${escHtml(i.nom_produit)}</div><div class="product-price">${parseInt(i.prix_unitaire).toLocaleString()} × ${i.quantite}</div></div><div class="fw-600">${parseInt(i.sous_total).toLocaleString()} F</div></div>`; });
    h += `<div class="section-sep"></div><div class="flex-between"><span class="fw-700">Total</span><strong style="color:var(--primary)">${parseInt(c.total).toLocaleString()} FCFA</strong></div>`;
    document.getElementById('modal-items-body').innerHTML = h;
  });
}
function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;');}
function escAttr(s){return escHtml(s).replace(/"/g,'&quot;');}
function renderProductThumb(item){
  const src = item.image_url || `https://loremflickr.com/320/240/${encodeURIComponent((item.nom_produit || 'produit') + ',product')}`;
  const fallback = escHtml(item.icone || '📦');
  return `<div class="product-img"><img src="${escAttr(src)}" alt="${escAttr(item.nom_produit || 'Produit')}" loading="lazy" onerror="this.parentElement.textContent='${fallback}'"></div>`;
}
function dateRelative(d){const diff=Math.floor((Date.now()-new Date(d))/1000);if(diff<60)return 'à l\'instant';if(diff<3600)return Math.floor(diff/60)+' min';return new Date(d).toLocaleDateString('fr-FR');}
</script>
