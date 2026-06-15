<?php
// ============================================================
//  RAPIDLIV — Admin : Gestion des services (VERSION MODERNE + GOOGLE ICONS)
//  Fichier : pages/admin/services.php
// ============================================================

$services   = Database::query(
    "SELECT s.*, cs.nom AS cat_nom, cs.icone,
            COUNT(DISTINCT p.id)  AS nb_produits,
            COUNT(DISTINCT c.id)  AS nb_commandes
     FROM services s
     JOIN categories_service cs ON cs.id = s.categorie_id
     LEFT JOIN produits p  ON p.service_id  = s.id
     LEFT JOIN commandes c ON c.service_id  = s.id
     GROUP BY s.id
     ORDER BY s.actif DESC, cs.ordre, s.nom");

$categories = Database::query("SELECT * FROM categories_service ORDER BY ordre");
$categories_produit = Database::query("SELECT id, service_id, nom FROM categories_produit ORDER BY service_id, ordre, nom");

$hasImageUrl = Database::columnExists('produits', 'image_url');
$imageSelect = $hasImageUrl ? ', image_url' : '';

$produits_preview = Database::query(
    "SELECT id, service_id, nom, icone$imageSelect
     FROM produits
     WHERE disponible=1
     ORDER BY service_id, nom");

$produits_par_service = [];
foreach ($produits_preview as $produit) {
    $sid = (int)$produit['service_id'];
    if (!isset($produits_par_service[$sid])) $produits_par_service[$sid] = [];
    if (count($produits_par_service[$sid]) < 4) $produits_par_service[$sid][] = $produit;
}

$categories_produit_par_service = [];
foreach ($categories_produit as $catProduit) {
    $sid = (int)$catProduit['service_id'];
    if (!isset($categories_produit_par_service[$sid])) $categories_produit_par_service[$sid] = [];
    $categories_produit_par_service[$sid][] = $catProduit;
}

function adminProduitImageUrl(array $produit): string {
    $image = trim($produit['image_url'] ?? '');
    if ($image !== '') return $image;
    return 'https://loremflickr.com/240/180/' . rawurlencode($produit['nom'] . ',product');
}

function adminProduitPreview(array $produit): string {
    $src = htmlspecialchars(adminProduitImageUrl($produit), ENT_QUOTES, 'UTF-8');
    $alt = htmlspecialchars($produit['nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
    $fallback = htmlspecialchars($produit['icone'] ?? '📦', ENT_QUOTES, 'UTF-8');
    return '<div class="product-preview-item"><img src="' . $src . '" alt="' . $alt . '" loading="lazy" onerror="this.parentElement.textContent=\'' . $fallback . '\'"><span>' . $alt . '</span></div>';
}
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
  
  /* En-tête de page */
  .modern-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
  .modern-title { font-size: 24px; font-weight: 700; color: var(--rl-text); letter-spacing: -0.5px; }
  .modern-subtitle { font-size: 14px; color: var(--rl-muted); margin-top: 2px; }

  /* Boutons */
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
  .btn-modern-sm { padding: 6px 10px; font-size: 13px; }

  /* Grille & Cartes */
  .grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px; }
  
  .modern-card {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg);
    box-shadow: var(--rl-shadow); border: 1px solid var(--rl-border); padding: 20px;
    display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;
  }
  .modern-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05); }

  /* Badges */
  .badge-modern { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
  .badge-green { background: #dcfce7; color: #166534; }
  .badge-red { background: #fee2e2; color: #991b1b; }
  .badge-gray { background: #f1f5f9; color: #475569; }

  /* Éléments internes d'infos */
  .info-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; border-top: 1px dashed var(--rl-border); padding-top: 12px; }
  .info-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--rl-muted); }
  .info-item .material-symbols-outlined { font-size: 16px; color: var(--rl-muted); }

  .product-preview-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin:4px 0 16px; }
  .product-preview-item { border:1px solid var(--rl-border); border-radius:8px; overflow:hidden; background:#f8fafc; min-width:0; font-size:20px; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:86px; }
  .product-preview-item img { width:100%; height:58px; object-fit:cover; display:block; }
  .product-preview-item span { display:block; width:100%; padding:5px 6px; font-size:10.5px; font-weight:600; color:var(--rl-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .helper-text { font-size: 12px; color: var(--rl-muted); margin-top: 5px; }
  .service-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr)) 38px;
    gap: 8px;
    margin-top: 10px;
    border-top: 1px solid var(--rl-border);
    padding-top: 14px;
  }
  .service-actions .btn-modern {
    min-width: 0;
    width: 100%;
    padding-left: 8px;
    padding-right: 8px;
    white-space: nowrap;
    overflow: hidden;
  }
  .service-actions .btn-delete {
    grid-column: 3;
    grid-row: 1 / span 2;
    align-self: stretch;
    padding: 0;
  }

  /* Modals */
  .modern-modal {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(4px);
    display: flex; align-items: flex-start; justify-content: center; z-index: 1000; padding: 16px;
    overflow-y: auto;
  }
  .modal-content {
    background: var(--rl-surface); border-radius: var(--rl-radius-lg); width: 100%; max-width: 520px;
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
    border-radius: var(--rl-radius-md); font-size: 14px; outline: none; transition: border 0.2s; box-sizing: border-box; color: var(--rl-text);
  }
  .input-modern:focus { border-color: var(--rl-primary); }

  /* Flash Alert intérieur */
  .flash-inner { padding: 10px 12px; border-radius: var(--rl-radius-md); font-size: 13px; font-weight: 500; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
  .flash-inner-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

  /* Empty State */
  .empty-state-card { grid-column: 1 / -1; text-align: center; padding: 48px 20px; background: var(--rl-surface); border: 2px dashed var(--rl-border); border-radius: var(--rl-radius-lg); }

  @keyframes modalSlide { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  @media (max-width: 640px) {
    .modern-header { align-items: flex-start; flex-direction: column; }
    .grid-layout { grid-template-columns: 1fr; }
    .modern-card { padding: 16px; }
    .modal-content { max-height: calc(100vh - 16px); }
    .form-row { grid-template-columns: 1fr; gap: 0; margin-bottom: 0; }
    .product-preview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .service-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .service-actions .btn-delete { grid-column: auto; grid-row: auto; min-height: 36px; }
    .btn-modern { white-space: normal; }
  }
</style>

<!-- En-tête -->
<div class="modern-header">
  <div>
    <div class="modern-title">Services partenaires</div>
    <div class="modern-subtitle"><?= count($services) ?> service<?= count($services)>1?'s':'' ?> référencé<?= count($services)>1?'s':'' ?></div>
  </div>
  <button class="btn-modern btn-modern-primary" onclick="ouvrirModalAjout()">
    <span class="material-symbols-outlined" style="font-size:18px">add_business</span> Ajouter un service
  </button>
</div>

<!-- Grille des services -->
<div class="grid-layout">
<?php foreach ($services as $s): ?>
<div class="modern-card" style="<?= !$s['actif'] ? 'opacity:.75;' : '' ?> border-top: 3px solid <?= $s['actif'] ? 'var(--rl-primary)' : 'var(--rl-border)' ?>">
  
  <div>
    <!-- Bloc supérieur -->
    <div style="display:flex; gap:14px; align-items:flex-start; margin-bottom:12px">
      <span class="material-symbols-outlined" style="font-size:32px; background:#f8fafc; padding:10px; border-radius:10px; border:1px solid var(--rl-border); color:var(--rl-primary)">
        <?= $s['icone'] ?: 'storefront' ?>
      </span>
      <div style="flex:1; min-width:0">
        <div style="font-weight:700; font-size:16px; color:var(--rl-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
          <?= sanitize($s['nom']) ?>
        </div>
        <span class="badge-modern badge-gray" style="margin-top:4px; font-size:11px"><?= sanitize($s['cat_nom']) ?></span>
        
        <div style="display:flex; align-items:center; gap:8px; margin-top:8px">
          <span style="display:inline-flex; align-items:center; gap:3px; font-size:12px; font-weight:700; color:var(--rl-text)">
            <span class="material-symbols-outlined" style="font-size:14px; color:#f59e0b; font-variant-lightning-bolt:fill">star</span>
            <?= number_format($s['note_moyenne'],1) ?>
          </span>
          <span style="font-size:12px; color:var(--rl-muted)">· <?= $s['nb_commandes'] ?> commande<?= $s['nb_commandes']>1?'s':'' ?></span>
        </div>
      </div>
      <span class="badge-modern <?= $s['actif'] ? 'badge-green' : 'badge-red' ?>">
        <?= $s['actif'] ? 'Actif' : 'Inactif' ?>
      </span>
    </div>

    <?php $preview_items = $produits_par_service[(int)$s['id']] ?? []; ?>
    <?php if ($preview_items): ?>
    <div class="product-preview-grid">
      <?php foreach ($preview_items as $produit): ?>
        <?= adminProduitPreview($produit) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Liste d'informations -->
    <div class="info-list">
      <div class="info-item">
        <span class="material-symbols-outlined">schedule</span>
        <span>Délai de préparation : <strong><?= $s['delai_min'] ?>–<?= $s['delai_max'] ?> min</strong></span>
      </div>
      <div class="info-item">
        <span class="material-symbols-outlined">shopping_bag</span>
        <span>Catalogue : <strong><?= $s['nb_produits'] ?> produit<?= $s['nb_produits']>1?'s':'' ?></strong></span>
      </div>
      <div class="info-item">
        <span class="material-symbols-outlined">shopping_cart_checkout</span>
        <span>Commande minimale : <strong><?= formatMontant($s['commande_min']) ?></strong></span>
      </div>
      <?php if ($s['adresse']): ?>
      <div class="info-item" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
        <span class="material-symbols-outlined">location_on</span>
        <span><?= sanitize($s['adresse']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($s['telephone']): ?>
      <div class="info-item">
        <span class="material-symbols-outlined">call</span>
        <span><?= sanitize($s['telephone']) ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Barre d'actions -->
  <div class="service-actions">
    <button class="btn-modern btn-modern-primary btn-modern-sm"
            onclick="ouvrirModalProduit(<?= (int)$s['id'] ?>, '<?= sanitize($s['nom']) ?>')">
      <span class="material-symbols-outlined" style="font-size:16px">add_shopping_cart</span> Produit
    </button>

    <button class="btn-modern btn-modern-outline btn-modern-sm"
            onclick="genererProduitsService(<?= (int)$s['id'] ?>, '<?= sanitize($s['nom']) ?>')"
            title="Créer automatiquement un catalogue de départ">
      <span class="material-symbols-outlined" style="font-size:16px">auto_awesome</span> Auto
    </button>

    <button class="btn-modern btn-modern-outline btn-modern-sm"
            onclick="ouvrirModalModif(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
      <span class="material-symbols-outlined" style="font-size:16px">edit</span> Modifier
    </button>
    
    <button class="btn-modern btn-modern-sm <?= $s['actif'] ? 'btn-modern-outline' : 'btn-modern-primary' ?>"
            onclick="toggleService(<?= $s['id'] ?>, <?= $s['actif'] ? 0 : 1 ?>)">
      <span class="material-symbols-outlined" style="font-size:16px"><?= $s['actif'] ? 'pause' : 'play_arrow' ?></span>
      <?= $s['actif'] ? 'Désactiver' : 'Activer' ?>
    </button>
    
    <button class="btn-modern btn-modern-danger btn-modern-sm btn-delete"
            onclick="supprimerService(<?= $s['id'] ?>, '<?= sanitize($s['nom']) ?>', <?= $s['nb_commandes'] ?>)"
            title="Supprimer ce service">
      <span class="material-symbols-outlined" style="font-size:16px">delete</span>
    </button>
  </div>
</div>
<?php endforeach; ?>

<?php if (!$services): ?>
<div class="empty-state-card">
  <span class="material-symbols-outlined" style="font-size:48px; color:var(--rl-muted); margin-bottom:12px">storefront</span>
  <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--rl-text)">Aucun service trouvé</h3>
  <p style="margin:6px 0 0 0; font-size:13px; color:var(--rl-muted)">Ajoutez votre premier partenaire pour démarrer l'activité.</p>
</div>
<?php endif; ?>
</div>


<!-- ============ MODAL AJOUT SERVICE ============ -->
<div id="modal-add-svc" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-add-svc')">
  <div class="modal-content">
    <div style="padding:16px 20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--rl-text)">Nouveau service partenaire</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-add-svc')">
        <span class="material-symbols-outlined" style="color:var(--rl-muted)">close</span>
      </button>
    </div>
    
    <div style="padding:20px">
      <div class="form-group">
        <label class="form-label required">Catégorie</label>
        <select class="input-modern" id="ns-cat">
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"><?= sanitize($c['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label required">Nom du service</label>
        <input class="input-modern" id="ns-nom" placeholder="Ex: Supermarché Marché Central">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="input-modern" id="ns-desc" rows="2" placeholder="Courte description de l'établissement..." style="resize:vertical"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Image du service depuis votre ordinateur</label>
        <input type="file" class="input-modern" id="ns-logo-file" accept="image/*">
        <div class="helper-text">JPG, PNG, WEBP ou GIF. Taille maximum : 3 Mo.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Ou image du service par URL</label>
        <input class="input-modern" id="ns-logo" placeholder="https://exemple.com/image-service.jpg">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Adresse physique</label>
          <input class="input-modern" id="ns-adr" placeholder="Ex: Escale, Ziguinchor">
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input class="input-modern" id="ns-tel" placeholder="+221 33 991 xx xx">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Délai min (minutes)</label>
          <input type="number" class="input-modern" id="ns-dmin" value="20" min="5">
        </div>
        <div class="form-group">
          <label class="form-label">Délai max (minutes)</label>
          <input type="number" class="input-modern" id="ns-dmax" value="40" min="10">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Commande minimum (FCFA)</label>
        <input type="number" class="input-modern" id="ns-cmin" value="2000" min="0">
      </div>
    </div>
    
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-add-svc')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="creerService()">Créer le service</button>
    </div>
  </div>
</div>


<!-- ============ MODAL MODIFICATION SERVICE ============ -->
<div id="modal-edit-svc" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-edit-svc')">
  <div class="modal-content">
    <div style="padding:16px 20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--rl-text)">Modifier l'établissement</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-edit-svc')">
        <span class="material-symbols-outlined" style="color:var(--rl-muted)">close</span>
      </button>
    </div>
    
    <div style="padding:20px">
      <input type="hidden" id="es-id">
      <div class="form-group">
        <label class="form-label required">Nom du service</label>
        <input class="input-modern" id="es-nom">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="input-modern" id="es-desc" rows="2" style="resize:vertical"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Remplacer l’image depuis votre ordinateur</label>
        <input type="file" class="input-modern" id="es-logo-file" accept="image/*">
        <div class="helper-text">Laissez vide pour garder l’image actuelle.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Ou image du service par URL</label>
        <input class="input-modern" id="es-logo" placeholder="https://exemple.com/image-service.jpg">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Adresse</label>
          <input class="input-modern" id="es-adr">
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input class="input-modern" id="es-tel">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Délai min (minutes)</label>
          <input type="number" class="input-modern" id="es-dmin" min="5">
        </div>
        <div class="form-group">
          <label class="form-label">Délai max (minutes)</label>
          <input type="number" class="input-modern" id="es-dmax" min="10">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Commande minimum (FCFA)</label>
        <input type="number" class="input-modern" id="es-cmin" min="0">
      </div>
    </div>
    
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-edit-svc')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="sauverModifService()">Enregistrer</button>
    </div>
  </div>
</div>


<!-- ============ MODAL CONFIRMATION SUPPRESSION ============ -->
<div id="modal-suppr-svc" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-suppr-svc')">
  <div class="modal-content" style="max-width:440px">
    <div style="padding:16px 20px; border-bottom:1px solid #fee2e2; display:flex; justify-content:space-between; align-items:center">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:#991b1b">⚠️ Supprimer le service</h3>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-suppr-svc')">
        <span class="material-symbols-outlined" style="color:var(--rl-muted)">close</span>
      </button>
    </div>
    
    <div style="padding:20px">
      <input type="hidden" id="suppr-svc-id">
      
      <div id="suppr-svc-avert" class="flash-inner flash-inner-error"></div>
      
      <p style="font-size:14px; color:var(--rl-text); line-height:1.5; margin:0 0 14px 0">
        Vous êtes sur le point d'effacer définitivement le partenaire <strong id="suppr-svc-nom"></strong> ainsi que l'intégralité de son catalogue produit. Cette action est <strong>irréversible</strong>.
      </p>
      
      <div id="suppr-svc-warning-cmd" class="flash-inner flash-inner-error" style="display:none;">
        <span class="material-symbols-outlined">warning</span>
        <span>Ce service possède des commandes archivées ou actives. Sa désactivation est recommandée à la place d'une suppression physique.</span>
      </div>
      
      <div class="form-group" style="margin-top:16px">
        <label class="form-label">Tapez exactement <strong>SUPPRIMER</strong> pour confirmer :</label>
        <input type="text" class="input-modern" id="suppr-svc-confirm" placeholder="SUPPRIMER"
               style="font-weight:700; letter-spacing:1px; text-align:center">
      </div>
    </div>
    
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-suppr-svc')">Annuler</button>
      <button class="btn-modern btn-modern-primary" style="background:#ef4444" onclick="confirmerSuppressionService()">Effacer le partenaire</button>
    </div>
  </div>
</div>

<!-- ============ MODAL AJOUT PRODUIT ============ -->
<div id="modal-add-product" class="modern-modal" style="display:none" onclick="if(event.target===this)fermerModal('modal-add-product')">
  <div class="modal-content">
    <div style="padding:16px 20px; border-bottom:1px solid var(--rl-border); display:flex; justify-content:space-between; align-items:center">
      <div>
        <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--rl-text)">Nouveau produit</h3>
        <div id="np-service-label" style="font-size:12px; color:var(--rl-muted); margin-top:2px"></div>
      </div>
      <button style="background:none; border:none; display:flex; cursor:pointer" onclick="fermerModal('modal-add-product')">
        <span class="material-symbols-outlined" style="color:var(--rl-muted)">close</span>
      </button>
    </div>
    
    <div style="padding:20px">
      <input type="hidden" id="np-service-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label required">Nom du produit</label>
          <input class="input-modern" id="np-nom" placeholder="Ex: Riz parfumé 5kg">
        </div>
        <div class="form-group">
          <label class="form-label required">Prix (FCFA)</label>
          <input type="number" class="input-modern" id="np-prix" min="1" placeholder="4500">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Catégorie produit</label>
          <select class="input-modern" id="np-categorie">
            <option value="">Aucune catégorie</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Prix promo (optionnel)</label>
          <input type="number" class="input-modern" id="np-prix-promo" min="0" placeholder="Ex: 3900">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="input-modern" id="np-desc" rows="2" style="resize:vertical" placeholder="Détail court et réaliste du produit"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Image produit depuis votre ordinateur</label>
          <input type="file" class="input-modern" id="np-image-file" accept="image/*">
          <div class="helper-text">JPG, PNG, WEBP ou GIF. Taille maximum : 3 Mo.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Icône fallback</label>
          <input class="input-modern" id="np-icone" value="📦" maxlength="10">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Ou image produit par URL</label>
        <input class="input-modern" id="np-image" placeholder="https://...">
      </div>
    </div>
    
    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--rl-border); display:flex; justify-content:flex-end; gap:8px">
      <button class="btn-modern btn-modern-outline" onclick="fermerModal('modal-add-product')">Annuler</button>
      <button class="btn-modern btn-modern-primary" onclick="creerProduit()">Ajouter le produit</button>
    </div>
  </div>
