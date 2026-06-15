<?php
// pages/livreur/dashboard.php
$user = utilisateurConnecte();
$livreur = Database::queryOne(
    "SELECT l.*, z.nom AS zone_nom FROM livreurs l LEFT JOIN zones z ON z.id=l.zone_id WHERE l.id=?",
    [$user['id']]);
$mission_active = Database::queryOne(
    "SELECT l.*, c.reference, c.adresse_livraison, c.total,
            CONCAT(u.prenom,' ',u.nom) AS client_nom, u.telephone AS client_tel,
            s.nom AS service_nom
     FROM livraisons l
     JOIN commandes c ON c.id=l.commande_id
     JOIN utilisateurs u ON u.id=c.client_id
     JOIN services s ON s.id=c.service_id
     WHERE l.livreur_id=? AND l.statut IN ('assignee','en_route','arrivee')
     ORDER BY l.cree_le DESC LIMIT 1",
    [$user['id']]);
$stats_jour = Database::queryOne(
    "SELECT COUNT(*) AS livraisons, COALESCE(SUM(gain_livreur),0) AS gains
     FROM livraisons WHERE livreur_id=? AND DATE(livree_le)=CURDATE() AND statut='livree'",
    [$user['id']]);
$stats_semaine = Database::queryOne(
    "SELECT COUNT(*) AS livraisons, COALESCE(SUM(gain_livreur),0) AS gains
     FROM livraisons WHERE livreur_id=? AND YEARWEEK(livree_le)=YEARWEEK(NOW()) AND statut='livree'",
    [$user['id']]);
$avis = Database::query(
    "SELECT e.note, e.commentaire, e.cree_le FROM evaluations e
     WHERE e.cible_type='livreur' AND e.cible_id=? ORDER BY e.cree_le DESC LIMIT 5",
    [$user['id']]);
?>

<div class="page-header">
  <div>
    <div class="page-title">Tableau de bord</div>
    <div class="page-sub">Bonjour, <?= sanitize($user['nom']) ?></div>
  </div>
  <div class="flex gap-8">
    <span class="status-dot <?= $livreur['statut']==='disponible'?'status-on':($livreur['statut']==='en_course'?'status-busy':'status-off') ?>"></span>
    <span class="fw-600 text-sm">
      <?= $livreur['statut']==='disponible'?'En ligne':($livreur['statut']==='en_course'?'En course':'Hors ligne') ?>
    </span>
    <?php if ($livreur['documents_valides'] && $livreur['statut'] !== 'en_course'): ?>
    <button class="btn btn-sm <?= $livreur['statut']==='disponible'?'btn-outline':'btn-primary' ?>" onclick="toggleStatut(this)">
      <?= $livreur['statut']==='disponible'?'Passer hors-ligne':'Passer en ligne' ?>
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!$livreur['documents_valides']): ?>
<div class="card mb-20" style="border-color:var(--amber);background:var(--amber-light)">
  <div class="flex-between gap-12">
    <div>
      <div class="fw-700">Dossier livreur non validé</div>
      <div class="text-sm text-muted">
        <?= ($livreur['documents_statut'] ?? '') === 'rejetes'
          ? 'Vos documents ont été rejetés : ' . sanitize($livreur['documents_motif'] ?? 'veuillez les renvoyer.')
          : 'Vos documents sont en attente de vérification par un administrateur.' ?>
      </div>
    </div>
    <a href="index.php?page=livreur_profil" class="btn btn-primary btn-sm">Voir mes documents</a>
  </div>
</div>
<?php endif; ?>

<div class="grid-4 mb-20">
  <div class="card-sm"><div class="metric-label">Livraisons aujourd'hui</div><div class="metric-value"><?= $stats_jour['livraisons'] ?></div></div>
  <div class="card-sm"><div class="metric-label">Gains du jour</div><div class="metric-value" style="font-size:18px"><?= formatMontant($stats_jour['gains']) ?></div></div>
  <div class="card-sm"><div class="metric-label">Cette semaine</div><div class="metric-value"><?= $stats_semaine['livraisons'] ?> <span style="font-size:14px">livr.</span></div></div>
  <div class="card-sm"><div class="metric-label">Note moyenne</div><div class="metric-value"><span class="stars">★</span> <?= number_format($livreur['note_moyenne'],1) ?></div></div>
</div>

