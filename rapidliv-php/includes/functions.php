<?php
// ============================================================
//  RAPIDLIV — Fonctions utilitaires
//  Fichier : includes/functions.php
// ============================================================

// ---- SESSION -----------------------------------------------
function demarrerSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'secure'   => false,   // true en HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function estConnecte(): bool {
    return isset($_SESSION['user_id']);
}

function utilisateurConnecte(): ?array {
    if (!estConnecte()) return null;
    $user = [
        'id'    => $_SESSION['user_id'],
        'nom'   => $_SESSION['user_nom'] ?? '',
        'role'  => strtolower(trim($_SESSION['user_role'] ?? '')),
        'email' => $_SESSION['user_email'] ?? '',
    ];

    if (class_exists('Database')) {
        try {
            $row = Database::queryOne(
                "SELECT id, nom, prenom, email, role FROM utilisateurs WHERE id=? AND actif=1",
                [$user['id']]
            );
            if ($row) {
                $_SESSION['user_nom']   = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
                $_SESSION['user_role']  = strtolower(trim($row['role'] ?? ''));
                $_SESSION['user_email'] = $row['email'] ?? '';
                return [
                    'id'    => $row['id'],
                    'nom'   => $_SESSION['user_nom'],
                    'role'  => $_SESSION['user_role'],
                    'email' => $_SESSION['user_email'],
                ];
            }
        } catch (Throwable $e) {
            // Garder la session existante si la BDD est momentanément indisponible.
        }
    }

    return $user;
}

function connecterUtilisateur(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nom']   = $user['prenom'] . ' ' . $user['nom'];
    $_SESSION['user_role']  = strtolower(trim($user['role']));
    $_SESSION['user_email'] = $user['email'];
}

function estSuperAdmin(): bool {
    $user = utilisateurConnecte();
    if (!$user || ($user['role'] ?? '') !== 'admin') return false;

    if (!class_exists('Database') || !Database::columnExists('utilisateurs', 'super_admin')) {
        return true;
    }

    $row = Database::queryOne("SELECT super_admin FROM utilisateurs WHERE id=?", [$user['id']]);
    return !empty($row['super_admin']);
}

function aDroit(string $droit): bool {
    $user = utilisateurConnecte();
    if (!$user || ($user['role'] ?? '') !== 'admin') return false;
    if (estSuperAdmin()) return true;

    if (!class_exists('Database') || !Database::columnExists('utilisateurs', 'droits_json')) {
        return true;
    }

    $row = Database::queryOne("SELECT droits_json FROM utilisateurs WHERE id=?", [$user['id']]);
    if (empty($row['droits_json'])) {
        return true;
    }

    $droits = json_decode($row['droits_json'], true);
    return is_array($droits) && in_array($droit, $droits, true);
}

function deconnecterUtilisateur(): void {
    session_unset();
    session_destroy();
}

function urlAccueilUtilisateur(?array $user = null): string {
    $role = strtolower(trim($user['role'] ?? $_SESSION['user_role'] ?? ''));

    return match ($role) {
        'admin'   => 'index.php?page=admin_dashboard',
        'livreur' => 'index.php?page=livreur_dashboard',
        'client'  => 'index.php?page=accueil_client',
        default   => 'index.php?page=accueil',
    };
}

function exigerConnexion(string ...$roles): void {
    if (!estConnecte()) {
        rediriger('/public/index.php?page=connexion');
    }
    if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
        rediriger('/public/index.php?page=acces_refuse');
    }
}