</div>

<script>
const CATEGORIES_PRODUIT_PAR_SERVICE = <?= json_encode($categories_produit_par_service, JSON_UNESCAPED_UNICODE) ?>;

function fermerModal(id) {
  document.getElementById(id).style.display = 'none';
}

function ouvrirModalAjout() {
  document.getElementById('ns-nom').value  = '';
  document.getElementById('ns-desc').value = '';
  document.getElementById('ns-logo').value = '';
  document.getElementById('ns-logo-file').value = '';
  document.getElementById('ns-adr').value  = '';
  document.getElementById('ns-tel').value  = '';
  document.getElementById('ns-dmin').value = '20';
  document.getElementById('ns-dmax').value = '40';
  document.getElementById('ns-cmin').value = '2000';
  document.getElementById('modal-add-svc').style.display = 'flex';
  setTimeout(() => document.getElementById('ns-nom').focus(), 100);
}

function ouvrirModalProduit(serviceId, serviceNom) {
  document.getElementById('np-service-id').value = serviceId;
  document.getElementById('np-service-label').textContent = serviceNom;
  document.getElementById('np-nom').value = '';
  document.getElementById('np-prix').value = '';
  document.getElementById('np-prix-promo').value = '';
  document.getElementById('np-desc').value = '';
  document.getElementById('np-image').value = '';
  document.getElementById('np-image-file').value = '';
  document.getElementById('np-icone').value = '📦';

  const select = document.getElementById('np-categorie');
  const categories = CATEGORIES_PRODUIT_PAR_SERVICE[String(serviceId)] || [];
  select.innerHTML = '<option value="">Aucune catégorie</option>' + categories.map(c => (
    `<option value="${c.id}">${escHtml(c.nom)}</option>`
  )).join('');

  document.getElementById('modal-add-product').style.display = 'flex';
  setTimeout(() => document.getElementById('np-nom').focus(), 100);
}

