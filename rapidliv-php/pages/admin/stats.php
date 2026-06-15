<?php // pages/admin/stats.php
// ============================================================
//  RAPIDLIV — Admin : Tableau de bord & Statistiques
//  Fichier : pages/admin/stats.php
// ============================================================

$stats_global = Database::queryOne("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS ca, SUM(statut='livree') AS livrees, SUM(statut='annulee') AS annulees, COUNT(DISTINCT client_id) AS clients FROM commandes");
$stats_mois = Database::queryOne("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS ca FROM commandes WHERE MONTH(cree_le)=MONTH(NOW()) AND YEAR(cree_le)=YEAR(NOW())");
$evolution = Database::query("SELECT DATE(cree_le) AS jour, COUNT(*) AS total, SUM(statut='livree') AS livrees, COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS ca FROM commandes WHERE cree_le >= NOW() - INTERVAL 14 DAY GROUP BY DATE(cree_le) ORDER BY jour");
$top_services = Database::query("SELECT s.nom, cs.icone, COUNT(c.id) AS commandes, COALESCE(SUM(c.total),0) AS ca, s.note_moyenne, s.nb_evaluations FROM services s JOIN categories_service cs ON cs.id=s.categorie_id LEFT JOIN commandes c ON c.service_id=s.id AND c.statut='livree' GROUP BY s.id ORDER BY commandes DESC LIMIT 8");
$top_livreurs = Database::query("SELECT CONCAT(u.prenom,' ',u.nom) AS nom, l.nb_livraisons_total, l.gains_total, l.note_moyenne FROM livreurs l JOIN utilisateurs u ON u.id=l.id ORDER BY l.nb_livraisons_total DESC LIMIT 5");
$par_categorie = Database::query("SELECT cs.nom, cs.icone, COUNT(c.id) AS nb FROM categories_service cs LEFT JOIN services s ON s.categorie_id=cs.id LEFT JOIN commandes c ON c.service_id=s.id AND c.statut='livree' GROUP BY cs.id ORDER BY nb DESC");
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<style>
  :root {
    --rl-primary: #0E8F6E;
    --rl-primary-light: #E5F6F0;
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
  
  /* En-tête */
  .modern-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .modern-title { font-size: 24px; font-weight: 700; color: var(--rl-text); letter-spacing: -0.5px; }

  /* Grille de Cartes Métriques */
  .grid-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
  .metric-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); padding: 20px;
    border: 1px solid var(--rl-border); box-shadow: var(--rl-shadow);
    display: flex; align-items: center; gap: 16px;
  }
  .metric-icon-box {
    background: var(--rl-primary-light); color: var(--rl-primary);
    padding: 12px; border-radius: var(--rl-radius-md); display: flex; align-items: center; justify-content: center;
  }
  .metric-info { display: flex; flex-direction: column; }
  .metric-label { font-size: 13px; font-weight: 500; color: var(--rl-muted); margin-bottom: 4px; }
  .metric-value { font-size: 22px; font-weight: 700; color: var(--rl-text); line-height: 1.1; }
  .metric-sub { font-size: 12px; font-weight: 600; color: var(--rl-primary); margin-top: 4px; }

  /* Structures des sections de graphiques */
  .grid-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 24px; }
  .dashboard-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); padding: 20px;
    border: 1px solid var(--rl-border); box-shadow: var(--rl-shadow); display: flex; flex-direction: column;
  }
  .card-title { font-size: 15px; font-weight: 700; color: var(--rl-text); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
  .card-title .material-symbols-outlined { color: var(--rl-muted); font-size: 18px; }

  /* Composant Histogramme Graphique */
  .chart-container { display: flex; align-items: flex-end; gap: 8px; height: 160px; padding-top: 10px; margin-bottom: 12px; }
  .chart-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; gap: 6px; }
  .chart-bar { width: 100%; background: var(--rl-primary); border-radius: 4px 4px 0 0; opacity: 0.85; transition: all 0.3s ease; min-height: 4px; }
  .chart-bar:hover { opacity: 1; filter: brightness(0.9); }
  .chart-val { font-size: 10px; font-weight: 600; color: var(--rl-text); }
  .chart-label { font-size: 10px; color: var(--rl-muted); white-space: nowrap; transform: translateY(4px); }

  /* Barres de Progression Modernes */
  .progress-wrap { margin-bottom: 14px; }
  .progress-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 13px; }
  .progress-bar-bg { background: var(--rl-bg); height: 8px; border-radius: 99px; overflow: hidden; border: 1px solid var(--rl-border); }
  .progress-bar-fill { background: var(--rl-primary); height: 100%; border-radius: 99px; transition: width 0.5s ease; }

  /* Tableaux épurés */
  .table-responsive { overflow-x: auto; margin: 0 -20px; }
  .modern-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
  .modern-table th { background: var(--rl-bg); padding: 10px 20px; font-weight: 600; color: var(--rl-muted); border-bottom: 1px solid var(--rl-border); }
  .modern-table td { padding: 12px 20px; border-bottom: 1px solid var(--rl-border); color: var(--rl-text); vertical-align: middle; }
  .modern-table tr:last-child td { border-bottom: none; }

  /* Lignes liste (Top livreurs) */
  .row-list-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px dashed var(--rl-border); }
  .row-list-item:last-child { border-bottom: none; padding-bottom: 0; }
  .rank-badge { width: 22px; font-weight: 700; color: var(--rl-muted); font-size: 14px; }
  .avatar-text { width: 36px; height: 36px; background: var(--rl-bg); border: 1px solid var(--rl-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--rl-text); }
  
  .star-span { display: inline-flex; align-items: center; gap: 2px; font-size: 12px; font-weight: 600; }
  .star-span .material-symbols-outlined { font-size: 13px; color: #f59e0b; }
</style>

<!-- En-tête -->
<div class="modern-header">
  <div>
    <div class="modern-title">Statistiques d'activité</div>
  </div>
</div>

<!-- Cartes Métriques Principales -->
<div class="grid-metrics">
  <div class="metric-card">
    <div class="metric-icon-box"><span class="material-symbols-outlined">analytics</span></div>
    <div class="metric-info">
      <span class="metric-label">Total commandes</span>
      <span class="metric-value"><?= number_format($stats_global['total']) ?></span>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon-box"><span class="material-symbols-outlined">payments</span></div>
    <div class="metric-info">
      <span class="metric-label">Chiffre d'Affaires</span>
      <span class="metric-value"><?= formatMontant($stats_global['ca']) ?></span>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon-box"><span class="material-symbols-outlined">calendar_month</span></div>
    <div class="metric-info">
      <span class="metric-label">Performance ce mois</span>
      <span class="metric-value"><?= number_format($stats_mois['total']) ?> cmd</span>
      <span class="metric-sub"><?= formatMontant($stats_mois['ca']) ?></span>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon-box"><span class="material-symbols-outlined">local_shipping</span></div>
    <div class="metric-info">
      <span class="metric-label">Taux de livraison</span>
      <span class="metric-value"><?= $stats_global['total'] > 0 ? round($stats_global['livrees'] / $stats_global['total'] * 100) : 0 ?>%</span>
    </div>
  </div>
</div>

<!-- Section Graphiques & Catégories -->
<div class="grid-dashboard">
  <!-- Évolution 14 jours -->
  <div class="dashboard-card">
    <div class="card-title">
      <span class="material-symbols-outlined">equalizer</span> Commandes — 14 derniers jours
    </div>
    <div class="chart-container">
      <?php
      $max_val = max(array_column($evolution, 'total') ?: [1]);
      foreach ($evolution as $e):
        $h = max(4, round($e['total'] / $max_val * 100));
      ?>
      <div class="chart-bar-wrap">
        <div class="chart-val"><?= $e['total'] ?></div>
        <div class="chart-bar" style="height: <?= $h ?>%" title="<?= $e['total'] ?> commandes (<?= formatMontant($e['ca']) ?>)"></div>
        <div class="chart-label"><?= date('d/m', strtotime($e['jour'])) ?></div>
      </div>
      <?php endforeach; ?>
      
      <?php if (!$evolution): ?>
        <div style="margin: auto; color: var(--rl-muted); font-size: 13px;">Aucune donnée disponible sur cette période.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Répartition par catégorie -->
  <div class="dashboard-card">
    <div class="card-title">
      <span class="material-symbols-outlined">pie_chart</span> Volume par catégorie
    </div>
    <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
      <?php $total_cat = array_sum(array_column($par_categorie, 'nb')) ?: 1; ?>
      <?php foreach ($par_categorie as $cat): 
        $pct = round($cat['nb'] / $total_cat * 100);
      ?>
      <div class="progress-wrap">
        <div class="progress-label">
          <span style="font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
            <span style="font-size:16px"><?= $cat['icone'] ?></span> <?= sanitize($cat['nom']) ?>
          </span>
          <span style="font-weight: 600; color: var(--rl-text);"><?= $cat['nb'] ?> <span style="font-weight:400; color:var(--rl-muted)">(<?= $pct ?>%)</span></span>
        </div>
        <div class="progress-bar-bg">
          <div class="progress-bar-fill" style="width: <?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Section Listes Top Services & Livreurs -->
<div class="grid-dashboard">
  <!-- Top services -->
  <div class="dashboard-card">
    <div class="card-title" style="margin-bottom: 12px;">
      <span class="material-symbols-outlined">star</span> Établissements leaders
    </div>
    <div class="table-responsive">
      <table class="modern-table">
        <thead>
          <tr>
            <th style="width: 40px">#</th>
            <th>Service</th>
            <th style="text-align: center;">Commandes</th>
            <th>Volume CA</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top_services as $i => $s): ?>
          <tr>
            <td style="font-weight: 700; color: var(--rl-muted);"><?= $i + 1 ?></td>
            <td style="font-weight: 600;">
              <span style="margin-right: 4px; font-size:15px"><?= $s['icone'] ?></span> <?= sanitize($s['nom']) ?>
            </td>
            <td style="text-align: center; font-weight: 600;"><?= $s['commandes'] ?></td>
            <td style="font-weight: 700; color: var(--rl-primary);"><?= formatMontant($s['ca']) ?></td>
            <td>
              <span class="star-span">
                <span class="material-symbols-outlined" style="font-variant-lightning-bolt:fill">star</span>
                <?= number_format($s['note_moyenne'], 1) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top livreurs -->
  <div class="dashboard-card">
    <div class="card-title">
      <span class="material-symbols-outlined">directions_bike</span> Top coursiers
    </div>
    <div style="display: flex; flex-direction: column; justify-content: space-between; flex: 1;">
      <?php foreach ($top_livreurs as $i => $l): ?>
      <div class="row-list-item">
        <div class="rank-badge">#<?= $i + 1 ?></div>
        <div class="avatar-text"><?= strtoupper(substr($l['nom'], 0, 2)) ?></div>
        <div style="flex: 1;">
          <div style="font-weight: 600; font-size: 14px; color: var(--rl-text);"><?= sanitize($l['nom']) ?></div>
          <div style="font-size: 12px; color: var(--rl-muted); margin-top: 2px; display: flex; align-items: center; gap: 8px;">
            <span><strong><?= $l['nb_livraisons_total'] ?></strong> livraisons</span>
            <span>•</span>
            <span class="star-span">
              <span class="material-symbols-outlined" style="font-variant-lightning-bolt:fill">star</span>
              <?= number_format($l['note_moyenne'], 1) ?>
            </span>
          </div>
        </div>
        <div style="font-weight: 700; color: var(--rl-primary); font-size: 14px;">
          <?= formatMontant($l['gains_total']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>