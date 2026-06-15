<?php
// ============================================================
//  RAPIDLIV — Admin : Tableau de bord (VERSION MODERNE + GOOGLE ICONS)
//  Fichier : pages/admin/dashboard.php
// ============================================================

$stats_jour = Database::queryOne(
    "SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS ca,
            SUM(statut='livree') AS livrees,
            SUM(statut='en_attente') AS en_attente,
            SUM(statut='annulee') AS annulees
     FROM commandes WHERE DATE(cree_le)=CURDATE()");
$stats_mois = Database::queryOne(
    "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS ca
     FROM commandes WHERE MONTH(cree_le)=MONTH(NOW()) AND YEAR(cree_le)=YEAR(NOW())");
$livreurs_statut = Database::query("SELECT statut, COUNT(*) AS nb FROM livreurs GROUP BY statut");
$top_services    = Database::query(
    "SELECT s.nom, cs.icone, COUNT(c.id) AS commandes, COALESCE(SUM(c.total),0) AS ca, s.note_moyenne
     FROM services s JOIN categories_service cs ON cs.id=s.categorie_id
     LEFT JOIN commandes c ON c.service_id=s.id AND c.statut='livree'
     GROUP BY s.id ORDER BY commandes DESC LIMIT 5");
$commandes_recentes = Database::query(
    "SELECT c.reference, c.statut, c.total, c.cree_le,
            CONCAT(u.prenom,' ',u.nom) AS client_nom, s.nom AS service_nom
     FROM commandes c JOIN utilisateurs u ON u.id=c.client_id JOIN services s ON s.id=c.service_id
     ORDER BY c.cree_le DESC LIMIT 8");
$livreurs_actifs = Database::query(
    "SELECT l.statut, CONCAT(u.prenom,' ',u.nom) AS nom, l.type_vehicule, l.note_moyenne, z.nom AS zone
     FROM livreurs l JOIN utilisateurs u ON u.id=l.id LEFT JOIN zones z ON z.id=l.zone_id
     ORDER BY l.statut DESC, u.nom LIMIT 8");

$evolution = Database::query(
    "SELECT DATE(cree_le) AS jour, COUNT(*) AS total,
            SUM(statut='livree') AS livrees
     FROM commandes WHERE cree_le >= NOW() - INTERVAL 7 DAY
     GROUP BY DATE(cree_le) ORDER BY jour");
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
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; 
    font-size: 14px; font-weight: 500; border-radius: var(--rl-radius-md); 
    transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent;
    text-decoration: none;
  }
  .btn-modern-outline { background: var(--rl-surface); border-color: var(--rl-border); color: var(--rl-text); }
  .btn-modern-outline:hover { background: var(--rl-bg); border-color: var(--rl-muted); }

  .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px; }
  .grid-sections { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; margin-bottom: 24px; }
  @media (max-width: 992px) { .grid-sections { grid-template-columns: 1fr; } }

  .metric-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); padding: 20px;
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border);
  }
  .metric-label { font-size: 13px; font-weight: 600; color: var(--rl-muted); text-transform: uppercase; letter-spacing: 0.5px; }
  .metric-value { font-size: 28px; font-weight: 700; color: var(--rl-text); margin: 8px 0 4px 0; letter-spacing: -1px; }
  .metric-sub { font-size: 12px; color: var(--rl-muted); display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
  .metric-up { color: #166534; font-weight: 600; }

  .modern-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg);
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); overflow: hidden; padding: 20px;
  }
  .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .section-title-modern { font-size: 16px; font-weight: 700; color: var(--rl-text); margin: 0; }

  .modern-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  .modern-table th { background: #f8fafc; padding: 12px 14px; color: var(--rl-muted); font-weight: 600; border-bottom: 1px solid var(--rl-border); }
  .modern-table td { padding: 12px 14px; border-bottom: 1px solid var(--rl-border); vertical-align: middle; }
  .modern-table tbody tr:hover { background: #f8fafc; }

  .service-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--rl-border); }
  .service-item:last-child { border-bottom: none; padding-bottom: 0; }

  .grid-drivers { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
  .driver-card {
    background: var(--rl-surface); border: 1px solid var(--rl-border); border-radius: var(--rl-radius-md);
    padding: 14px; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.2s;
  }
  .driver-card:hover { box-shadow: var(--rl-shadow); }
  .driver-avatar {
    width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; color: #475569;
    display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
  }
  .status-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
  .status-disponible { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
  .status-en_course { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }
  .status-indisponible { background: #94a3b8; }
  
  .v-align { display: inline-flex; align-items: center; gap: 4px; vertical-align: middle; }
</style>

<div class="modern-header">
  <div>
    <div class="modern-title">Tableau de bord</div>
    <div class="modern-subtitle">Vue d'ensemble de l'activité commerciale — <?= date('d/m/Y') ?></div>
  </div>
  <button class="btn-modern btn-modern-outline" onclick="location.reload()">
    <span class="material-symbols-outlined" style="font-size: 18px;">refresh</span> Actualiser
  </button>
</div>

<div class="grid-stats">
  <div class="metric-card">
    <div class="metric-label">Commandes aujourd'hui</div>
    <div class="metric-value"><?= $stats_jour['total'] ?></div>
    <div class="metric-sub">
      <span class="metric-up v-align"><span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> <?= $stats_jour['livrees'] ?> livrées</span> 
      <span style="color:var(--rl-muted)">· <?= $stats_jour['en_attente'] ?> en attente</span>
    </div>
  </div>
  
  <div class="metric-card">
    <div class="metric-label">Chiffre d'Affaires (Jour)</div>
    <div class="metric-value" style="color: var(--rl-primary);"><?= formatMontant((int)$stats_jour['ca']) ?></div>
    <div class="metric-sub">Mensuel cumulé : <strong><?= formatMontant((int)$stats_mois['ca']) ?></strong></div>
  </div>
  
  <div class="metric-card">
    <div class="metric-label">Livreurs opérationnels</div>
    <div class="metric-value">
      <?= array_sum(array_column(array_filter($livreurs_statut, fn($r)=>in_array($r['statut'],['disponible','en_course'])), 'nb')) ?>
    </div>
    <div class="metric-sub">
      <?php foreach ($livreurs_statut as $ls): ?>
        <span style="margin-right: 6px;">
          <?= ucfirst(str_replace('_',' ',$ls['statut'])) ?>: <strong><?= $ls['nb'] ?></strong>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div class="metric-card">
    <div class="metric-label">Taux de conversion</div>
    <div class="metric-value">
      <?= $stats_jour['total'] > 0 ? round($stats_jour['livrees']/$stats_jour['total']*100) : 0 ?>%
    </div>
    <div class="metric-sub">Flux d'échecs / Annulations : <?= $stats_jour['annulees'] ?></div>
  </div>
</div>

<div class="grid-sections">
  <div class="modern-card">
    <div class="card-header-flex">
      <h3 class="section-title-modern">Commandes récentes</h3>
      <a href="index.php?page=admin_commandes" class="btn-modern btn-modern-outline" style="padding: 6px 12px; font-size: 13px;">Voir tout</a>
    </div>
    <div style="overflow-x:auto">
      <table class="modern-table">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Client</th>
            <th>Total</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($commandes_recentes as $c): ?>
        <tr>
          <td><span style="font-family:monospace; font-weight:600; color:#1e293b; background:#f1f5f9; padding:3px 6px; border-radius:4px; font-size:12px"><?= sanitize($c['reference']) ?></span></td>
          <td><div style="font-weight: 500;"><?= sanitize($c['client_nom']) ?></div></td>
          <td><div style="font-weight: 600; color:var(--rl-text)"><?= formatMontant($c['total']) ?></div></td>
          <td><?= statutBadgeHTML($c['statut']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modern-card">
    <div class="card-header-flex">
      <h3 class="section-title-modern">Top services</h3>
    </div>
    <div style="display: flex; flex-direction: column;">
      <?php foreach ($top_services as $s): ?>
      <div class="service-item">
        <div style="display:flex; align-items:center; gap:10px">
          <span class="material-symbols-outlined" style="font-size:24px; background:#f8fafc; padding:6px; border-radius:8px; border:1px solid var(--rl-border); color:var(--rl-primary)"><?= $s['icone'] ?: 'package_2' ?></span>
          <div>
            <div style="font-weight:600; font-size:14px; color:var(--rl-text)"><?= sanitize($s['nom']) ?></div>
            <div style="font-size:12px; color:var(--rl-muted); margin-top:2px; display:flex; align-items:center; gap:4px">
              <strong><?= $s['commandes'] ?></strong> commandes · <span class="material-symbols-outlined" style="font-size:14px; color:#f59e0b">star</span> <?= number_format($s['note_moyenne'], 1) ?>
            </div>
          </div>
        </div>
        <div style="font-weight:700; font-size:14px; color:var(--rl-text)"><?= formatMontant((int)$s['ca']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="modern-card">
  <div class="card-header-flex">
    <h3 class="section-title-modern">État de la flotte livreurs</h3>
    <a href="index.php?page=admin_livreurs" class="btn-modern btn-modern-outline" style="padding: 6px 12px; font-size: 13px;">Gérer les coursiers</a>
  </div>
  
  <div class="grid-drivers">
    <?php foreach ($livreurs_actifs as $l): ?>
    <div class="driver-card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px">
        <div style="display:flex; gap:10px; align-items:center">
          <div class="driver-avatar"><?= strtoupper(substr($l['nom'], 0, 2)) ?></div>
          <div>
            <div style="font-weight:600; font-size:13px; color:var(--rl-text)"><?= sanitize($l['nom']) ?></div>
            <div style="font-size:11px; color:var(--rl-muted); margin-top:1px;">
              <?= sanitize($l['zone'] ?? 'Zone indéfinie') ?>
            </div>
          </div>
        </div>
        <span class="status-indicator status-<?= $l['statut'] === 'disponible' ? 'disponible' : ($l['statut'] === 'en_course' ? 'en_course' : 'indisponible') ?>" title="Statut : <?= ucfirst($l['statut']) ?>"></span>
      </div>
      
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; font-size:11px; color:var(--rl-muted); background:#f8fafc; padding:4px 8px; border-radius:4px">
        <span class="v-align">
          <span class="material-symbols-outlined" style="font-size:14px; color:var(--rl-muted)">
            <?= match($l['type_vehicule']) { 'Moto'=>'two_wheeler', 'Vélo'=>'pedal_bike', 'Voiture'=>'directions_car', default=>'box' } ?>
          </span> 
          <?= $l['type_vehicule'] ?>
        </span>
        <span class="v-align" style="font-weight:600; color:var(--rl-text)"><span class="material-symbols-outlined" style="font-size:14px; color:#f59e0b">star</span> <?= number_format($l['note_moyenne'], 1) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>