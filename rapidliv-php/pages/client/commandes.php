<?php
// pages/client/commandes.php
$user      = utilisateurConnecte();
$statut    = $_GET['statut'] ?? '';
$page_num  = max(1,(int)($_GET['p']??1));
$par_page  = 10;
$offset    = ($page_num-1)*$par_page;

$where  = "WHERE c.client_id=?";
$params = [$user['id']];
if ($statut) { $where .= " AND c.statut=?"; $params[] = $statut; }

$total_count = Database::queryOne("SELECT COUNT(*) AS nb FROM commandes c $where", $params);
$pagination  = paginer((int)$total_count['nb'], $par_page, $page_num);
$params_page = array_merge($params, [$par_page, $offset]);

$commandes = Database::query(
    "SELECT c.*, s.nom AS service_nom, cs.icone AS service_icone,
            l.eta_minutes, l.statut AS livr_statut, l.code_confirmation,
            CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom
     FROM commandes c
     JOIN services s ON s.id=c.service_id
     JOIN categories_service cs ON cs.id=s.categorie_id
     LEFT JOIN livraisons l ON l.commande_id=c.id
     LEFT JOIN livreurs lv ON lv.id=l.livreur_id
     LEFT JOIN utilisateurs ul ON ul.id=lv.id
     $where ORDER BY c.cree_le DESC LIMIT ? OFFSET ?", $params_page);
?>

<div class="page-header">
  <div>
    <div class="page-title">Mes commandes</div>
    <div class="page-sub"><?= $pagination['total'] ?> commande<?= $pagination['total']>1?'s':'' ?></div>
  </div>
  <a href="index.php?page=accueil_client" class="btn btn-primary btn-sm">+ Nouvelle commande</a>
</div>

<!-- Filtres rapides -->
<div class="flex gap-8 mb-20" style="flex-wrap:wrap">
  <?php foreach ([''=> 'Toutes', 'en_attente'=>'En attente', 'en_route'=>'En route', 'livree'=>'Livrées', 'annulee'=>'Annulées'] as $val=>$label): ?>
  <a href="index.php?page=client_commandes<?= $val?"&statut=$val":'' ?>"
     class="btn btn-sm <?= $statut===$val?'btn-primary':'btn-outline' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$commandes): ?>
<div class="card empty-state">
  <div class="empty-icon">📦</div>
  <div class="empty-title">Aucune commande</div>
  <div class="empty-text">Vous n'avez pas encore passé de commande<?= $statut?" avec ce statut":'' ?></div>
  <a href="index.php?page=accueil_client" class="btn btn-primary">Commander maintenant</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($commandes as $c): ?>
<div class="card" style="cursor:pointer" onclick="voirCommande('<?= $c['id'] ?>')">
  <div class="flex-between mb-12">
    <div class="flex gap-12">
      <div style="font-size:28px"><?= $c['service_icone'] ?></div>
      <div>
        <div class="fw-700"><?= sanitize($c['reference']) ?></div>
        <div class="text-sm text-muted"><?= sanitize($c['service_nom']) ?></div>
        <div class="text-xs text-muted"><?= formatDate($c['cree_le']) ?></div>
      </div>
    </div>
    <div style="text-align:right">
      <?= statutBadgeHTML($c['statut']) ?>
      <div class="fw-700 text-primary mt-8"><?= formatMontant($c['total']) ?></div>
    </div>
  </div>

  <?php if (in_array($c['statut'], ['en_attente','confirmee','en_preparation','en_route'])): ?>
  <!-- Barre de progression -->
  <?php
  $etapes_map = ['en_attente'=>0,'confirmee'=>1,'en_preparation'=>2,'en_route'=>3,'livree'=>4];
  $idx_actuel = $etapes_map[$c['statut']] ?? 0;
  $pct = round($idx_actuel/4*100);
  ?>
  <div class="progress-wrap">
    <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
  </div>
  <div class="flex-between text-xs text-muted mt-8">
    <span>Commande reçue</span>
    <?php if ($c['statut']==='en_route' && $c['livreur_nom']): ?>
      <span>🏍️ <?= sanitize($c['livreur_nom']) ?> · <?= $c['eta_minutes']??'~' ?> min</span>
    <?php endif; ?>
    <span>Livraison</span>
  </div>
  <?php endif; ?>

  <?php if ($c['code_confirmation'] && !in_array($c['statut'], ['livree','annulee'], true)): ?>
  <div class="card-sm mt-12" style="background:var(--primary-light);border-color:var(--primary)">
    <div class="flex-between">
      <div>
        <div class="text-xs text-muted">Code à donner au livreur après réception</div>
        <div class="fw-700 text-primary" style="font-size:22px;letter-spacing:5px"><?= sanitize($c['code_confirmation']) ?></div>
      </div>
      <a href="index.php?page=client_suivi&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" onclick="event.stopPropagation()">Voir le suivi</a>
    </div>
  </div>
  <?php endif; ?>

  <div class="flex gap-8 mt-12">
    <button class="btn btn-outline btn-sm" onclick="event.stopPropagation();voirCommande('<?= $c['id'] ?>')">Voir détail</button>
    <?php if ($c['statut']==='en_route'): ?>
    <a href="index.php?page=client_suivi&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">📍 Suivre</a>
    <?php endif; ?>
    <?php if (in_array($c['statut'],['en_attente','confirmee'])): ?>
    <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();annulerCommande('<?= $c['id'] ?>')">Annuler</button>
    <?php endif; ?>
    <?php if ($c['statut']==='livree'): ?>
    <button class="btn btn-outline btn-sm" onclick="event.stopPropagation();evaluerCommande('<?= $c['id'] ?>','<?= $c['service_id'] ?>')">⭐ Évaluer</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pagination['total_pages']>1): ?>