// ---- SÉCURITÉ ----------------------------------------------
function hasherMotDePasse(string $mdp): string {
    return password_hash($mdp, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verifierMotDePasse(string $mdp, string $hash): bool {
    return password_verify($mdp, $hash);
}

function genererToken(int $longueur = 32): string {
    return bin2hex(random_bytes($longueur));
}

function genererCodeConfirmation(): string {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

function genererReference(): string {
    return 'CMD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function validerEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validerTelephone(string $tel): bool {
    return (bool) preg_match('/^\+?[0-9]{8,15}$/', preg_replace('/\s/', '', $tel));
}

function assurerDocumentsLivreur(): void {
    $colonnes = [
        'carte_identite_fichier' => "VARCHAR(255) DEFAULT NULL AFTER numero_assurance",
        'documents_statut'       => "ENUM('en_attente','valides','rejetes') NOT NULL DEFAULT 'en_attente' AFTER documents_valides",
        'documents_motif'        => "VARCHAR(500) DEFAULT NULL AFTER documents_statut",
        'documents_verifies_le'  => "DATETIME DEFAULT NULL AFTER documents_motif",
        'documents_verifies_par' => "CHAR(36) DEFAULT NULL AFTER documents_verifies_le",
    ];

    foreach ($colonnes as $colonne => $definition) {
        if (!Database::columnExists('livreurs', $colonne)) {
            Database::execute("ALTER TABLE livreurs ADD COLUMN $colonne $definition");
        }
    }

    Database::execute(
        "UPDATE livreurs
         SET documents_statut=CASE WHEN documents_valides=1 THEN 'valides' ELSE documents_statut END"
    );
    Database::execute(
        "UPDATE livreurs l
         JOIN utilisateurs u ON u.id=l.id
         SET l.documents_valides=0,
             l.documents_statut='en_attente',
             l.documents_motif=NULL,
             l.statut=CASE WHEN l.statut='en_course' THEN l.statut ELSE 'hors_ligne' END
         WHERE l.documents_valides=1
           AND (u.photo_url IS NULL OR u.photo_url='' OR l.carte_identite_fichier IS NULL OR l.carte_identite_fichier='')"
    );
}

function enregistrerImageUpload(array $fichier, string $dossier, string $prefixe, int $tailleMax = 5242880): string {
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || empty($fichier['tmp_name'])
        || !is_uploaded_file($fichier['tmp_name'])) {
        throw new RuntimeException('Fichier image manquant ou invalide');
    }
    if (($fichier['size'] ?? 0) > $tailleMax) {
        throw new RuntimeException('Image trop lourde (5 Mo maximum)');
    }

    $info = @getimagesize($fichier['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!$info || !isset($extensions[$info['mime']])) {
        throw new RuntimeException('Format accepté : JPG, PNG ou WEBP');
    }

    if (!is_dir($dossier) && !mkdir($dossier, 0775, true)) {
        throw new RuntimeException('Impossible de créer le dossier des documents');
    }
    if (!is_writable($dossier)) {
        throw new RuntimeException('Le dossier des documents n’est pas accessible en écriture');
    }

    $nom = $prefixe . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$info['mime']];
    if (!move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nom)) {
        throw new RuntimeException('Impossible d’enregistrer le fichier');
    }
    @chmod($dossier . '/' . $nom, 0640);
    return $nom;
}

// ---- RÉPONSES JSON (pour l'API) ----------------------------
function jsonReponse(mixed $data, int $code = 200): never {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonErreur(string $message, int $code = 400): never {
    jsonReponse(['erreur' => $message], $code);
}

function jsonSucces(string $message, array $extra = []): never {
    jsonReponse(array_merge(['succes' => true, 'message' => $message], $extra));
}

// ---- REDIRECTION -------------------------------------------
function rediriger(string $url): never {
    header('Location: ' . $url);
    exit;
}

// ---- FORMAT ------------------------------------------------
function formatMontant(int $montant): string {
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

function formatDate(string $date): string {
    return date('d/m/Y à H:i', strtotime($date));
}

function dateRelative(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)   return 'à l\'instant';
    if ($diff < 3600) return floor($diff/60) . ' min';
    if ($diff < 86400) return floor($diff/3600) . 'h';
    return date('d/m/Y', strtotime($date));
}

function statutBadgeHTML(string $statut): string {
    $map = [
        'en_attente'      => ['bg-gray-100 text-gray-600',    'En attente'],
        'confirmee'       => ['bg-blue-100 text-blue-700',    'Confirmée'],
        'en_preparation'  => ['bg-amber-100 text-amber-700',  'En préparation'],
        'en_route'        => ['bg-indigo-100 text-indigo-700','En route'],
        'livree'          => ['bg-green-100 text-green-700',  'Livrée'],
        'annulee'         => ['bg-red-100 text-red-700',      'Annulée'],
        'disponible'      => ['bg-green-100 text-green-700',  'Disponible'],
        'en_course'       => ['bg-amber-100 text-amber-700',  'En course'],
        'hors_ligne'      => ['bg-gray-100 text-gray-500',    'Hors ligne'],
    ];
    [$classe, $label] = $map[$statut] ?? ['bg-gray-100 text-gray-500', $statut];
    return "<span class=\"badge {$classe}\">{$label}</span>";
}

// ---- CSRF --------------------------------------------------
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = genererToken(16);
    }
    return $_SESSION['csrf_token'];
}

function verifierCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonErreur('Token CSRF invalide', 403);
    }
}

function assurerProduitCommandeNullable(): void {
    if (!Database::columnExists('commande_items', 'produit_id')) {
        return;
    }

    try {
        Database::execute("ALTER TABLE commande_items MODIFY produit_id INT UNSIGNED DEFAULT NULL");
    } catch (Exception $e) {
        $fk = Database::queryOne(
            "SELECT CONSTRAINT_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commande_items'
               AND COLUMN_NAME = 'produit_id'
               AND REFERENCED_TABLE_NAME = 'produits'
             LIMIT 1"
        );
        $fkName = $fk['CONSTRAINT_NAME'] ?? '';

        if ($fkName !== '') {
            Database::execute("ALTER TABLE commande_items DROP FOREIGN KEY `$fkName`");
        }

        Database::execute("ALTER TABLE commande_items MODIFY produit_id INT UNSIGNED DEFAULT NULL");
        Database::execute(
            "ALTER TABLE commande_items
             ADD CONSTRAINT commande_items_produit_id_fk
             FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL"
        );
    }
}

