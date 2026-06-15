<?php
// ============================================================
//  RAPIDLIV — Point d'entrée principal
//  Fichier : public/index.php
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

demarrerSession();
assurerDocumentsLivreur();

$page = $_GET['page'] ?? 'accueil';
$user = utilisateurConnecte();

// Pages publiques (sans connexion)
$pages_publiques = ['accueil', 'connexion', 'inscription', 'acces_refuse'];

// Redirection si non connecté
if (!in_array($page, $pages_publiques) && !estConnecte()) {
    rediriger('/rapidliv-php/public/index.php?page=connexion');
}

// Les formulaires d'authentification sont inutiles si la session est déjà ouverte.
// La vitrine publique reste toutefois accessible à tout le monde.
if (estConnecte() && in_array($page, ['connexion', 'inscription'], true)) {
    rediriger(urlAccueilUtilisateur($user));
}

// Sécurité rôles
$pages_admin   = ['admin_dashboard','admin_commandes','admin_livreurs','admin_services','admin_stats','admin_parametres'];
$pages_livreur = ['livreur_dashboard','livreur_missions','livreur_historique','livreur_profil'];
$pages_client  = ['accueil_client','client_commandes','client_panier','client_profil','client_shop','client_suivi'];

if (in_array($page, $pages_admin)   && $user['role'] !== 'admin')   rediriger('/rapidliv-php/public/index.php?page=acces_refuse');
if (in_array($page, $pages_livreur) && $user['role'] !== 'livreur') rediriger('/rapidliv-php/public/index.php?page=acces_refuse');
if (in_array($page, $pages_client)  && !in_array($user['role'] ?? '', ['client','livreur','admin'])) rediriger('/rapidliv-php/public/index.php?page=acces_refuse');

// Charger l'en-tête HTML
include __DIR__ . '/../includes/header.php';

// Router vers la bonne page
switch ($page) {
    // PUBLIC
    case 'accueil':          include __DIR__ . '/../pages/accueil.php'; break;
    case 'connexion':        include __DIR__ . '/../pages/connexion.php'; break;
    case 'inscription':      include __DIR__ . '/../pages/inscription.php'; break;
    case 'acces_refuse':     include __DIR__ . '/../pages/acces_refuse.php'; break;

    // CLIENT
    case 'accueil_client':   include __DIR__ . '/../pages/client/accueil.php'; break;
    case 'client_shop':      include __DIR__ . '/../pages/client/shop.php'; break;
    case 'client_panier':    include __DIR__ . '/../pages/client/panier.php'; break;
    case 'client_commandes': include __DIR__ . '/../pages/client/commandes.php'; break;
    case 'client_suivi':     include __DIR__ . '/../pages/client/suivi.php'; break;
    case 'client_profil':    include __DIR__ . '/../pages/client/profil.php'; break;

    // LIVREUR
    case 'livreur_dashboard': include __DIR__ . '/../pages/livreur/dashboard.php'; break;
    case 'livreur_missions':  include __DIR__ . '/../pages/livreur/missions.php'; break;
    case 'livreur_historique':include __DIR__ . '/../pages/livreur/historique.php'; break;
    case 'livreur_profil':    include __DIR__ . '/../pages/livreur/profil.php'; break;

    // ADMIN
    case 'admin_dashboard':   include __DIR__ . '/../pages/admin/dashboard.php'; break;
    case 'admin_commandes':   include __DIR__ . '/../pages/admin/commandes.php'; break;
    case 'admin_livreurs':    include __DIR__ . '/../pages/admin/livreurs.php'; break;
    case 'admin_services':    include __DIR__ . '/../pages/admin/services.php'; break;
    case 'admin_stats':       include __DIR__ . '/../pages/admin/stats.php'; break;
    case 'admin_parametres':  include __DIR__ . '/../pages/admin/parametres.php'; break;

    default:
        http_response_code(404);
        echo '<div class="container" style="text-align:center;padding:80px"><h2>Page introuvable</h2><a href="index.php" class="btn btn-primary">Retour accueil</a></div>';
}

include __DIR__ . '/../includes/footer.php';