<div class="pagination">
  <a href="?page=client_commandes&p=<?=$page_num-1?>&statut=<?=$statut?>" class="page-link <?=!$pagination['a_precedent']?'disabled':''?>">← Préc.</a>
  <?php for($i=1;$i<=$pagination['total_pages'];$i++): ?>
  <a href="?page=client_commandes&p=<?=$i?>&statut=<?=$statut?>" class="page-link <?=$i===$page_num?'active':''?>"><?=$i?></a>
  <?php endfor; ?>
  <a href="?page=client_commandes&p=<?=$page_num+1?>&statut=<?=$statut?>" class="page-link <?=!$pagination['a_suivant']?'disabled':''?>">Suiv. →</a>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Modal détail -->
<div id="modal-cmd" class="modal-overlay" style="display:none" onclick="if(event.target===this)fermerModal('modal-cmd')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-cmd-titre">Commande</div>
      <button class="modal-close" onclick="fermerModal('modal-cmd')">✕</button>
    </div>
    <div class="modal-body" id="modal-cmd-body">Chargement...</div>
  </div>
</div>

<!-- Modal évaluation -->
<div id="modal-eval" class="modal-overlay" style="display:none" onclick="if(event.target===this)fermerModal('modal-eval')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Évaluer la commande</div>
      <button class="modal-close" onclick="fermerModal('modal-eval')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="eval-cmd-id">
      <input type="hidden" id="eval-service-id">
      <div class="form-group">
        <label class="form-label">Note (1 à 5 étoiles)</label>
        <div class="flex gap-8" id="stars-wrap" style="font-size:28px;cursor:pointer">
          <?php for($i=1;$i<=5;$i++): ?>
          <span onclick="setNote(<?=$i?>)" id="star-<?=$i?>" style="color:var(--border);transition:.1s">★</span>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Commentaire (optionnel)</label>
        <textarea class="form-textarea" id="eval-commentaire" placeholder="Votre avis..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fermerModal('modal-eval')">Annuler</button>
      <button class="btn btn-primary" onclick="envoyerEval()">Envoyer</button>
    </div>
  </div>
</div>

<script>
let noteSelectionnee = 0;

