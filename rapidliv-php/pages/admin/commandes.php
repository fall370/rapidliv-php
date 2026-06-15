<?php
// ============================================================
//  RAPIDLIV — Admin : Gestion des commandes (VERSION MODERNE)
//  Fichier : pages/admin/commandes.php
// ============================================================

$statut_filtre = $_GET['statut'] ?? '';
$recherche     = $_GET['q'] ?? '';
$page_num      = max(1, (int)($_GET['p'] ?? 1));
$par_page      = 15;
$offset        = ($page_num - 1) * $par_page;

$where  = "WHERE 1=1";
$params = [];
if ($statut_filtre) { $where .= " AND c.statut = ?";         $params[] = $statut_filtre; }
if ($recherche)     { $where .= " AND (c.reference LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.telephone LIKE ?)";
                      $params[] = "%$recherche%"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }

$total_count = Database::queryOne(
    "SELECT COUNT(DISTINCT c.id) AS nb FROM commandes c JOIN utilisateurs u ON u.id=c.client_id $where", $params);
$pagination  = paginer((int)$total_count['nb'], $par_page, $page_num);

// ---- Requête principale avec GROUP_CONCAT ----
$params_page = array_merge($params, [$par_page, $offset]);
$commandes   = Database::query(
    "SELECT
        c.id, c.reference, c.statut, c.sous_total, c.frais_livraison, c.total, c.cree_le, c.adresse_livraison,
        CONCAT(u.prenom,' ',u.nom)   AS client_nom,
        u.telephone                  AS client_tel,
        s.id                         AS service_id,
        s.nom                        AS service_nom,
        cs.icone                     AS service_icone,
        CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom,
        ul.telephone                 AS livreur_tel,
        l.id                         AS livraison_id,
        l.statut                     AS livr_statut,
        l.eta_minutes,
        pay.methode                  AS paiement_methode,
        pay.statut                   AS paiement_statut,
        (SELECT GROUP_CONCAT(CONCAT(ci.quantite,'× ',ci.nom_produit) ORDER BY ci.id SEPARATOR ', ')
         FROM commande_items ci WHERE ci.commande_id = c.id)  AS articles_resume,
        (SELECT COUNT(*) FROM commande_items ci WHERE ci.commande_id = c.id) AS nb_articles
     FROM commandes c
     JOIN utilisateurs u     ON u.id  = c.client_id
     JOIN services s         ON s.id  = c.service_id
     JOIN categories_service cs ON cs.id = s.categorie_id
     LEFT JOIN livraisons l    ON l.commande_id = c.id
     LEFT JOIN livreurs lv     ON lv.id = l.livreur_id
     LEFT JOIN utilisateurs ul ON ul.id = lv.id
     LEFT JOIN paiements pay   ON pay.commande_id = c.id
     $where
     GROUP BY c.id
     ORDER BY c.cree_le DESC
     LIMIT ? OFFSET ?",
    $params_page);

