<?php // pages/admin/parametres.php
$params = Database::query("SELECT * FROM parametres");
$params_map = array_column($params, 'valeur', 'cle');
?>

<!-- Styles UI Modernisés et Alignés -->
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
  
  .modern-title { font-size: 24px; font-weight: 700; color: var(--rl-text); letter-spacing: -0.5px; margin-bottom: 24px; }

  .btn-modern {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; 
    font-size: 14px; font-weight: 500; border-radius: var(--rl-radius-md); 
    transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent; text-decoration: none;
  }
  .btn-modern-primary { background: var(--rl-primary); color: white; width: 100%; margin-top: 8px; }
  .btn-modern-primary:hover { background: var(--rl-primary-hover); }

  /* Structures Grilles & Cartes */
  .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }
  
  .modern-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg);
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); padding: 20px; overflow: hidden;
  }
  .section-title-modern { font-size: 16px; font-weight: 700; color: var(--rl-text); margin: 0 0 16px 0; border-bottom: 1px solid var(--rl-border); padding-bottom: 8px; }
  .mb-16 { margin-bottom: 16px; }

  /* Formulaires */
  .form-group { margin-bottom: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--rl-text); }
  .input-modern {
    width: 100%; background: var(--rl-bg); border: 1px solid var(--rl-border); padding: 10px 12px;
    border-radius: var(--rl-radius-md); font-size: 14px; outline: none; transition: border 0.2s; box-sizing: border-box;
    color: var(--rl-text);
  }
  .input-modern:focus { border-color: var(--rl-primary); }

  /* Lignes de paiement */
  .payment-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--rl-border); }
  .payment-row:last-child { border-bottom: none; padding-bottom: 0; }

  /* Tableaux */
  .modern-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  .modern-table th { background: #f8fafc; padding: 10px 12px; color: var(--rl-muted); font-weight: 600; border-bottom: 1px solid var(--rl-border); font-size: 12px; }
  .modern-table td { padding: 10px 12px; border-bottom: 1px solid var(--rl-border); vertical-align: middle; }
  .modern-table tr:last-child td { border-bottom: none; }

  /* Badges */
  .badge-modern { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
  .badge-green { background: #dcfce7; color: #166534; }
  .badge-red { background: #fee2e2; color: #991b1b; }

  /* Mini stats */
  .grid-stats-mini { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .metric-card-sm {
    background: var(--rl-bg); border-radius: var(--rl-radius-md); padding: 12px;
    border: 1px solid var(--rl-border);
  }
  .metric-label-sm { font-size: 12px; font-weight: 600; color: var(--rl-muted); text-transform: uppercase; letter-spacing: 0.5px; }
  .metric-value-sm { font-size: 20px; font-weight: 700; color: var(--rl-text); margin-top: 4px; }
</style>

<div class="modern-title">Paramètres système</div>

<div class="grid-layout">
  <!-- Colonne Gauche : Configuration générale -->
  <div class="modern-card">
    <h3 class="section-title-modern">Configuration générale</h3>
    
    <div class="form-group">
      <label class="form-label">Frais de livraison de base (FCFA)</label>
      <input type="number" class="input-modern" id="frais_livraison" value="<?= (int)($params_map['frais_livraison']??1500) ?>">
    </div>
    
    <div class="form-group">
      <label class="form-label">Commission livreurs (%)</label>
      <input type="number" class="input-modern" id="commission" value="<?= (int)($params_map['commission']??10) ?>" min="1" max="50">
    </div>
    
    <div class="form-group">
      <label class="form-label">Rayon de livraison max (km)</label>
      <input type="number" class="input-modern" id="rayon_max" value="<?= (int)($params_map['rayon_max']??15) ?>">
    </div>
    
    <div class="form-group" style="margin-top: 20px; margin-bottom: 24px;">
      <label style="display:flex; align-items:center; gap:12px; cursor:pointer; margin-bottom:14px">
        <input type="checkbox" id="auto_assign_livreur" <?= ($params_map['auto_assign_livreur']??'1')==='1'?'checked':'' ?> style="accent-color:var(--rl-primary); width:18px; height:18px; cursor:pointer">
        <div>
          <span style="font-size:14px; font-weight:600; display:block; color:var(--rl-text)">Assignation automatique des livreurs</span>
          <span style="font-size:12px; color:var(--rl-muted); display:block; margin-top:2px">Attribuer automatiquement chaque nouvelle commande au meilleur livreur disponible.</span>
        </div>
      </label>
      <label style="display:flex; align-items:center; gap:12px; cursor:pointer">
        <input type="checkbox" id="maintenance" <?= ($params_map['maintenance']??'0')==='1'?'checked':'' ?> style="accent-color:var(--rl-primary); width:18px; height:18px; cursor:pointer">
        <div>
          <span style="font-size:14px; font-weight:600; display:block; color:var(--rl-text)">Mode maintenance</span>
          <span style="font-size:12px; color:var(--rl-muted); display:block; margin-top:2px">Rendre l'application temporairement inaccessible aux clients</span>
        </div>
      </label>
    </div>
    
    <button class="btn-modern btn-modern-primary" onclick="sauverParams()">💾 Enregistrer les modifications</button>
    <button class="btn-modern btn-modern-outline" style="width:100%; margin-top:10px" onclick="assignerCommandesAuto()">
      <span class="material-symbols-outlined" style="font-size:18px">auto_mode</span>
      Assigner maintenant les commandes en attente
    </button>
  </div>

  <!-- Colonne Droite : Paiements, Zones & Stats -->
  <div>
    <!-- Méthodes de paiement -->
    <div class="modern-card mb-16">
      <h3 class="section-title-modern">Méthodes de paiement acceptées</h3>
      <?php foreach ([['orange_money','📱 Orange Money'],['wave','🌊 Wave'],['cash','💵 Cash à la livraison'],['carte','💳 Carte bancaire'],['free_money','📲 Free Money']] as [$val,$label]): ?>
      <div class="payment-row">
        <span style="font-size:14px; font-weight:500; color:var(--rl-text)"><?= $label ?></span>
        <label style="cursor:pointer; display:flex; align-items:center;">
          <input type="checkbox" checked style="accent-color:var(--rl-primary); width:18px; height:18px; cursor:pointer">
        </label>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Zones de livraison -->
    <div class="modern-card mb-16">
      <h3 class="section-title-modern">Zones de tarification active</h3>
      <?php $zones = Database::query("SELECT * FROM zones ORDER BY nom"); ?>
      <div style="overflow-x:auto">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Zone</th>
              <th>Frais (FCFA)</th>
              <th>Rayon</th>
              <th style="text-align:right">Statut</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($zones as $z): ?>
          <tr>
            <td style="font-weight:600; color:var(--rl-text)"><?= sanitize($z['nom']) ?></td>
            <td style="font-weight:500"><?= number_format($z['frais_base']) ?></td>
            <td style="color:var(--rl-muted)"><?= $z['rayon_km'] ?> km</td>
            <td style="text-align:right">
              <?= $z['actif'] ? '<span class="badge-modern badge-green">Actif</span>':'<span class="badge-modern badge-red">Inactif</span>' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Statistiques système -->
    <div class="modern-card">
      <h3 class="section-title-modern">Métriques système</h3>
      <?php
      $nbUsers     = Database::queryOne("SELECT COUNT(*) AS nb FROM utilisateurs WHERE role='client'")['nb'];
      $nbLivreurs  = Database::queryOne("SELECT COUNT(*) AS nb FROM utilisateurs WHERE role='livreur'")['nb'];
      $nbServices  = Database::queryOne("SELECT COUNT(*) AS nb FROM services WHERE actif=1")['nb'];
      $nbProduits  = Database::queryOne("SELECT COUNT(*) AS nb FROM produits WHERE disponible=1")['nb'];
      ?>
      <div class="grid-stats-mini">
        <div class="metric-card-sm">
          <div class="metric-label-sm">Clients</div>
          <div class="metric-value-sm"><?= $nbUsers ?></div>
        </div>
        <div class="metric-card-sm">
          <div class="metric-label-sm">Livreurs</div>
          <div class="metric-value-sm"><?= $nbLivreurs ?></div>
        </div>
        <div class="metric-card-sm">
          <div class="metric-label-sm">Services Actifs</div>
          <div class="metric-value-sm"><?= $nbServices ?></div>
        </div>
        <div class="metric-card-sm">
          <div class="metric-label-sm">Produits Indexés</div>
          <div class="metric-value-sm"><?= $nbProduits ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function sauverParams() {
  const data = {
    frais_livraison: document.getElementById('frais_livraison').value,
    commission:      document.getElementById('commission').value,
    rayon_max:       document.getElementById('rayon_max').value,
    maintenance:     document.getElementById('maintenance').checked ? '1' : '0',
    auto_assign_livreur: document.getElementById('auto_assign_livreur').checked ? '1' : '0',
  };
  api('sauver_parametres', data)
    .then(() => flash('✅ Paramètres sauvegardés avec succès', 'success'))
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function assignerCommandesAuto() {
  api('auto_assigner_commandes', {})
    .then(d => {
      flash(`✅ ${d.message}`, 'success');
      setTimeout(() => location.reload(), 1200);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}
</script>