function parametreValeur(string $cle, mixed $defaut = null): mixed {
    if (!class_exists('Database')) return $defaut;

    try {
        $row = Database::queryOne("SELECT valeur FROM parametres WHERE cle=?", [$cle]);
        return $row['valeur'] ?? $defaut;
    } catch (Throwable $e) {
        return $defaut;
    }
}

function choisirLivreurAutomatique(int $serviceId, ?string $livreurExcluId = null): ?array {
    return Database::queryOne(
        "SELECT l.id, CONCAT(u.prenom,' ',u.nom) AS nom
         FROM livreurs l
         JOIN utilisateurs u ON u.id=l.id
         LEFT JOIN services s ON s.id=?
         WHERE l.statut='disponible'
           AND l.documents_valides=1
           AND u.actif=1
           AND (? IS NULL OR l.id<>?)
         ORDER BY
           CASE WHEN s.zone_id IS NOT NULL AND l.zone_id=s.zone_id THEN 0 ELSE 1 END,
           l.note_moyenne DESC,
           l.nb_livraisons_total ASC,
           l.position_maj_le DESC
         LIMIT 1",
        [$serviceId, $livreurExcluId, $livreurExcluId]
    );
}

function assignerLivreurACommande(string $commandeId, string $livreurId, int $fraisLivraison, string $reference = ''): array {
    $commande = Database::queryOne(
        "SELECT id, reference, statut, client_id FROM commandes WHERE id=?",
        [$commandeId]
    );
    if (!$commande) {
        throw new RuntimeException('Commande introuvable');
    }
    if (in_array($commande['statut'], ['livree', 'annulee'], true)) {
        throw new RuntimeException('Commande déjà terminée');
    }
    if (Database::queryOne("SELECT id FROM livraisons WHERE commande_id=?", [$commandeId])) {
        throw new RuntimeException('Cette commande a déjà un livreur assigné');
    }
    if ($commande['client_id'] === $livreurId) {
        throw new RuntimeException('Un livreur ne peut pas livrer sa propre commande');
    }

    $livreur = Database::queryOne(
        "SELECT l.id, CONCAT(u.prenom,' ',u.nom) AS nom
         FROM livreurs l
         JOIN utilisateurs u ON u.id=l.id
         WHERE l.id=? AND l.statut='disponible' AND l.documents_valides=1 AND u.actif=1",
        [$livreurId]
    );
    if (!$livreur) {
        throw new RuntimeException('Livreur indisponible ou documents non validés');
    }

    $commission = (int)parametreValeur('commission', COMMISSION_LIVREUR);
    $gain = (int)round($fraisLivraison * $commission / 100);
    $code = genererCodeConfirmation();
    $livraisonId = uniqid('liv-', true);
    $ref = $reference ?: ($commande['reference'] ?? '');

    Database::execute(
        "INSERT INTO livraisons (id,commande_id,livreur_id,statut,code_confirmation,gain_livreur)
         VALUES (?,?,?,'assignee',?,?)",
        [$livraisonId, $commandeId, $livreurId, $code, $gain]
    );
    Database::execute("UPDATE livreurs SET statut='en_course' WHERE id=?", [$livreurId]);
    Database::execute("UPDATE commandes SET statut='confirmee', confirmee_le=COALESCE(confirmee_le,NOW()) WHERE id=?", [$commandeId]);
    Database::execute(
        "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json)
         VALUES (?,?,'commande','Nouvelle mission assignée',?,?)",
        [
            uniqid('n-', true),
            $livreurId,
            $ref ? "La commande $ref vous a été assignée automatiquement." : 'Une commande vous a été assignée.',
            json_encode(['commande_id' => $commandeId, 'reference' => $ref, 'livraison_id' => $livraisonId], JSON_UNESCAPED_UNICODE)
        ]
    );

    return [
        'livraison_id' => $livraisonId,
        'livreur_id' => $livreurId,
        'livreur_nom' => $livreur['nom'],
        'code_confirmation' => $code,
    ];
}

function assignerCommandeAutomatiquement(
    string $commandeId,
    int $serviceId,
    int $fraisLivraison,
    string $reference = '',
    ?string $livreurExcluId = null
): ?array {
    if ((string)parametreValeur('auto_assign_livreur', '1') !== '1') {
        return null;
    }

    $livreur = choisirLivreurAutomatique($serviceId, $livreurExcluId);
    if (!$livreur) {
        return null;
    }

    return assignerLivreurACommande($commandeId, $livreur['id'], $fraisLivraison, $reference);
}

// ---- PAGINATION --------------------------------------------
function paginer(int $total, int $parPage, int $pageCourante): array {
    $totalPages = (int) ceil($total / $parPage);
    $offset     = ($pageCourante - 1) * $parPage;
    return [
        'total'       => $total,
        'par_page'    => $parPage,
        'page'        => $pageCourante,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'a_suivant'   => $pageCourante < $totalPages,
        'a_precedent' => $pageCourante > 1,
    ];
}