$livreurs_dispo = Database::query(
    "SELECT l.id, CONCAT(u.prenom,' ',u.nom) AS nom, l.type_vehicule, l.note_moyenne, z.nom AS zone
     FROM livreurs l JOIN utilisateurs u ON u.id=l.id LEFT JOIN zones z ON z.id=l.zone_id
     WHERE l.statut='disponible' AND l.documents_valides=1 ORDER BY l.note_moyenne DESC");

$resume_rows = Database::query("SELECT statut, COUNT(*) AS nb FROM commandes GROUP BY statut");
$resume_map  = array_column($resume_rows, 'nb', 'statut');
?>

<!-- Styles UI Modernisés -->
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
  }
  .btn-modern-primary { background: var(--rl-primary); color: white; }
  .btn-modern-primary:hover { background: var(--rl-primary-hover); }
  .btn-modern-outline { background: var(--rl-surface); border-color: var(--rl-border); color: var(--rl-text); }
  .btn-modern-outline:hover { background: var(--rl-bg); border-color: var(--rl-muted); }

  .filter-bar { 
    background: var(--rl-surface); padding: 16px; border-radius: var(--rl-radius-lg); 
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border);
    display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px;
  }
  .input-modern {
    background: var(--rl-bg); border: 1px solid var(--rl-border); padding: 8px 12px;
    border-radius: var(--rl-radius-md); font-size: 14px; outline: none; transition: border 0.2s;
  }
  .input-modern:focus { border-color: var(--rl-primary); }

  .status-pill-container { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
  .status-pill {
    padding: 6px 12px; font-size: 13px; font-weight: 500; border-radius: 20px;
    text-decoration: none; display: flex; align-items: center; gap: 6px;
    border: 1px solid var(--rl-border); background: var(--rl-surface); color: var(--rl-muted);
    transition: all 0.2s;
  }
  .status-pill.active { background: var(--rl-text); color: #fff; border-color: var(--rl-text); }
  .status-pill .pill-badge { background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 10px; font-size: 11px; }
  .status-pill.active .pill-badge { background: rgba(255,255,255,0.2); color: #fff; }

  .modern-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg);
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); overflow: hidden;
  }

  .modern-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  .modern-table th { background: #f8fafc; padding: 14px 16px; color: var(--rl-muted); font-weight: 600; border-bottom: 1px solid var(--rl-border); }
  .modern-table td { padding: 16px; border-bottom: 1px solid var(--rl-border); vertical-align: middle; }
  .modern-table tbody tr:hover { background: #f8fafc; }

  .badge-modern {
    display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;
  }
  .badge-gray { background: #f1f5f9; color: #475569; }
  .badge-blue { background: #dbeafe; color: #1e40af; }
  .badge-amber { background: #fef3c7; color: #92400e; }
  .badge-green { background: #dcfce7; color: #166534; }
  .badge-red { background: #fee2e2; color: #991b1b; }

  .modern-modal {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px;
  }
  .modal-content {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); width: 100%; max-width: 550px;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); border: 1px solid var(--rl-border); overflow: hidden;
    animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes modalSlide { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="modern-header">
  <div>
    <div class="modern-title">Gestion des commandes</div>
    <div class="modern-subtitle"><?= $pagination['total'] ?> commande<?= $pagination['total'] > 1 ? 's' : '' ?> au total</div>
  </div>
  <div style="display:flex; gap:8px;">
    <button class="btn-modern btn-modern-outline" onclick="exporterCSV()">📥 Exporter CSV</button>
    <button class="btn-modern btn-modern-outline" onclick="location.reload()">⟳ Actualiser</button>
  </div>
</div>

<!-- Filtres de recherche -->
<form method="GET" action="" class="filter-bar">
  <input type="hidden" name="page" value="admin_commandes">
  <div style="position:relative; flex:1; min-width:240px;">
    <input type="text" name="q" class="input-modern" style="width:100%; padding-left:36px;" placeholder="Référence, nom client, numéro..." value="<?= sanitize($recherche) ?>">
    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--rl-muted)">🔍</span>
  </div>
  <select name="statut" class="input-modern" onchange="this.form.submit()">
    <option value="">Tous les statuts</option>
    <?php foreach (['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','en_route'=>'En route','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l): ?>
    <option value="<?=$v?>" <?=$statut_filtre===$v?'selected':''?>><?=$l?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn-modern btn-modern-primary">Filtrer</button>
  <?php if($statut_filtre || $recherche): ?>
    <a href="index.php?page=admin_commandes" class="btn-modern btn-modern-outline" style="text-decoration:none">✕ Effacer</a>
  <?php endif; ?>
</form>

<!-- Boutons raccourcis d'état -->
<div class="status-pill-container">
  <?php foreach ([
    ''               => ['Toutes',          array_sum($resume_map)],
    'en_attente'     => ['En attente',      $resume_map['en_attente']??0],
    'confirmee'      => ['Confirmées',      $resume_map['confirmee']??0],
    'en_preparation' => ['En préparation',  $resume_map['en_preparation']??0],
    'en_route'       => ['En route',        $resume_map['en_route']??0],
    'livree'         => ['Livrées',         $resume_map['livree']??0],
    'annulee'        => ['Annulées',        $resume_map['annulee']??0],
  ] as $v => [$lbl, $nb]): ?>
  <a href="index.php?page=admin_commandes<?=$v?"&statut=$v":''?>" class="status-pill <?=$statut_filtre===$v?'active':''?>">
    <?=$lbl?> <span class="pill-badge"><?=$nb?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$commandes): ?>
<div class="modern-card" style="padding:48px; text-align:center;">
  <div style="font-size:40px; margin-bottom:12px;">📭</div>
  <div style="font-weight:600; font-size:16px;">Aucune commande trouvée</div>
  <div style="color:var(--rl-muted); font-size:14px; margin-top:4px;"><?=$statut_filtre||$recherche?'Essayez d\'ajuster vos filtres de recherche.':'Aucune donnée actuellement.'?></div>
</div>
<?php else: ?>

<div class="modern-card">
  <div style="overflow-x:auto">
    <table class="modern-table" id="table-commandes">
      <thead>
        <tr>
          <th>Référence</th>
          <th>Client</th>
          <th>Service</th>
          <th>Articles</th>
          <th>Total</th>
          <th>Paiement</th>
          <th>Livreur</th>
          <th>Statut</th>
          <th>Date</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($commandes as $c): ?>
      <tr>
        <!-- Référence -->
        <td><span style="font-family:monospace; font-weight:600; color:#1e293b; background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:12px"><?= sanitize($c['reference']) ?></span></td>
        
        <!-- Client -->
        <td>
          <div style="font-weight:600"><?= sanitize($c['client_nom']) ?></div>
          <div style="font-size:12px; color:var(--rl-muted)"><?= sanitize($c['client_tel']??'') ?></div>
        </td>

        <!-- Service -->
        <td>
          <div style="display:flex; align-items:center; gap:6px">
            <span><?= $c['service_icone'] ?></span>
            <span><?= sanitize($c['service_nom']) ?></span>
          </div>
        </td>

        <!-- Articles -->
        <td>
          <?php if (!empty($c['articles_resume'])): ?>
            <div style="max-width:220px; font-size:13px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($c['articles_resume']) ?>">
              <?= sanitize($c['articles_resume']) ?>
            </div>
            <div style="font-size:11px; color:var(--rl-muted); margin-top:2px;">
              <?= $c['nb_articles'] ?> article<?= $c['nb_articles']>1?'s':'' ?>
            </div>
          <?php else: ?>
            <span class="badge-modern badge-red">Aucun article</span>
          <?php endif; ?>
        </td>

        <!-- Total -->
        <td>
          <div style="font-weight:600; color:var(--rl-text)"><?= formatMontant($c['total']) ?></div>
          <div style="font-size:11px; color:var(--rl-muted)">Livraison: <?= formatMontant($c['frais_livraison']) ?></div>
        </td>

        <!-- Paiement -->
        <td>
          <?php if ($c['paiement_methode']): ?>
            <div style="font-size:13px; font-weight:500">
              <?= match($c['paiement_methode']) {
                'orange_money' => '📱 Orange Money',
                'wave'         => '🌊 Wave',
                'cash'         => '💵 Cash',
                'carte'        => '💳 Carte',
                default        => sanitize($c['paiement_methode'])
              } ?>
            </div>
            <?php $pb=['en_attente'=>'badge-gray','autorise'=>'badge-amber','capture'=>'badge-green','rembourse'=>'badge-blue','echoue'=>'badge-red']; ?>
            <span class="badge-modern <?=$pb[$c['paiement_statut']]??'badge-gray'?>" style="font-size:10px; padding:1px 6px; margin-top:4px">
              <?=ucfirst($c['paiement_statut']??'')?>
            </span>
          <?php else: ?><span style="color:var(--rl-muted)">—</span><?php endif; ?>
        </td>

        <!-- Livreur -->
        <td>
          <?php if ($c['livreur_nom']): ?>
            <div style="font-weight:500; font-size:13px"><?= sanitize($c['livreur_nom']) ?></div>
            <?php if ($c['eta_minutes']): ?><span class="badge-modern badge-amber" style="font-size:10px; padding:1px 4px; margin-top:2px">⏱️ <?=$c['eta_minutes']?> min</span><?php endif; ?>
          <?php elseif (in_array($c['statut'], ['en_attente','confirmee'])): ?>
            <span class="badge-modern badge-gray" style="color:#94a3b8">Non assigné</span>
          <?php else: ?><span style="color:var(--rl-muted)">—</span><?php endif; ?>
        </td>

        <!-- Statut principal -->
        <td><?= statutBadgeHTML($c['statut']) ?></td>

        <!-- Date -->
        <td style="font-size:12px; color:var(--rl-muted); white-space:nowrap"><?= formatDate($c['cree_le']) ?></td>

        <!-- Actions -->
        <td style="text-align:right">
          <div style="display:inline-flex; gap:6px; align-items:center">
            <button class="btn-modern btn-modern-outline" style="padding:6px 10px; font-size:12px" onclick="voirDetail('<?=$c['id']?>')">👁️</button>
            
            <?php if ($c['statut']==='en_attente'): ?>
              <button class="btn-modern btn-modern-primary" style="padding:6px 10px; font-size:12px" onclick="ouvrirAssignation('<?=$c['id']?>','<?=sanitize($c['reference'])?>')">🏍️ Assigner</button>
            <?php endif; ?>

            <?php
            $transitions = ['en_attente'=>['confirmee','annulee'],'confirmee'=>['en_preparation','annulee'],'en_preparation'=>['en_route','annulee'],'en_route'=>['annulee']];
            $dispo = $transitions[$c['statut']] ?? [];
            if ($dispo): ?>
              <select class="input-modern" style="padding:5px 8px; font-size:12px; width:auto; background:#fff" onchange="changerStatut('<?=$c['id']?>',this)">
                <option value="">Statut…</option>
                <?php foreach ($dispo as $s): ?>
                  <option value="<?=$s?>"><?=ucfirst(str_replace('_',' ',$s))?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination moderne -->
<?php if ($pagination['total_pages']>1): ?>
<div style="display:flex; justify-content:center; gap:6px; margin-top:24px">
  <a href="?page=admin_commandes&p=<?=$page_num-1?>&statut=<?=urlencode($statut_filtre)?>&q=<?=urlencode($recherche)?>" class="btn-modern btn-modern-outline <?=!$pagination['a_precedent']?'style=\"opacity:0.5; pointer-events:none\"':''?>">« Préc.</a>
  <?php 
  $d=max(1,$page_num-2); $f=min($pagination['total_pages'],$page_num+2);
  for($i=$d;$i<=$f;$i++): ?>
    <a href="?page=admin_commandes&p=<?=$i?>&statut=<?=urlencode($statut_filtre)?>&q=<?=urlencode($recherche)?>" class="btn-modern <?=$i===$page_num?'btn-modern-primary':'btn-modern-outline'?>"><?=$i?></a>
  <?php endfor; ?>
  <a href="?page=admin_commandes&p=<?=$page_num+1?>&statut=<?=urlencode($statut_filtre)?>&q=<?=urlencode($recherche)?>" class="btn-modern btn-modern-outline <?=!$pagination['a_suivant']?'style=\"opacity:0.5; pointer-events:none\"':''?>">Suiv. »</a>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Modals System -->
<div id="modal-detail" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-detail')">
  <div class="modal-content">
    <div style="padding:20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:between; align-items:center">
      <h3 style="margin:0; font-size:18px; font-weight:700" id="modal-detail-titre">Détails de la commande</h3>
      <button style="background:none; border:none; font-size:18px; cursor:pointer" onclick="fermerModal('modal-detail')">✕</button>
    </div>
    <div style="padding:20px; max-height:75vh; overflow-y:auto" id="modal-detail-body"></div>
  </div>
</div>

<div id="modal-assignation" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-assignation')">
  <div class="modal-content" style="max-width:460px">
    <div style="padding:20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:between; align-items:center">
      <h3 style="margin:0; font-size:18px; font-weight:700">Assigner un livreur</h3>
      <button style="background:none; border:none; font-size:18px; cursor:pointer" onclick="fermerModal('modal-assignation')">✕</button>
    </div>
    <div style="padding:20px">
      <input type="hidden" id="assign-cmd-id">
      <div style="background:#f1f5f9; padding:12px; border-radius:var(--rl-radius-md); margin-bottom:16px">
        <span style="font-size:12px; color:var(--rl-muted)">Commande concernée :</span>
        <div style="font-weight:700; font-size:15px; color:var(--rl-text)" id="assign-cmd-ref">—</div>
      </div>
      <div style="margin-bottom:16px">
        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px">Livreurs actifs disponibles :</label>
        <?php if ($livreurs_dispo): ?>
          <select id="assign-livreur" class="input-modern" style="width:100%">
            <option value="">-- Sélectionner un profil --</option>
            <?php foreach ($livreurs_dispo as $l): ?>
              <option value="<?=$l['id']?>"><?=sanitize($l['nom'])?> (<?=$l['type_vehicule']?>) · <?=sanitize($l['zone']??'?')?> · ★ <?=number_format($l['note_moyenne'],1)?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <div style="color:var(--rl-primary); font-size:13px; padding:6px 0;">Aucun coursier disponible pour le moment.</div>
        <?php endif; ?>
      </div>
    </div>
    <div style="padding:16px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-assignation')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="confirmerAssignation()" <?=!$livreurs_dispo?'disabled':''?>>Confirmer l'assignation</button>
    </div>
  </div>
</div>

<script>
function voirDetail(id) {
  const body = document.getElementById('modal-detail-body');
  document.getElementById('modal-detail-titre').textContent = 'Chargement...';
  body.innerHTML = '<div style="text-align:center; padding:30px">⏳ Analyse des informations...</div>';
  document.getElementById('modal-detail').style.display = 'flex';

  api('commande_detail', {}, 'GET', {id}).then(c => {
    document.getElementById('modal-detail-titre').textContent = 'Commande ' + c.reference;
    let h = `<div style="display:flex; justify-content:space-between; margin-bottom:16px">
              <div><strong style="font-size:16px">${esc(c.service_nom)}</strong><div style="color:var(--rl-muted); font-size:13px">Client : ${esc(c.client_nom)}</div></div>
              <div>${badge(c.statut)}</div>
             </div>`;
    h += `<div style="font-size:13px; background:#f8fafc; padding:10px; border-radius:6px; margin-bottom:16px">📍 <strong>Lieu de livraison :</strong> ${esc(c.adresse_livraison)}</div>`;
    
    if (c.livreur_nom) {
      h += `<div style="background:#eff6ff; padding:12px; border-radius:8px; display:flex; gap:10px; margin-bottom:16px; border:1px solid #bfdbfe">
              <span style="font-size:24px">🏍️</span>
              <div>
                <div style="font-weight:600; color:#1e3a8a">${esc(c.livreur_nom)}</div>
                <div style="font-size:12px; color:#60a5fa">${esc(c.livreur_tel||'')} ${c.eta_minutes ? '• ETA: '+c.eta_minutes+' min' : ''}</div>
                ${c.code_confirmation ? `<div style="font-size:11px; margin-top:6px; background:#fff; padding:4px 8px; border-radius:4px; display:inline-block">Code de validation : <strong style="color:var(--rl-primary)">${c.code_confirmation}</strong></div>` : ''}
              </div>
            </div>`;
    }

    h += `<div style="font-weight:600; font-size:14px; margin-bottom:10px; padding-bottom:4px; border-bottom:1px solid var(--rl-border)">Panier d'articles</div>`;
    if (c.items && c.items.length) {
      c.items.forEach(i => {
        h += `<div style="display:flex; align-items:center; gap:10px; justify-content:space-between; font-size:13px; margin-bottom:8px">
                ${renderProductThumb(i)}
                <div style="flex:1"><span style="color:var(--rl-muted)">${i.quantite}x</span> ${esc(i.nom_produit)}</div>
                <div style="font-weight:500">${parseInt(i.sous_total).toLocaleString('fr-FR')} FCFA</div>
              </div>`;
      });
    } else {
      h += `<div style="color:var(--rl-muted); font-size:13px">Aucun article indexé.</div>`;
    }

    h += `<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--rl-border); font-size:13px">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px"><span style="color:var(--rl-muted)">Sous-total</span><span>${parseInt(c.sous_total).toLocaleString('fr-FR')} FCFA</span></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px"><span style="color:var(--rl-muted)">Frais de livraison</span><span>${parseInt(c.frais_livraison).toLocaleString('fr-FR')} FCFA</span></div>
            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:var(--rl-primary); border-top:2px dashed var(--rl-border); padding-top:8px"><span>Total général</span><span>${parseInt(c.total).toLocaleString('fr-FR')} FCFA</span></div>
          </div>`;
    body.innerHTML = h;
  }).catch(e => { body.innerHTML = `<div style="color:var(--rl-primary)">Erreur de chargement: ${esc(e.message)}</div>`; });
}

function ouvrirAssignation(id, ref) {
  document.getElementById('assign-cmd-id').value = id;
  document.getElementById('assign-cmd-ref').textContent = ref;
  document.getElementById('modal-assignation').style.display = 'flex';
}

function fermerModal(id) { document.getElementById(id).style.display = 'none'; }

function confirmerAssignation() {
  const cmdId  = document.getElementById('assign-cmd-id').value;
  const livrId = document.getElementById('assign-livreur')?.value;
  if (!livrId) return;
  api('assigner_livreur', {commande_id: cmdId, livreur_id: livrId})
    .then(d => { location.reload(); })
    .catch(e => alert('Erreur : ' + e.message));
}

function changerStatut(id, sel) {
  const statut = sel.value; if (!statut) return;
  api('changer_statut_commande', {commande_id: id, statut})
    .then(() => { location.reload(); })
    .catch(e => { alert('Erreur : ' + e.message); sel.value=''; });
}

function exporterCSV() {
  const headings = ['Référence','Client','Service','Articles','Total','Statut','Date'];
  const rows = [headings];
  document.querySelectorAll('#table-commandes tbody tr').forEach(tr => {
    const tds = [...tr.querySelectorAll('td')].map(td => td.textContent.trim().replace(/\s+/g, ' '));
    if (tds.length > 1) rows.push([tds[0], tds[1], tds[2], tds[3], tds[4], tds[7], tds[8]]);
  });
  const csvContent = '\uFEFF' + rows.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.setAttribute("download", `rapidliv_commandes_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s){ return esc(s).replace(/"/g,'&quot;'); }
function renderProductThumb(item) {
  const src = item.image_url || `https://loremflickr.com/320/240/${encodeURIComponent((item.nom_produit || 'produit') + ',product')}`;
  const fallback = esc(item.icone || '📦');
  return `<div class="product-img" style="width:44px;height:44px;border-radius:8px"><img src="${escAttr(src)}" alt="${escAttr(item.nom_produit || 'Produit')}" loading="lazy" onerror="this.parentElement.textContent='${fallback}'"></div>`;
}
function badge(s){
  const m={en_attente:['badge-gray','En attente'],confirmee:['badge-blue','Confirmée'],en_preparation:['badge-amber','En préparation'],en_route:['badge-blue','En route'],livree:['badge-green','Livrée'],annulee:['badge-red','Annulée']};
  const[c,l]=m[s]||['badge-gray',s]; return `<span class="badge-modern ${c}">${l}</span>`;
}
</script>