function ouvrirModalModif(s) {
  document.getElementById('es-id').value   = s.id;
  document.getElementById('es-nom').value  = s.nom;
  document.getElementById('es-desc').value = s.description || '';
  document.getElementById('es-logo').value = s.logo_url || '';
  document.getElementById('es-logo-file').value = '';
  document.getElementById('es-adr').value  = s.adresse || '';
  document.getElementById('es-tel').value  = s.telephone || '';
  document.getElementById('es-dmin').value = s.delai_min;
  document.getElementById('es-dmax').value = s.delai_max;
  document.getElementById('es-cmin').value = s.commande_min;
  document.getElementById('modal-edit-svc').style.display = 'flex';
}

async function uploaderImageDepuisInput(inputId, type) {
  const input = document.getElementById(inputId);
  const file = input?.files?.[0];
  if (!file) return '';
  const data = new FormData();
  data.append('image', file);
  data.append('type', type);
  const res = await api('upload_image', data);
  return res.url || '';
}

async function creerService() {
  const nom = document.getElementById('ns-nom').value.trim();
  if (!nom) { flash('Le nom du service est obligatoire', 'error'); return; }

  try {
    const uploadedUrl = await uploaderImageDepuisInput('ns-logo-file', 'services');
    await api('creer_service', {
      categorie_id: document.getElementById('ns-cat').value,
      nom,
      description:  document.getElementById('ns-desc').value,
      logo_url:     uploadedUrl || document.getElementById('ns-logo').value,
      adresse:      document.getElementById('ns-adr').value,
      telephone:    document.getElementById('ns-tel').value,
      delai_min:    document.getElementById('ns-dmin').value,
      delai_max:    document.getElementById('ns-dmax').value,
      commande_min: document.getElementById('ns-cmin').value,
    });
    flash('✅ Service créé avec succès', 'success');
    fermerModal('modal-add-svc');
    setTimeout(() => location.reload(), 1200);
  } catch(e) { flash('Erreur : ' + e.message, 'error'); }
}