function voirCommande(id) {
  document.getElementById('modal-cmd-body').innerHTML = '<div style="text-align:center;padding:30px">Chargement...</div>';
  document.getElementById('modal-cmd').style.display = 'flex';
  api('commande_detail', {}, 'GET', {id}).then(c => {
    document.getElementById('modal-cmd-titre').textContent = c.reference;
    let h = `<div class="flex-between mb-12"><div><strong>${escHtml(c.service_nom)}</strong></div>${badgeHtml(c.statut)}</div>`;
    h += `<div class="text-sm text-muted mb-12">📍 ${escHtml(c.adresse_livraison)}</div>`;
    if(c.livreur_nom) h += `<div class="text-sm mb-12">🏍️ Livreur : <strong>${escHtml(c.livreur_nom)}</strong>${c.livreur_tel?' · '+escHtml(c.livreur_tel):''}</div>`;
    h += '<div class="section-sep"></div><div class="section-title">Articles</div>';
    (c.items||[]).forEach(i => { h += `<div class="product-row">${renderProductThumb(i)}<div style="flex:1"><div class="product-name">${escHtml(i.nom_produit)}</div><div class="product-price">${parseInt(i.prix_unitaire).toLocaleString('fr-FR')} × ${i.quantite}</div></div><div class="fw-600">${parseInt(i.sous_total).toLocaleString('fr-FR')} F</div></div>`; });
    h += `<div class="section-sep"></div>`;
    h += `<div class="flex-between mb-8"><span>Sous-total</span><strong>${parseInt(c.sous_total).toLocaleString('fr-FR')} FCFA</strong></div>`;
    h += `<div class="flex-between mb-16"><span>Livraison</span><strong>${parseInt(c.frais_livraison).toLocaleString('fr-FR')} FCFA</strong></div>`;
    h += `<div class="flex-between" style="font-size:16px"><span class="fw-700">Total</span><strong style="color:var(--primary)">${parseInt(c.total).toLocaleString('fr-FR')} FCFA</strong></div>`;
    document.getElementById('modal-cmd-body').innerHTML = h;
  }).catch(e => { document.getElementById('modal-cmd-body').innerHTML = '<div class="text-muted">Erreur : '+escHtml(e.message)+'</div>'; });
}

function annulerCommande(id) {
  const raison = prompt('Raison de l\'annulation (optionnel) :');
  if (raison === null) return;
  api('annuler_commande', {commande_id: id, raison})
    .then(() => { flash('Commande annulée', 'success'); setTimeout(()=>location.reload(),1200); })
    .catch(e => flash(e.message, 'error'));
}

function escAttr(s) { return escHtml(s).replace(/"/g,'&quot;'); }
function renderProductThumb(item) {
  const src = item.image_url || `https://loremflickr.com/320/240/${encodeURIComponent((item.nom_produit || 'produit') + ',product')}`;
  const fallback = escHtml(item.icone || '📦');
  return `<div class="product-img"><img src="${escAttr(src)}" alt="${escAttr(item.nom_produit || 'Produit')}" loading="lazy" onerror="this.parentElement.textContent='${fallback}'"></div>`;
}

function evaluerCommande(cmdId, serviceId) {
  document.getElementById('eval-cmd-id').value = cmdId;
  document.getElementById('eval-service-id').value = serviceId;
  noteSelectionnee = 0;
  setNote(0);
  document.getElementById('eval-commentaire').value = '';
  document.getElementById('modal-eval').style.display = 'flex';
}

function setNote(n) {
  noteSelectionnee = n;
  for(let i=1;i<=5;i++) {
    document.getElementById('star-'+i).style.color = i<=n?'#f59e0b':'var(--border)';
  }
}

async function envoyerEval() {
  if (!noteSelectionnee) { flash('Sélectionnez une note', 'error'); return; }
  const data = {
    commande_id: document.getElementById('eval-cmd-id').value,
    cible_type: 'service',
    cible_id: document.getElementById('eval-service-id').value,
    note: noteSelectionnee,
    commentaire: document.getElementById('eval-commentaire').value
  };
  try {
    await api('ajouter_evaluation', data);
    flash('Merci pour votre évaluation !', 'success');
    fermerModal('modal-eval');
  } catch(e) { flash(e.message,'error'); }
}

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function badgeHtml(s){const m={en_attente:'badge-gray En attente',confirmee:'badge-blue Confirmée',en_preparation:'badge-amber En préparation',en_route:'badge-blue En route',livree:'badge-green Livrée',annulee:'badge-red Annulée'};const[c,l]=(m[s]||'badge-gray '+s).split(' ',2);return`<span class="badge ${c}">${l}</span>`;}
</script>