<div class="grid-2">
  <!-- Mission active -->
  <div class="card">
    <div class="section-title">Mission en cours</div>
    <?php if ($mission_active): ?>
    <div class="card-sm mb-12" style="border-color:var(--primary);background:var(--primary-light)">
      <div class="flex-between mb-12">
        <div>
          <div class="fw-700"><?= sanitize($mission_active['reference']) ?></div>
          <div class="text-sm"><?= sanitize($mission_active['service_nom']) ?></div>
        </div>
        <div class="fw-700 text-primary"><?= formatMontant($mission_active['total']) ?></div>
      </div>
      <div class="mb-12">
        <div class="text-sm fw-600"><?= sanitize($mission_active['client_nom']) ?></div>
        <div class="text-sm text-muted">📍 <?= sanitize($mission_active['adresse_livraison']) ?></div>
        <div class="text-sm text-muted">📞 <?= sanitize($mission_active['client_tel']) ?></div>
      </div>
      <div class="map-box mb-12" style="height:140px">
        <div class="map-pin">🏍️</div>
        <div>En route vers le client</div>
      </div>
      <div class="flex gap-8">
        <button class="btn btn-primary" style="flex:1" onclick="confirmerLivraison('<?= $mission_active['id'] ?>')">
          ✓ Confirmer la livraison
        </button>
        <a href="tel:<?= $mission_active['client_tel'] ?>" class="btn btn-outline">📞</a>
      </div>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:30px">
      <div class="empty-icon">😴</div>
      <div class="empty-title">Aucune mission active</div>
      <div class="empty-text">Les nouvelles missions apparaissent ici</div>
      <?php if ($livreur['documents_valides']): ?>
      <a href="index.php?page=livreur_missions" class="btn btn-primary btn-sm">Voir les missions disponibles</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Performances + avis -->
  <div>
    <div class="card mb-20">
      <div class="section-title">Performances semaine</div>
      <?php
      $objectif = 50;
      $pct = min(100, round($stats_semaine['livraisons']/$objectif*100));
      ?>
      <div class="progress-wrap">
        <div class="progress-label"><span>Livraisons</span><span class="fw-600"><?= $stats_semaine['livraisons'] ?> / <?= $objectif ?></span></div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
      <div class="progress-wrap">
        <div class="progress-label"><span>Satisfaction</span><span class="fw-600"><?= number_format($livreur['note_moyenne']/5*100,0) ?>%</span></div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $livreur['note_moyenne']/5*100 ?>%"></div></div>
      </div>
      <div class="section-sep"></div>
      <div class="flex-between">
        <span class="text-sm text-muted">Total livraisons</span>
        <span class="fw-700"><?= $livreur['nb_livraisons_total'] ?></span>
      </div>
      <div class="flex-between mt-8">
        <span class="text-sm text-muted">Gains totaux</span>
        <span class="fw-700 text-primary"><?= formatMontant($livreur['gains_total']) ?></span>
      </div>
    </div>

    <div class="card">
      <div class="section-title">Derniers avis</div>
      <?php if ($avis): ?>
      <?php foreach ($avis as $a): ?>
      <div style="padding:8px 0;border-bottom:1px solid var(--border-light)">
        <div class="stars"><?= str_repeat('★',$a['note']) ?><?= str_repeat('☆',5-$a['note']) ?></div>
        <div class="text-sm text-muted"><?= sanitize($a['commentaire']??'—') ?></div>
        <div class="text-xs text-muted"><?= dateRelative($a['cree_le']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="text-muted text-sm" style="padding:16px 0">Aucun avis pour l'instant</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal confirmation livraison -->
<div id="modal-confirm" class="modal-overlay" style="display:none">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <div class="modal-title">Confirmer la livraison</div>
      <button class="modal-close" onclick="fermerModal('modal-confirm')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="confirm-liv-id">
      <div class="form-group">
        <label class="form-label required">Code de confirmation du client</label>
        <input type="text" id="confirm-code" class="form-input" placeholder="Code à 4 chiffres" maxlength="4" style="text-align:center;font-size:24px;letter-spacing:4px">
        <div class="form-hint">Demandez le code au client pour confirmer la livraison</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fermerModal('modal-confirm')">Annuler</button>
      <button class="btn btn-primary" onclick="validerConfirmation()">Valider</button>
    </div>
  </div>
</div>

<script>
function confirmerLivraison(livId) {
  document.getElementById('confirm-liv-id').value = livId;
  document.getElementById('confirm-code').value = '';
  document.getElementById('modal-confirm').style.display = 'flex';
  setTimeout(() => document.getElementById('confirm-code').focus(), 100);
}
function validerConfirmation() {
  const id   = document.getElementById('confirm-liv-id').value;
  const code = document.getElementById('confirm-code').value.trim();
  if (code.length !== 4) { flash('Code à 4 chiffres requis', 'error'); return; }
  api('confirmer_livraison', {livraison_id: id, code})
    .then(() => { flash('Livraison confirmée ! Bravo !', 'success'); fermerModal('modal-confirm'); setTimeout(()=>location.reload(), 1500); })
    .catch(e => flash(e.message, 'error'));
}
function toggleStatut(btn) {
  const actuel = btn.textContent.trim().includes('hors-ligne') ? 'disponible' : 'hors_ligne';
  // En réalité, on regarde le statut actuel et on bascule
  const nouveauStatut = '<?= $livreur['statut'] ?>' === 'disponible' ? 'hors_ligne' : 'disponible';
  api('toggle_statut_livreur', {statut: nouveauStatut})
    .then(() => { flash('Statut mis à jour', 'success'); setTimeout(()=>location.reload(),1000); })
    .catch(e => flash(e.message, 'error'));
}
function dateRelative(d) {
  const diff = Math.floor((Date.now()-new Date(d))/1000);
  if(diff<60) return 'à l\'instant';
  if(diff<3600) return Math.floor(diff/60)+' min';
  return new Date(d).toLocaleDateString('fr-FR');
}
</script>
