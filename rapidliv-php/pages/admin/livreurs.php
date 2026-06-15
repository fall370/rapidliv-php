<?php
// ============================================================
//  RAPIDLIV — Admin : Gestion des livreurs (VERSION MODERNE + GOOGLE ICONS)
//  Fichier : pages/admin/livreurs.php
// ============================================================

$livreurs = Database::query(
    "SELECT l.*, u.nom, u.prenom, u.email, u.telephone, u.photo_url, u.actif AS user_actif, u.cree_le,
            z.nom AS zone_nom,
            (SELECT COUNT(*) FROM livraisons lv WHERE lv.livreur_id=l.id AND lv.statut='livree') AS nb_livraisons_done,
            (SELECT COUNT(*) FROM livraisons lv WHERE lv.livreur_id=l.id AND lv.statut NOT IN ('livree','echouee')) AS missions_actives
     FROM livreurs l
     JOIN utilisateurs u  ON u.id  = l.id
     LEFT JOIN zones z    ON z.id  = l.zone_id
     ORDER BY u.nom ASC");

$zones = Database::query("SELECT * FROM zones WHERE actif=1 ORDER BY nom");

$stats = [
  'total'      => count($livreurs),
  'disponible' => count(array_filter($livreurs, fn($l) => $l['statut']==='disponible')),
  'en_course'  => count(array_filter($livreurs, fn($l) => $l['statut']==='en_course')),
  'hors_ligne' => count(array_filter($livreurs, fn($l) => $l['statut']==='hors_ligne')),
  'suspendu'   => count(array_filter($livreurs, fn($l) => $l['statut']==='suspendu')),
];
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<style>
  :root {
    --rl-primary: #0E8F6E;
    --rl-primary-hover: #08624E;
    --rl-surface: #ffffff;
    --rl-bg: #f8fafc;
    --rl-text: #0f172a;
    --rl-muted: #64748b;
    --rl-border: #e2e8f0;
    --rl-radius-lg: 14px;
    --rl-radius-md: 10px;
    --rl-shadow: 0 1px 2px rgba(16, 32, 51, .04), 0 8px 24px rgba(16, 32, 51, .06);
  }

  body { background-color: var(--rl-bg); color: var(--rl-text); }
  
  .modern-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
  .modern-title { font-size: 24px; font-weight: 700; color: var(--rl-text); letter-spacing: -0.5px; }
  .modern-subtitle { font-size: 14px; color: var(--rl-muted); margin-top: 2px; }

  .btn-modern {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 14px; 
    font-size: 14px; font-weight: 500; border-radius: var(--rl-radius-md); 
    transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent; text-decoration: none;
  }
  .btn-modern-primary { background: var(--rl-primary); color: white; }
  .btn-modern-primary:hover { background: var(--rl-primary-hover); }
  .btn-modern-outline { background: var(--rl-surface); border-color: var(--rl-border); color: var(--rl-text); }
  .btn-modern-outline:hover { background: var(--rl-bg); border-color: var(--rl-muted); }
  .btn-modern-danger { background: #fee2e2; color: #991b1b; }
  .btn-modern-danger:hover { background: #fca5a5; }
  .btn-modern-amber { background: #fef3c7; color: #92400e; }
  .btn-modern-amber:hover { background: #fde68a; }

  .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
  
  .metric-card-sm {
    background: var(--rl-surface); border-radius: var(--rl-radius-md); padding: 16px;
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); display: flex; align-items: center; gap: 12px;
  }
  .metric-value-sm { font-size: 22px; font-weight: 700; color: var(--rl-text); margin-top: 2px; }

  .status-dot-lg { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
  .dot-dispo { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
  .dot-course { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }
  .dot-offline { background: #94a3b8; }
  .dot-suspended { background: #ef4444; }

  .modern-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg);
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); overflow: hidden;
  }

  .modern-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  .modern-table th { background: #f8fafc; padding: 14px 16px; color: var(--rl-muted); font-weight: 600; border-bottom: 1px solid var(--rl-border); }
  .modern-table td { padding: 14px 16px; border-bottom: 1px solid var(--rl-border); vertical-align: middle; }
  .modern-table tbody tr:hover { background: #f8fafc; }

  .driver-avatar {
    width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; color: #475569;
    display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
  }

  .badge-modern { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
  .badge-gray { background: #f1f5f9; color: #475569; }
  .badge-green { background: #dcfce7; color: #166534; }
  .badge-red { background: #fee2e2; color: #991b1b; }

  .modern-modal {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(4px);
    display: flex; align-items: flex-start; justify-content: center; z-index: 1000; padding: 16px;
    overflow-y: auto;
  }
  .modal-content {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); width: 100%; max-width: 500px;
    max-height: calc(100vh - 32px); margin: auto 0;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); border: 1px solid var(--rl-border); overflow: hidden;
    display: flex; flex-direction: column;
    animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .modal-content > div:nth-child(2) { overflow-y: auto; }
  .modal-content > div:first-child,
  .modal-content > div:last-child { flex-shrink: 0; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
  .form-group { margin-bottom: 14px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--rl-text); }
  .form-label.required::after { content: " *"; color: var(--rl-primary); }
  .input-modern {
    width: 100%; background: var(--rl-bg); border: 1px solid var(--rl-border); padding: 8px 12px;
    border-radius: var(--rl-radius-md); font-size: 14px; outline: none; transition: border 0.2s; box-sizing: border-box;
  }
  .input-modern:focus { border-color: var(--rl-primary); }
  .form-hint { font-size: 11px; color: var(--rl-muted); margin-top: 4px; }
  .v-align { display: inline-flex; align-items: center; gap: 4px; vertical-align: middle; }

  @keyframes modalSlide { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  @media (max-width: 640px) {
    .modern-header { align-items: flex-start; flex-direction: column; }
    .form-row { grid-template-columns: 1fr; gap: 0; margin-bottom: 0; }
    .modal-content { max-height: calc(100vh - 16px); }
  }
</style>

<div class="modern-header">
  <div>
    <div class="modern-title">Gestion des livreurs</div>
    <div class="modern-subtitle"><?= $stats['total'] ?> coursier<?= $stats['total']>1?'s':'' ?> configuré<?= $stats['total']>1?'s':'' ?></div>
  </div>
  <button class="btn-modern btn-modern-primary" onclick="document.getElementById('modal-add-liv').style.display='flex'">
    <span class="material-symbols-outlined" style="font-size:18px">person_add</span> Ajouter un livreur
  </button>
</div>

<div class="grid-stats">
  <div class="metric-card-sm">
    <span class="status-dot-lg dot-dispo"></span>
    <div><div class="form-label" style="margin:0">Disponibles</div><div class="metric-value-sm" style="color:#22c55e"><?= $stats['disponible'] ?></div></div>
  </div>
  <div class="metric-card-sm">
    <span class="status-dot-lg dot-course"></span>
    <div><div class="form-label" style="margin:0">En course</div><div class="metric-value-sm" style="color:#f59e0b"><?= $stats['en_course'] ?></div></div>
  </div>
  <div class="metric-card-sm">
    <span class="status-dot-lg dot-offline"></span>
    <div><div class="form-label" style="margin:0">Hors ligne</div><div class="metric-value-sm"><?= $stats['hors_ligne'] ?></div></div>
  </div>
  <div class="metric-card-sm">
    <span class="status-dot-lg dot-suspended"></span>
    <div><div class="form-label" style="margin:0">Suspendus</div><div class="metric-value-sm" style="color:#ef4444"><?= $stats['suspendu'] ?></div></div>
  </div>
</div>

<div class="modern-card">
  <div style="overflow-x:auto">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Livreur</th>
          <th>Contact</th>
          <th>Zone</th>
          <th>Véhicule</th>
          <th>Missions</th>
          <th>Note</th>
          <th>Documents</th>
          <th>Statut</th>
          <th>Inscrit le</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($livreurs as $l): ?>
      <tr style="<?= $l['statut']==='suspendu' ? 'background:#fff5f5; opacity: 0.75' : '' ?>">
        <td>
          <div style="display:flex; align-items:center; gap:10px">
            <div class="driver-avatar" style="overflow:hidden">
              <?php if ($l['photo_url']): ?>
                <img src="<?= sanitize($l['photo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
                <?= strtoupper(substr($l['prenom'],0,1).substr($l['nom'],0,1)) ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight:600; color:var(--rl-text)"><?= sanitize($l['prenom'].' '.$l['nom']) ?></div>
              <div style="font-size:12px; color:var(--rl-muted)"><?= sanitize($l['email']) ?></div>
            </div>
          </div>
        </td>

        <td style="font-weight: 500; font-size:13px"><?= sanitize($l['telephone']) ?></td>
        <td><span style="font-size:13px; font-weight:500"><?= sanitize($l['zone_nom'] ?? '—') ?></span></td>
        <td>
          <span class="badge-modern badge-gray">
            <span class="material-symbols-outlined" style="font-size:16px;">
              <?= match($l['type_vehicule']) { 'Moto'=>'two_wheeler', 'Vélo'=>'pedal_bike', 'Voiture'=>'directions_car', default=>'box' } ?>
            </span>
            <?= sanitize($l['type_vehicule']) ?>
          </span>
        </td>

        <td>
          <div style="font-weight:600"><?= $l['nb_livraisons_done'] ?> fait<?= $l['nb_livraisons_done']>1?'s':'' ?></div>
          <?php if ($l['missions_actives'] > 0): ?>
            <div style="font-size:11px; color:#d97706; font-weight:600; margin-top:2px">⏳ <?= $l['missions_actives'] ?> en cours</div>
          <?php endif; ?>
        </td>

        <td>
          <div style="display:flex; align-items:center; gap:4px; font-weight:600">
            <span class="material-symbols-outlined" style="font-size:16px; color:#f59e0b">star</span><?= number_format($l['note_moyenne'],1) ?>
          </div>
          <div style="font-size:11px; color:var(--rl-muted); margin-top:2px"><?= $l['nb_evaluations'] ?> avis</div>
        </td>

        <td>
          <?php if ($l['documents_statut']==='valides'): ?>
            <span class="badge-modern badge-green"><span class="material-symbols-outlined" style="font-size:14px">verified</span> Validés</span>
          <?php elseif ($l['documents_statut']==='rejetes'): ?>
            <span class="badge-modern badge-red" title="<?= sanitize($l['documents_motif'] ?? '') ?>">Rejetés</span>
          <?php else: ?>
            <span class="badge-modern badge-gray">En attente</span>
          <?php endif; ?>
          <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:5px">
            <?php if ($l['photo_url']): ?>
            <a href="<?= sanitize($l['photo_url']) ?>" target="_blank" class="btn-modern btn-modern-outline" style="padding:2px 7px;font-size:11px">Photo</a>
            <?php endif; ?>
            <?php if ($l['carte_identite_fichier']): ?>
            <a href="/rapidliv-php/api/api.php?action=document_identite_livreur&id=<?= urlencode($l['id']) ?>" target="_blank" class="btn-modern btn-modern-outline" style="padding:2px 7px;font-size:11px">Identité</a>
            <?php endif; ?>
            <?php if ($l['photo_url'] && $l['carte_identite_fichier'] && $l['documents_statut']!=='valides'): ?>
            <button class="btn-modern btn-modern-primary" style="padding:2px 7px;font-size:11px" onclick="validerDocuments('<?= $l['id'] ?>','<?= sanitize($l['prenom'].' '.$l['nom']) ?>')">Valider</button>
            <button class="btn-modern btn-modern-danger" style="padding:2px 7px;font-size:11px" onclick="rejeterDocuments('<?= $l['id'] ?>','<?= sanitize($l['prenom'].' '.$l['nom']) ?>')">Rejeter</button>
            <?php endif; ?>
          </div>
        </td>

        <td>
          <div style="display:flex; align-items:center; gap:6px">
            <span class="status-dot-lg <?= match($l['statut']) { 'disponible'=>'dot-dispo','en_course'=>'dot-course','suspendu'=>'dot-suspended', default=>'dot-offline' } ?>"></span>
            <span style="font-size:13px; font-weight:500"><?= statutBadgeHTML($l['statut']) ?></span>
          </div>
        </td>

        <td style="font-size:12px; color:var(--rl-muted); white-space:nowrap"><?= date('d/m/Y', strtotime($l['cree_le'])) ?></td>

        <td style="text-align:right">
          <div style="display:inline-flex; gap:4px">
            <button class="btn-modern btn-modern-outline" style="padding:6px; min-width:32px" title="Modifier le profil" onclick="ouvrirModifLivreur(<?= htmlspecialchars(json_encode($l), ENT_QUOTES) ?>)">
              <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
            </button>

            <?php if ($l['statut'] !== 'suspendu'): ?>
              <button class="btn-modern btn-modern-amber" style="padding:6px; min-width:32px" title="Suspendre le compte" onclick="suspendrelivreur('<?= $l['id'] ?>', '<?= sanitize($l['prenom'].' '.$l['nom']) ?>')">
                <span class="material-symbols-outlined" style="font-size:16px;">pause</span>
              </button>
            <?php else: ?>
              <button class="btn-modern btn-modern-primary" style="padding:6px; min-width:32px" title="Réactiver" onclick="reactriverLivreur('<?= $l['id'] ?>', '<?= sanitize($l['prenom'].' '.$l['nom']) ?>')">
                <span class="material-symbols-outlined" style="font-size:16px;">play_arrow</span>
              </button>
            <?php endif; ?>

            <button class="btn-modern btn-modern-danger" style="padding:6px; min-width:32px" title="Supprimer définitivement" onclick="supprimerLivreur('<?= $l['id'] ?>', '<?= sanitize($l['prenom'].' '.$l['nom']) ?>', <?= $l['nb_livraisons_done'] ?>, <?= $l['missions_actives'] ?>)">
              <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>

      <?php if (!$livreurs): ?>
        <tr><td colspan="10" style="text-align:center; color:var(--rl-muted); padding:40px">Aucun coursier enregistré.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modals identiques au précédent mais avec icône Google Fonts de fermeture 'close' -->
<div id="modal-add-liv" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-add-liv')">
  <div class="modal-content">
    <div style="padding:18px 20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700">Nouveau profil livreur</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-add-liv')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <!-- ... Rest of modal-body ... -->
    <div style="padding:20px">
      <div class="form-row">
        <div class="form-group"><label class="form-label required">Prénom</label><input class="input-modern" id="nl-prenom" placeholder="Ibrahima"></div>
        <div class="form-group"><label class="form-label required">Nom</label><input class="input-modern" id="nl-nom" placeholder="Diallo"></div>
      </div>
      <div class="form-group"><label class="form-label required">Adresse Email</label><input type="email" class="input-modern" id="nl-email" placeholder="livreur@rapidliv.sn"></div>
      <div class="form-group"><label class="form-label required">Téléphone</label><input class="input-modern" id="nl-tel" placeholder="+221 77 xxx xx xx"></div>
      <div class="form-group">
        <label class="form-label required">Mot de passe temporaire</label>
        <input type="password" class="input-modern" id="nl-mdp" placeholder="6 caractères minimum">
        <div class="form-hint">Le livreur devra modifier ce mot de passe dès sa première connexion.</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Zone par défaut</label>
          <select class="input-modern" id="nl-zone">
            <option value="">-- Choisir --</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= sanitize($z['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Type de Véhicule</label>
          <select class="input-modern" id="nl-vehicule">
            <option value="Moto">🏍️ Moto</option>
            <option value="Vélo">🚲 Vélo</option>
            <option value="Voiture">🚗 Voiture</option>
          </select>
        </div>
      </div>
    </div>
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-add-liv')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="creerLivreur()">Créer le compte</button>
    </div>
  </div>
</div>

<div id="modal-edit-liv" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-edit-liv')">
  <div class="modal-content">
    <div style="padding:18px 20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700">Modifier la fiche coursier</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-edit-liv')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <!-- ... Rest of modal body ... -->
    <div style="padding:20px">
      <input type="hidden" id="el-id">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Prénom</label><input class="input-modern" id="el-prenom"></div>
        <div class="form-group"><label class="form-label">Nom</label><input class="input-modern" id="el-nom"></div>
      </div>
      <div class="form-group"><label class="form-label">Numéro de Téléphone</label><input class="input-modern" id="el-tel"></div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Zone</label>
          <select class="input-modern" id="el-zone">
            <option value="">-- Choisir --</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= sanitize($z['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Véhicule</label>
          <select class="input-modern" id="el-vehicule">
            <option value="Moto">🏍️ Moto</option>
            <option value="Vélo">🚲 Vélo</option>
            <option value="Voiture">🚗 Voiture</option>
          </select>
        </div>
      </div>
    </div>
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-edit-liv')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="sauverModifLivreur()">Enregistrer</button>
    </div>
  </div>
</div>

<div id="modal-suppr-liv" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-suppr-liv')">
  <div class="modal-content" style="max-width:440px">
    <div style="padding:18px 20px; border-bottom:1px solid #fee2e2; display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:#991b1b">⚠️ Destruction de compte</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-suppr-liv')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <!-- ... Rest of modal body ... -->
    <div style="padding:20px">
      <input type="hidden" id="suppr-liv-id">
      <div style="background:#fff5f5; color:#991b1b; padding:12px; border-radius:6px; font-size:13px; line-height:1.5; margin-bottom:14px">
        Vous êtes sur le point de purger définitivement le profil de <strong id="suppr-liv-nom"></strong>. Cette action détruira ses accès de manière <strong>irréversible</strong>.
      </div>
      <div id="suppr-liv-warn-missions" style="display:none; background:#fffbeb; color:#b45309; padding:12px; border-radius:6px; font-size:13px; margin-bottom:14px; font-weight:600">
        🚨 Alerte : Ce coursier possède des livraisons actives ! Transférez ses tâches avant finalisation.
      </div>
      <div id="suppr-liv-info-livr" style="background:#f8fafc; padding:10px; border-radius:6px; font-size:12px; border:1px solid var(--rl-border); margin-bottom:14px"></div>
      <div class="form-group">
        <label class="form-label">Pour confirmer, écrivez <strong>SUPPRIMER</strong> :</label>
        <input type="text" class="input-modern" id="suppr-liv-confirm" placeholder="SUPPRIMER" style="font-weight:700; letter-spacing:1px; text-align:center">
      </div>
    </div>
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-suppr-liv')">Annuler</button>
      <button class="btn-modern btn-modern-primary" style="background:#ef4444" onclick="confirmerSuppressionLivreur()">Détruire le profil</button>
    </div>
  </div>
</div>

<script>
function fermerModal(id) { document.getElementById(id).style.display = 'none'; }

function creerLivreur() {
  const data = {
    prenom: document.getElementById('nl-prenom').value.trim(),
    nom: document.getElementById('nl-nom').value.trim(),
    email: document.getElementById('nl-email').value.trim(),
    telephone: document.getElementById('nl-tel').value.trim(),
    mot_de_passe: document.getElementById('nl-mdp').value,
    zone_id: document.getElementById('nl-zone').value,
    type_vehicule: document.getElementById('nl-vehicule').value,
  };

  if (!data.prenom || !data.nom || !data.email || !data.telephone || !data.mot_de_passe) {
    flash('Veuillez remplir tous les champs obligatoires', 'error');
    return;
  }

  api('creer_livreur', data)
    .then(() => {
      flash('✅ Livreur créé avec succès', 'success');
      fermerModal('modal-add-liv');
      setTimeout(() => location.reload(), 900);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function ouvrirModifLivreur(l) {
  document.getElementById('el-id').value = l.id;
  document.getElementById('el-prenom').value = l.prenom || '';
  document.getElementById('el-nom').value = l.nom || '';
  document.getElementById('el-tel').value = l.telephone || '';
  document.getElementById('el-zone').value = l.zone_id || '';
  document.getElementById('el-vehicule').value = l.type_vehicule || 'Moto';
  document.getElementById('modal-edit-liv').style.display = 'flex';
}

function sauverModifLivreur() {
  api('modifier_livreur', {
    id: document.getElementById('el-id').value,
    prenom: document.getElementById('el-prenom').value.trim(),
    nom: document.getElementById('el-nom').value.trim(),
    telephone: document.getElementById('el-tel').value.trim(),
    zone_id: document.getElementById('el-zone').value,
    type_vehicule: document.getElementById('el-vehicule').value,
  })
    .then(() => {
      flash('✅ Livreur modifié', 'success');
      fermerModal('modal-edit-liv');
      setTimeout(() => location.reload(), 900);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function changerStatutLivreur(id, statut, message) {
  api('changer_statut_livreur_admin', { id, statut })
    .then(() => {
      flash(message, 'success');
      setTimeout(() => location.reload(), 800);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function suspendrelivreur(id, nom) {
  if (!confirm(`Suspendre le compte de ${nom} ?`)) return;
  changerStatutLivreur(id, 'suspendu', '⏸ Livreur suspendu');
}

function reactriverLivreur(id, nom) {
  if (!confirm(`Réactiver le compte de ${nom} ?`)) return;
  changerStatutLivreur(id, 'hors_ligne', '✅ Livreur réactivé');
}

function validerDocuments(id, nom) {
  if (!confirm(`Vous avez vérifié la photo et la carte d’identité de ${nom} ?`)) return;
  api('valider_documents_livreur', { id })
    .then(() => {
      flash('✅ Documents validés', 'success');
      setTimeout(() => location.reload(), 800);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function rejeterDocuments(id, nom) {
  const motif = prompt(`Motif du rejet des documents de ${nom} :`);
  if (motif === null) return;
  if (!motif.trim()) {
    flash('Le motif du rejet est obligatoire', 'error');
    return;
  }
  api('rejeter_documents_livreur', { id, motif: motif.trim() })
    .then(() => {
      flash('Documents rejetés', 'success');
      setTimeout(() => location.reload(), 800);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function supprimerLivreur(id, nom, nbLivraisons, missionsActives) {
  document.getElementById('suppr-liv-id').value = id;
  document.getElementById('suppr-liv-nom').textContent = nom;
  document.getElementById('suppr-liv-confirm').value = '';
  document.getElementById('suppr-liv-warn-missions').style.display = missionsActives > 0 ? 'block' : 'none';
  document.getElementById('suppr-liv-info-livr').textContent = `${nbLivraisons} livraison(s) terminée(s), ${missionsActives} mission(s) active(s).`;
  document.getElementById('modal-suppr-liv').style.display = 'flex';
}

function confirmerSuppressionLivreur() {
  const confirmation = document.getElementById('suppr-liv-confirm').value.trim();
  if (confirmation !== 'SUPPRIMER') {
    flash('Veuillez écrire précisément SUPPRIMER', 'error');
    return;
  }

  api('supprimer_livreur', { id: document.getElementById('suppr-liv-id').value })
    .then(() => {
      flash('✅ Livreur supprimé', 'success');
      fermerModal('modal-suppr-liv');
      setTimeout(() => location.reload(), 900);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}
</script>
