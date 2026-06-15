<?php
// pages/accueil.php
$produits_disponibles = Database::query(
    "SELECT p.*, s.nom AS service_nom
     FROM produits p
     JOIN services s ON s.id=p.service_id
     WHERE p.disponible=1 AND s.actif=1
     ORDER BY p.cree_le DESC, p.nom
     LIMIT 12"
);

function accueilPublicProduitImageUrl(array $produit): string {
    $image = trim($produit['image_url'] ?? '');
    if ($image !== '') return $image;
    return 'https://loremflickr.com/480/360/' . rawurlencode(($produit['nom'] ?? 'produit') . ',product');
}

$utilisateur_accueil = utilisateurConnecte();
$url_espace = urlAccueilUtilisateur($utilisateur_accueil);
$peut_acheter = in_array($utilisateur_accueil['role'] ?? '', ['client', 'livreur'], true);
$url_commander = $peut_acheter
    ? 'index.php?page=accueil_client'
    : ($utilisateur_accueil ? $url_espace : 'index.php?page=connexion');
$url_connexion = 'index.php?page=connexion';
?>
<section style="min-height:520px;border-radius:18px;overflow:hidden;position:relative;background:#0f172a;margin-bottom:36px">
  <img
    src="https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=1600&q=80"
    alt="Livreur préparant une commande en ville"
    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.72">
  <div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(15,23,42,.88),rgba(15,23,42,.52),rgba(15,23,42,.18))"></div>
  <div style="position:relative;z-index:1;max-width:640px;padding:72px 48px;color:#fff">
    <div class="logo" style="font-size:42px;display:block;margin-bottom:18px;color:#fff">Rapid<span>Liv</span></div>
    <h1 style="font-size:44px;line-height:1.05;font-weight:800;margin-bottom:16px;letter-spacing:0">Livraison locale rapide à Dakar</h1>
    <p style="font-size:18px;color:rgba(255,255,255,.86);max-width:560px;margin-bottom:28px">
      Restaurants, supermarchés, pharmacies et accessoires utiles, livrés avec suivi clair et livreurs identifiés.
    </p>
    <div class="flex gap-12" style="flex-wrap:wrap">
      <a href="<?= sanitize($url_commander) ?>" class="btn btn-primary btn-lg">
        <?= $peut_acheter ? 'Commander maintenant' : ($utilisateur_accueil ? 'Accéder à mon espace' : 'Se connecter pour commander') ?>
      </a>
      <?php if (!$utilisateur_accueil): ?>
      <a href="<?= sanitize($url_connexion) ?>" class="btn btn-outline btn-lg" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.45)">Se connecter</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="grid-4 mb-24" id="zones-livraison">
  <?php foreach ([
    ['35 min', 'Délai moyen', 'Sur les commandes urbaines'],
    ['4.8/5', 'Satisfaction', 'Services vérifiés'],
    ['7j/7', 'Disponibilité', 'Selon les horaires partenaires'],
    ['Dakar', 'Zone active', 'Plateau, Almadies, Ouakam'],
  ] as [$val,$titre,$desc]): ?>
  <div class="card-sm">
    <div class="metric-value" style="font-size:24px"><?= $val ?></div>
    <div class="fw-700 mt-8"><?= $titre ?></div>
    <div class="text-sm text-muted"><?= $desc ?></div>
  </div>
  <?php endforeach; ?>
</section>

<section style="margin-bottom:42px" id="services">
  <div class="section-title">Ce que vous pouvez commander</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px">
    <?php foreach ([
      ['Courses', 'Supermarché, épicerie, produits frais', 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80'],
      ['Pharmacie', 'Médicaments courants et parapharmacie', 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=700&q=80'],
      ['Restaurant', 'Plats sénégalais et menus du jour', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=700&q=80'],
      ['Électronique', 'Chargeurs, câbles et accessoires mobiles', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=700&q=80'],
    ] as [$titre,$desc,$img]): ?>
    <article class="shop-card">
      <div class="shop-img"><img src="<?= $img ?>" alt="<?= sanitize($titre) ?>"></div>
      <div class="shop-body">
        <div class="shop-name"><?= sanitize($titre) ?></div>
        <div class="shop-meta"><?= sanitize($desc) ?></div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($produits_disponibles): ?>
<section style="margin-bottom:42px" id="produits">
  <div class="section-title">Produits disponibles</div>
  <div class="grid-auto">
    <?php foreach ($produits_disponibles as $produit): ?>
    <a href="<?= sanitize(
        $peut_acheter
            ? 'index.php?page=client_shop&id=' . (int)$produit['service_id']
            : ($utilisateur_accueil ? $url_espace : $url_connexion)
    ) ?>" class="shop-card">
      <div class="shop-img">
        <img src="<?= sanitize(accueilPublicProduitImageUrl($produit)) ?>"
             alt="<?= sanitize($produit['nom']) ?>"
             loading="lazy"
             onerror="this.parentElement.textContent=<?= htmlspecialchars(json_encode($produit['icone'] ?: '📦'), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="shop-body">
        <div class="shop-name"><?= sanitize($produit['nom']) ?></div>
        <div class="shop-meta"><?= sanitize($produit['service_nom']) ?></div>
        <div class="product-price">
          <?php if ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix']): ?>
            <span style="text-decoration:line-through;color:var(--text3);font-weight:400"><?= formatMontant($produit['prix']) ?></span>
            <span style="color:var(--accent)"><?= formatMontant($produit['prix_promo']) ?></span>
          <?php else: ?>
            <?= formatMontant($produit['prix']) ?>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="partner-strip" id="devenir-livreur">
  <div>
    <div style="font-size:24px;font-weight:800;margin-bottom:8px;color:var(--text)">Devenez livreur partenaire</div>
    <div style="color:var(--text-medium);margin-bottom:20px">Recevez des missions proches de votre zone, avec code de confirmation et historique clair des livraisons.</div>
    <a href="<?= sanitize($utilisateur_accueil ? $url_espace : 'index.php?page=inscription') ?>" class="btn btn-primary">
      <?= $utilisateur_accueil ? 'Accéder à mon espace' : 'Rejoindre RapidLiv' ?>
    </a>
  </div>
  <img src="https://images.unsplash.com/photo-1593950315186-76a92975b60c?auto=format&fit=crop&w=700&q=80" alt="Coursier en moto" style="width:100%;height:220px;object-fit:cover;border-radius:12px">
</section>
