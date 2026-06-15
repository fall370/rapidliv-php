<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?> — Livraison rapide à Dakar</title>
<link rel="stylesheet" href="/rapidliv-php/public/css/app.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
</head>
<body>

<?php
$user = utilisateurConnecte();
$page = $_GET['page'] ?? 'accueil';
$nonconnecte = in_array($page, ['accueil', 'connexion', 'inscription', 'acces_refuse']);
$logo_url = 'index.php?page=accueil';

// Compter notifications non lues
$nb_notifs = 0;
if ($user) {
    $row = Database::queryOne(
        "SELECT COUNT(*) AS nb FROM notifications WHERE utilisateur_id = ? AND lu = 0",
        [$user['id']]
    );
    $nb_notifs = $row['nb'] ?? 0;
}
?>

<header class="topbar">
  <div class="topbar-inner">
    <a href="<?= sanitize($logo_url) ?>" class="logo" aria-label="RapidLiv">
      <span class="logo-mark"><i class="fa-solid fa-bolt"></i></span>
      <span class="logo-text">Rapid<span>Liv</span></span>
    </a>

    <?php if ($user): ?>
    <!-- NAV CONNECTÉ -->
    <nav class="main-nav" id="main-nav">
      <?php if ($user['role'] === 'client'): ?>
        <a href="index.php?page=accueil_client"  class="nav-link <?= $page==='accueil_client'?'active':'' ?>">Accueil</a>
        <a href="index.php?page=client_commandes" class="nav-link <?= $page==='client_commandes'?'active':'' ?>">Mes commandes</a>
        <a href="index.php?page=client_panier"   class="nav-link <?= $page==='client_panier'?'active':'' ?>">
          Panier <span class="cart-badge" id="cart-count">0</span>
        </a>
      <?php elseif ($user['role'] === 'livreur'): ?>
        <a href="index.php?page=livreur_dashboard"  class="nav-link <?= $page==='livreur_dashboard'?'active':'' ?>">Tableau de bord</a>
        <a href="index.php?page=livreur_missions"   class="nav-link <?= $page==='livreur_missions'?'active':'' ?>">Missions</a>
        <a href="index.php?page=livreur_historique" class="nav-link <?= $page==='livreur_historique'?'active':'' ?>">Historique</a>
        <a href="index.php?page=accueil_client"     class="nav-link <?= in_array($page,['accueil_client','client_shop'])?'active':'' ?>">Acheter</a>
        <a href="index.php?page=client_commandes"  class="nav-link <?= in_array($page,['client_commandes','client_suivi'])?'active':'' ?>">Mes achats</a>
        <a href="index.php?page=client_panier"      class="nav-link <?= $page==='client_panier'?'active':'' ?>">
          Panier <span class="cart-badge" id="cart-count">0</span>
        </a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <a href="index.php?page=admin_dashboard"  class="nav-link <?= $page==='admin_dashboard'?'active':'' ?>">Dashboard</a>
        <a href="index.php?page=admin_commandes"  class="nav-link <?= $page==='admin_commandes'?'active':'' ?>">Commandes</a>
        <a href="index.php?page=admin_livreurs"   class="nav-link <?= $page==='admin_livreurs'?'active':'' ?>">Livreurs</a>
        <a href="index.php?page=admin_services"   class="nav-link <?= $page==='admin_services'?'active':'' ?>">Services</a>
        <a href="index.php?page=admin_stats"      class="nav-link <?= $page==='admin_stats'?'active':'' ?>">Stats</a>
      <?php endif; ?>
    </nav>

    <div class="topbar-right">
      <!-- Notifications -->
      <div class="notif-wrap">
        <button class="icon-btn" onclick="toggleNotifs()" title="Notifications">
          <i class="fa-regular fa-bell"></i>
          <?php if ($nb_notifs > 0): ?><span class="notif-dot"><?= $nb_notifs ?></span><?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notif-dropdown" style="display:none">
          <div class="notif-header">Notifications</div>
          <div id="notif-list">Chargement...</div>
          <a href="#" onclick="marquerToutLu()" class="notif-footer">Tout marquer comme lu</a>
        </div>
      </div>
      <!-- Profil -->
      <div class="user-menu-wrap">
        <button class="user-btn" onclick="toggleUserMenu()">
          <div class="avatar-sm"><?= strtoupper(substr($user['nom'], 0, 2)) ?></div>
          <span class="user-name"><?= sanitize($user['nom']) ?></span>
          <i class="fa-solid fa-chevron-down user-chevron"></i>
        </button>
        <div class="user-dropdown" id="user-dropdown" style="display:none">
          <div class="dropdown-header"><?= sanitize($user['nom']) ?></div>
          <div class="dropdown-role badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></div>
          <?php if ($user['role']==='client'):?>
            <a href="index.php?page=client_profil" class="dropdown-link">Mon profil</a>
          <?php elseif ($user['role']==='livreur'):?>
            <a href="index.php?page=livreur_profil" class="dropdown-link">Mon profil</a>
          <?php elseif ($user['role']==='admin'):?>
            <a href="index.php?page=admin_parametres" class="dropdown-link">Paramètres</a>
          <?php endif;?>
          <a href="/rapidliv-php/api/auth.php?action=deconnexion" class="dropdown-link text-danger">Déconnexion</a>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- NAV NON CONNECTÉ -->
    <div class="topbar-right">
      <a href="index.php?page=connexion"   class="btn btn-outline btn-sm">Connexion</a>
      <a href="index.php?page=inscription" class="btn btn-primary btn-sm">S'inscrire</a>
    </div>
    <?php endif; ?>
  </div>
</header>

<?php if (isset($_SESSION['flash'])): ?>
<div class="flash flash-<?= $_SESSION['flash']['type'] ?>">
  <?= sanitize($_SESSION['flash']['message']) ?>
  <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<main class="main-content">
