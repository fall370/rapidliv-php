<?php
$user = utilisateurConnecte();
$infos = Database::queryOne(
    "SELECT u.*, l.*, l.statut AS livreur_statut, z.nom AS zone_nom
     FROM utilisateurs u
     JOIN livreurs l ON l.id=u.id
     LEFT JOIN zones z ON z.id=l.zone_id
     WHERE u.id=?",
    [$user['id']]
);
$zones = Database::query("SELECT * FROM zones WHERE actif=1 ORDER BY nom");
$documentsStatut = $infos['documents_statut'] ?? ($infos['documents_valides'] ? 'valides' : 'en_attente');
$badgeDocuments = match($documentsStatut) {
    'valides' => ['badge-green', 'Validés'],
    'rejetes' => ['badge-red', 'Rejetés'],
    default   => ['badge-amber', 'En attente de validation'],
};
?>

<div class="page-title mb-20">Mon profil livreur</div>
<div class="grid-2">
  <div class="card">
    <div class="flex gap-12 mb-20">
      <div class="avatar-sm" style="width:56px;height:56px;font-size:18px;overflow:hidden">
        <?php if ($infos['photo_url']): ?>
          <img src="<?= sanitize($infos['photo_url']) ?>" alt="Photo de profil" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <?= strtoupper(substr($infos['prenom'],0,1).substr($infos['nom'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="fw-700" style="font-size:16px"><?= sanitize($infos['prenom'].' '.$infos['nom']) ?></div>
        <div class="stars">★ <?= number_format($infos['note_moyenne'],1) ?></div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Prénom</label><input class="form-input" id="p-prenom" value="<?= sanitize($infos['prenom']) ?>"></div>
      <div class="form-group"><label class="form-label">Nom</label><input class="form-input" id="p-nom" value="<?= sanitize($infos['nom']) ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Téléphone</label><input class="form-input" id="p-tel" value="<?= sanitize($infos['telephone']) ?>"></div>
    <div class="form-group">
      <label class="form-label">Zone de livraison</label>
      <select class="form-select">
        <option value=""><?= sanitize($infos['zone_nom']??'—') ?></option>
        <?php foreach($zones as $z): ?><option value="<?=$z['id']?>" <?=$z['id']==$infos['zone_id']?'selected':''?>><?=sanitize($z['nom'])?></option><?php endforeach;?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Véhicule</label>
      <select class="form-select">
        <option <?=$infos['type_vehicule']==='Moto'?'selected':''?>>Moto</option>
        <option <?=$infos['type_vehicule']==='Vélo'?'selected':''?>>Vélo</option>
        <option <?=$infos['type_vehicule']==='Voiture'?'selected':''?>>Voiture</option>
      </select>
    </div>
    <button class="btn btn-primary" onclick="sauverProfilLivreur()">Enregistrer</button>
  </div>

  <div>
    <div class="card mb-16">
      <div class="section-title">Performances</div>
      <div class="grid-2 mb-12">
        <div class="card-sm"><div class="metric-label">Livraisons</div><div class="metric-value"><?= $infos['nb_livraisons_total'] ?></div></div>
        <div class="card-sm"><div class="metric-label">Gains totaux</div><div class="metric-value" style="font-size:16px"><?= formatMontant($infos['gains_total']) ?></div></div>
      </div>
    </div>

    <div class="card">
      <div class="section-title">Documents obligatoires</div>
      <div class="flex-between mb-12">
        <span class="text-sm fw-600">État du dossier</span>
        <span class="badge <?= $badgeDocuments[0] ?>"><?= $badgeDocuments[1] ?></span>
      </div>

      <?php if ($documentsStatut === 'rejetes' && $infos['documents_motif']): ?>
      <div class="flash flash-error" style="margin-bottom:12px">Motif : <?= sanitize($infos['documents_motif']) ?></div>
      <?php elseif ($documentsStatut === 'en_attente'): ?>
      <div class="text-sm text-muted mb-12">Un administrateur doit vérifier votre photo et votre carte d’identité avant votre mise en ligne.</div>
      <?php endif; ?>

      <div class="flex gap-8 mb-16" style="flex-wrap:wrap">
        <?php if ($infos['photo_url']): ?>
        <a href="<?= sanitize($infos['photo_url']) ?>" target="_blank" class="btn btn-outline btn-sm">Voir ma photo</a>
        <?php endif; ?>
        <?php if ($infos['carte_identite_fichier']): ?>
        <a href="/rapidliv-php/api/api.php?action=document_identite_livreur&id=<?= urlencode($user['id']) ?>" target="_blank" class="btn btn-outline btn-sm">Voir ma carte d’identité</a>
        <?php endif; ?>
      </div>

      <?php if ($documentsStatut !== 'valides'): ?>
      <div class="form-group">
        <label class="form-label required">Nouvelle photo de profil</label>
        <input type="file" id="doc-photo" class="form-input" accept="image/jpeg,image/png,image/webp" capture="user">
      </div>
      <div class="form-group">
        <label class="form-label required">Nouvelle photo de carte d’identité</label>
        <input type="file" id="doc-identite" class="form-input" accept="image/jpeg,image/png,image/webp" capture="environment">
      </div>
      <button class="btn btn-primary" onclick="renvoyerDocuments()">Envoyer les documents</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function sauverProfilLivreur() {
  api('maj_profil', {
    nom: document.getElementById('p-nom').value,
    prenom: document.getElementById('p-prenom').value,
    telephone: document.getElementById('p-tel').value
  }).then(() => flash('Profil mis à jour','success'))
    .catch(e => flash(e.message,'error'));
}

async function renvoyerDocuments() {
  const photo = document.getElementById('doc-photo').files[0];
  const identite = document.getElementById('doc-identite').files[0];
  if (!photo || !identite) {
    flash('Sélectionnez la photo de profil et la carte d’identité', 'error');
    return;
  }
  const data = new FormData();
  data.append('photo_profil', photo);
  data.append('carte_identite', identite);
  try {
    await api('mettre_a_jour_documents_livreur', data);
    flash('Documents envoyés pour validation', 'success');
    setTimeout(() => location.reload(), 1000);
  } catch (e) {
    flash(e.message, 'error');
  }
}
</script>