async function creerProduit() {
  const serviceId = document.getElementById('np-service-id').value;
  const nom = document.getElementById('np-nom').value.trim();
  const prix = parseInt(document.getElementById('np-prix').value || '0', 10);

  if (!serviceId) { flash('Service introuvable pour ce produit', 'error'); return; }
  if (!nom) { flash('Le nom du produit est obligatoire', 'error'); return; }
  if (!prix || prix <= 0) { flash('Le prix du produit est obligatoire', 'error'); return; }

  try {
    const uploadedUrl = await uploaderImageDepuisInput('np-image-file', 'products');
    await api('creer_produit', {
      service_id: serviceId,
      categorie_id: document.getElementById('np-categorie').value,
      nom,
      prix,
      prix_promo: document.getElementById('np-prix-promo').value,
      description: document.getElementById('np-desc').value,
      image_url: uploadedUrl || document.getElementById('np-image').value,
      icone: document.getElementById('np-icone').value || '📦',
    });
    flash('✅ Produit ajouté avec succès', 'success');
    fermerModal('modal-add-product');
    setTimeout(() => location.reload(), 900);
  } catch(e) { flash('Erreur : ' + e.message, 'error'); }
}

async function sauverModifService() {
  const id  = document.getElementById('es-id').value;
  const nom = document.getElementById('es-nom').value.trim();
  if (!nom) { flash('Le nom est obligatoire', 'error'); return; }

  try {
    const uploadedUrl = await uploaderImageDepuisInput('es-logo-file', 'services');
    await api('modifier_service', {
      id,
      nom,
      description:  document.getElementById('es-desc').value,
      logo_url:     uploadedUrl || document.getElementById('es-logo').value,
      adresse:      document.getElementById('es-adr').value,
      telephone:    document.getElementById('es-tel').value,
      delai_min:    document.getElementById('es-dmin').value,
      delai_max:    document.getElementById('es-dmax').value,
      commande_min: document.getElementById('es-cmin').value,
    });
    flash('✅ Service modifié avec succès', 'success');
    fermerModal('modal-edit-svc');
    setTimeout(() => location.reload(), 1200);
  } catch(e) { flash('Erreur : ' + e.message, 'error'); }
}

