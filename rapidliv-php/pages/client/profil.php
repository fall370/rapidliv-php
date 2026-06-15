<?php // pages/client/profil.php
$user = utilisateurConnecte();
$infos = Database::queryOne("SELECT * FROM utilisateurs WHERE id=?", [$user['id']]);
$adresses = Database::query("SELECT * FROM adresses WHERE utilisateur_id=? ORDER BY principale DESC", [$user['id']]);
$stats = Database::queryOne("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS depenses FROM commandes WHERE client_id=?", [$user['id']]);
?>
<div class="page-title mb-20">Mon profil</div>
<div class="grid-2">
  <div class="card">
    <div class="flex gap-12 mb-20">
      <div class="avatar-sm" style="width:56px;height:56px;font-size:18px"><?= strtoupper(substr($infos['prenom'],0,1).substr($infos['nom'],0,1)) ?></div>
      <div><div class="fw-700" style="font-size:16px"><?= sanitize($infos['prenom'].' '.$infos['nom']) ?></div><div class="text-sm text-muted">Membre depuis <?= date('M. Y',strtotime($infos['cree_le'])) ?></div></div>
    </div>
    <div id="profil-error" class="flash flash-error" style="display:none;margin-bottom:12px"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Prénom</label><input class="form-input" id="p-prenom" value="<?= sanitize($infos['prenom']) ?>"></div>
      <div class="form-group"><label class="form-label">Nom</label><input class="form-input" id="p-nom" value="<?= sanitize($infos['nom']) ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Email</label><input class="form-input" value="<?= sanitize($infos['email']) ?>" disabled style="opacity:.6"></div>
    <div class="form-group"><label class="form-label">Téléphone</label><input class="form-input" id="p-tel" value="<?= sanitize($infos['telephone']) ?>"></div>
    <button class="btn btn-primary" onclick="sauverProfil()">Enregistrer</button>
  </div>
  <div>
    <div class="card mb-16">
      <div class="section-title">Statistiques</div>
      <div class="grid-2">
        <div class="card-sm"><div class="metric-label">Commandes</div><div class="metric-value"><?= $stats['total'] ?></div></div>
        <div class="card-sm"><div class="metric-label">Total dépensé</div><div class="metric-value" style="font-size:18px"><?= formatMontant($stats['depenses']) ?></div></div>
      </div>
    </div>
    <div class="card">
      <div class="flex-between mb-16"><div class="section-title" style="margin-bottom:0">Adresses</div><button class="btn btn-outline btn-sm" onclick="ajouterAdresse()">+ Ajouter</button></div>
      <?php if ($adresses): ?>
      <?php foreach ($adresses as $a): ?>
      <div class="flex gap-8 mb-8 card-sm">
        <span style="font-size:18px">📍</span>
        <div style="flex:1"><div class="text-sm fw-600"><?= sanitize($a['label']) ?><?= $a['principale']?' <span class="badge badge-primary">Principale</span>':'' ?></div><div class="text-xs text-muted"><?= sanitize($a['rue'].', '.$a['quartier']) ?></div></div>
      </div>
      <?php endforeach; ?>
      <?php else: ?><div class="text-muted text-sm">Aucune adresse enregistrée</div><?php endif; ?>
    </div>
  </div>
</div>
<script>
function sauverProfil() {
  api('maj_profil', { nom: document.getElementById('p-nom').value, prenom: document.getElementById('p-prenom').value, telephone: document.getElementById('p-tel').value })
    .then(() => flash('Profil mis à jour', 'success'))
    .catch(e => { document.getElementById('profil-error').textContent=e.message; document.getElementById('profil-error').style.display='flex'; });
}
function ajouterAdresse() { flash('Fonctionnalité à venir', 'info'); }
</script>
