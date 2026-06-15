<?php
// ============================================================
//  RAPIDLIV — Configuration globale
//  Fichier : includes/config.php
// ============================================================

// Environnement : 'development' ou 'production'
define('ENV', 'development');

// Base de données MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'rapidliv');
define('DB_USER', 'root');          // Changer en production
define('DB_PASS', '');              // Changer en production
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_NAME', 'RapidLiv');
define('APP_URL',  'http://localhost/rapidliv-php/public'); // Changer en production
define('APP_VERSION', '1.0.0');

// Sécurité
define('JWT_SECRET', 'rapidliv_secret_2024_changez_moi');
define('SESSION_NAME', 'rapidliv_session');
define('BCRYPT_COST', 12);

// Livraison
define('FRAIS_LIVRAISON_BASE', 1500);   // FCFA
define('COMMISSION_LIVREUR',   10);     // %
define('RAYON_MAX_KM',         15);

// Timezone
date_default_timezone_set('Africa/Dakar');

// Affichage erreurs (désactiver en production)
if (ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