function toggleService(id, actif) {
  const msg = actif ? '✅ Service activé' : '⏸ Service désactivé';
  api('toggle_service', { id, actif })
    .then(() => { flash(msg, 'success'); setTimeout(() => location.reload(), 1000); })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function genererProduitsService(id, nom) {
  if (!confirm(`Créer automatiquement un catalogue de départ pour "${nom}" ?`)) return;
  api('generer_produits_service', { service_id: id })
    .then(d => {
      flash(`✅ ${d.message}`, 'success');
      setTimeout(() => location.reload(), 1200);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function supprimerService(id, nom, nbCommandes) {
  document.getElementById('suppr-svc-id').value   = id;
  document.getElementById('suppr-svc-nom').textContent = nom;
  document.getElementById('suppr-svc-confirm').value   = '';
  
  const avert = document.getElementById('suppr-svc-avert');
  avert.innerHTML = `<span class="material-symbols-outlined">warning</span> Vous allez purger "${nom}" du système.`;
  
  const warnCmd = document.getElementById('suppr-svc-warning-cmd');
  warnCmd.style.display = nbCommandes > 0 ? 'flex' : 'none';
  
  document.getElementById('modal-suppr-svc').style.display = 'flex';
  setTimeout(() => document.getElementById('suppr-svc-confirm').focus(), 100);
}

function confirmerSuppressionService() {
  const confirmation = document.getElementById('suppr-svc-confirm').value.trim();
  if (confirmation !== 'SUPPRIMER') {
    flash('Veuillez écrire précisément SUPPRIMER', 'error');
    document.getElementById('suppr-svc-confirm').focus();
    return;
  }
  const id = document.getElementById('suppr-svc-id').value;
  api('supprimer_service', { id })
    .then(() => {
      flash('✅ Service supprimé du système', 'success');
      fermerModal('modal-suppr-svc');
      setTimeout(() => location.reload(), 1200);
    })
    .catch(e => flash('Erreur : ' + e.message, 'error'));
}

function escHtml(s) {
  return String(s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
</script>
