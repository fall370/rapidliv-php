<?php
// ============================================================
//  RAPIDLIV — Diagnostic commande (à supprimer en production)
//  Accès : api/debug_commande.php
//  Envoyer en POST JSON : {"service_id":1,"adresse_livraison":"test","items":[{"produit_id":1,"quantite":1}]}
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Seulement en développement
if (ENV !== 'development') {
    http_response_code(403);
    echo json_encode(['erreur' => 'Non disponible en production']);
    exit;
}

demarrerSession();

$raw   = file_get_contents('php://input');
$input = $raw ? (json_decode($raw, true) ?? []) : [];

$diagnostic = [
    'session'     => [
        'connecte'    => isset($_SESSION['user_id']),
        'user_id'     => $_SESSION['user_id']    ?? null,
        'user_role'   => $_SESSION['user_role']  ?? null,
        'user_nom'    => $_SESSION['user_nom']   ?? null,
    ],
    'input_recu'  => $input,
    'checks'      => [],
    'erreurs'     => [],
];

// Check session
if (!isset($_SESSION['user_id'])) {
    $diagnostic['erreurs'][] = 'Pas connecté — session vide';
}
if (($_SESSION['user_role'] ?? '') !== 'client') {
    $diagnostic['erreurs'][] = "Rôle incorrect : '{$_SESSION['user_role']}' (doit être 'client')";
}

// Check service_id
$service_id = (int)($input['service_id'] ?? 0);
if (!$service_id) {
    $diagnostic['erreurs'][] = 'service_id manquant ou 0';
} else {
    $s = Database::queryOne("SELECT id, nom, actif FROM services WHERE id=?", [$service_id]);
    $diagnostic['checks']['service'] = $s ?: 'INTROUVABLE';
    if (!$s) $diagnostic['erreurs'][] = "Service #$service_id introuvable en BDD";
    if ($s && !$s['actif']) $diagnostic['erreurs'][] = "Service #$service_id est désactivé";
}

// Check adresse
$adresse = trim($input['adresse_livraison'] ?? $input['adresse'] ?? '');
$diagnostic['checks']['adresse'] = $adresse ?: '(VIDE)';
if (!$adresse) $diagnostic['erreurs'][] = 'adresse_livraison vide ou manquante';

// Check items
$items = $input['items'] ?? null;
$diagnostic['checks']['items_type']  = gettype($items);
$diagnostic['checks']['items_count'] = is_array($items) ? count($items) : 'N/A';
if ($items === null) $diagnostic['erreurs'][] = "Champ 'items' absent de la requête";
elseif (!is_array($items)) $diagnostic['erreurs'][] = "'items' n'est pas un tableau (reçu: ".gettype($items).')';
elseif (empty($items)) $diagnostic['erreurs'][] = "'items' est un tableau vide []";
else {
    foreach ($items as $idx => $item) {
        $pid = (int)($item['produit_id'] ?? 0);
        $qty = (int)($item['quantite']   ?? 0);
        if (!$pid) {
            $diagnostic['erreurs'][] = "Item[$idx] : produit_id manquant ou 0";
            continue;
        }
        if (!$qty) {
            $diagnostic['erreurs'][] = "Item[$idx] : quantite manquante ou 0";
        }
        $p = $service_id ? Database::queryOne(
            "SELECT id, nom, prix, disponible, service_id FROM produits WHERE id=?", [$pid]) : null;
        $diagnostic['checks']["produit_$idx"] = $p ?: "INTROUVABLE (id=$pid)";
        if ($p && (int)$p['service_id'] !== $service_id) {
            $diagnostic['erreurs'][] = "Item[$idx] produit #$pid appartient au service {$p['service_id']}, pas $service_id";
        }
        if ($p && !$p['disponible']) {
            $diagnostic['erreurs'][] = "Item[$idx] produit '{$p['nom']}' est marqué non disponible";
        }
    }
}

$diagnostic['resultat'] = empty($diagnostic['erreurs']) ? '✅ Tout est correct' : '❌ '.count($diagnostic['erreurs']).' erreur(s) trouvée(s)';

echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
