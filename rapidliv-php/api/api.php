<?php
// ============================================================
//  RAPIDLIV — API AJAX unifiée
//  Fichier : api/api.php
//  Usage   : api/api.php?action=NOM_ACTION (POST/GET)
// ============================================================

ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

demarrerSession();
assurerDocumentsLivreur();
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$user   = utilisateurConnecte();

// Données JSON envoyées
$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw) $input = json_decode($raw, true) ?? [];
    $input = array_merge($input, $_POST);
}

try {
    switch ($action) {

        // ====================================================
        //  AUTH
        // ====================================================
        case 'connexion':
            $email = trim($input['email'] ?? '');
            $mdp   = $input['mot_de_passe'] ?? '';
            if (!$email || !$mdp) jsonErreur('Email et mot de passe requis');

            $row = Database::queryOne(
                "SELECT * FROM utilisateurs WHERE email = ? AND actif = 1", [$email]);
            if (!$row || !verifierMotDePasse($mdp, $row['mot_de_passe']))
                jsonErreur('Identifiants incorrects', 401);

            connecterUtilisateur($row);
            $redirect = urlAccueilUtilisateur($row);
            jsonReponse(['succes' => true, 'redirect' => $redirect, 'role' => $row['role']]);

        case 'inscription':
            $nom      = trim($input['nom'] ?? '');
            $prenom   = trim($input['prenom'] ?? '');
            $email    = trim($input['email'] ?? '');
            $tel      = trim($input['telephone'] ?? '');
            $mdp      = $input['mot_de_passe'] ?? '';
            $role     = in_array($input['role'] ?? '', ['client','livreur']) ? $input['role'] : 'client';

            if (!$nom||!$prenom||!$email||!$tel||!$mdp) jsonErreur('Tous les champs sont requis');
            if (!validerEmail($email)) jsonErreur('Email invalide');
            if (strlen($mdp) < 6) jsonErreur('Mot de passe trop court (6 car. min)');

            $existe = Database::queryOne(
                "SELECT id FROM utilisateurs WHERE email=? OR telephone=?", [$email,$tel]);
            if ($existe) jsonErreur('Email ou téléphone déjà utilisé');

            $id   = uniqid('u-', true);
            $hash = hasherMotDePasse($mdp);
            $photoNom = null;
            $identiteNom = null;
            $photoUrl = null;

            if ($role === 'livreur') {
                if (empty($_FILES['photo_profil']) || empty($_FILES['carte_identite'])) {
                    jsonErreur('La photo de profil et la carte d’identité sont obligatoires');
                }
                try {
                    $photoNom = enregistrerImageUpload(
                        $_FILES['photo_profil'],
                        __DIR__ . '/../public/uploads/livreurs',
                        'profil'
                    );
                    $identiteNom = enregistrerImageUpload(
                        $_FILES['carte_identite'],
                        __DIR__ . '/../storage/livreurs',
                        'identite'
                    );
                    $photoUrl = '/rapidliv-php/public/uploads/livreurs/' . $photoNom;
                } catch (RuntimeException $e) {
                    if ($photoNom) @unlink(__DIR__ . '/../public/uploads/livreurs/' . $photoNom);
                    if ($identiteNom) @unlink(__DIR__ . '/../storage/livreurs/' . $identiteNom);
                    jsonErreur($e->getMessage());
                }
            }

            Database::beginTransaction();
            try {
                Database::execute(
                    "INSERT INTO utilisateurs (id,nom,prenom,email,telephone,mot_de_passe,role,photo_url)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$id,$nom,$prenom,$email,$tel,$hash,$role,$photoUrl]
                );

                if ($role === 'livreur') {
                    Database::execute(
                        "INSERT INTO livreurs
                         (id,carte_identite_fichier,documents_valides,documents_statut,statut)
                         VALUES (?,?,0,'en_attente','hors_ligne')",
                        [$id,$identiteNom]
                    );
                }
                Database::commit();
            } catch (Throwable $e) {
                Database::rollback();
                if ($photoNom) @unlink(__DIR__ . '/../public/uploads/livreurs/' . $photoNom);
                if ($identiteNom) @unlink(__DIR__ . '/../storage/livreurs/' . $identiteNom);
                jsonErreur('Impossible de créer le compte', 500);
            }

            $message = $role === 'livreur'
                ? 'Compte créé. Vos documents sont en attente de validation par un administrateur.'
                : 'Compte créé avec succès !';
            jsonSucces($message, ['redirect' => 'index.php?page=connexion']);

        // ====================================================
        //  SERVICES
        // ====================================================
        case 'services_liste':
            $cat = $_GET['categorie'] ?? '';
            $rech = $_GET['recherche'] ?? '';
            $sql = "SELECT s.*, cs.nom AS cat_nom, cs.icone AS cat_icone
                    FROM services s JOIN categories_service cs ON cs.id=s.categorie_id
                    WHERE s.actif=1";
            $p = [];
            if ($cat)  { $sql .= " AND cs.nom = ?"; $p[] = $cat; }
            if ($rech) { $sql .= " AND s.nom LIKE ?"; $p[] = "%$rech%"; }
            $sql .= " ORDER BY s.note_moyenne DESC";
            jsonReponse(Database::query($sql, $p));

        case 'service_detail':
            $id = (int)($_GET['id'] ?? 0);
            $s  = Database::queryOne("SELECT s.*, cs.nom AS cat_nom FROM services s JOIN categories_service cs ON cs.id=s.categorie_id WHERE s.id=?", [$id]);
            if (!$s) jsonErreur('Service introuvable', 404);
            $produits = Database::query(
                "SELECT p.*, cp.nom AS cat_produit FROM produits p
                 LEFT JOIN categories_produit cp ON cp.id=p.categorie_id
                 WHERE p.service_id=? AND p.disponible=1 ORDER BY cp.ordre, p.nom", [$id]);
            $s['produits'] = $produits;
            jsonReponse($s);

        // ====================================================
        //  COMMANDES
        // ====================================================
        case 'commandes_liste':
            if (!$user) jsonErreur('Non connecté', 401);
            $sql = "SELECT c.*, s.nom AS service_nom,
                           CONCAT(u.prenom,' ',u.nom) AS client_nom,
                           l.statut AS livr_statut,
                           CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom,
                           p.statut AS paiement_statut
                    FROM commandes c
                    JOIN services s ON s.id=c.service_id
                    JOIN utilisateurs u ON u.id=c.client_id
                    LEFT JOIN livraisons l ON l.commande_id=c.id
                    LEFT JOIN livreurs lv ON lv.id=l.livreur_id
                    LEFT JOIN utilisateurs ul ON ul.id=lv.id
                    LEFT JOIN paiements p ON p.commande_id=c.id";
            $p = [];
            if ($user['role'] === 'client') { $sql .= " WHERE c.client_id=?"; $p[] = $user['id']; }
            elseif (isset($_GET['statut'])) { $sql .= " WHERE c.statut=?"; $p[] = $_GET['statut']; }
            $sql .= " ORDER BY c.cree_le DESC LIMIT 50";
            jsonReponse(Database::query($sql, $p));

        case 'commande_detail':
            if (!$user) jsonErreur('Non connecté', 401);
            $id = $_GET['id'] ?? '';
            $c  = Database::queryOne(
                "SELECT c.*, s.nom AS service_nom, CONCAT(u.prenom,' ',u.nom) AS client_nom,
                        l.id AS livr_id, l.statut AS livr_statut, l.eta_minutes, l.code_confirmation,
                        CONCAT(ul.prenom,' ',ul.nom) AS livreur_nom, ul.telephone AS livreur_tel
                 FROM commandes c
                 JOIN services s ON s.id=c.service_id
                 JOIN utilisateurs u ON u.id=c.client_id
                 LEFT JOIN livraisons l ON l.commande_id=c.id
                 LEFT JOIN livreurs lv ON lv.id=l.livreur_id
                 LEFT JOIN utilisateurs ul ON ul.id=lv.id
                 WHERE c.id=?", [$id]);
            if (!$c) jsonErreur('Commande introuvable', 404);
            if ($user['role']==='client' && $c['client_id']!==$user['id']) jsonErreur('Accès refusé', 403);
            if ($user['role']==='livreur') {
                $c['code_confirmation'] = null;
            }
            $imageSelect = Database::columnExists('produits', 'image_url') ? ', p.image_url' : '';
            $items = Database::query(
                "SELECT ci.*$imageSelect, p.icone
                 FROM commande_items ci
                 LEFT JOIN produits p ON p.id=ci.produit_id
                 WHERE ci.commande_id=?", [$id]);
            $c['items'] = $items;
            jsonReponse($c);

        case 'passer_commande':
            if (!$user || !in_array($user['role'], ['client','livreur'], true)) {
                jsonErreur('Connectez-vous avec un compte client ou livreur pour commander', 403);
            }
            $service_id = (int)($input['service_id'] ?? 0);
            $adresse    = trim($input['adresse_livraison'] ?? $input['adresse'] ?? '');
            $methode    = $input['methode_paiement'] ?? 'cash';
            $items      = $input['items'] ?? [];
            // Validation explicite de chaque champ
            if (!$service_id)          jsonErreur('Service non spécifié');
            if (!$adresse)             jsonErreur('Adresse de livraison manquante');
            if (empty($items))         jsonErreur('Aucun article dans la commande');
            if (!is_array($items))     jsonErreur('Format des articles invalide');

            // Vérifier produits et calculer total
            $sous_total = 0;
            $items_valides = [];
            foreach ($items as $item) {
                $p = Database::queryOne(
                    "SELECT id, nom, prix, disponible FROM produits WHERE id=? AND service_id=?",
                    [(int)($item['produit_id'] ?? 0), $service_id]);
                $pid = (int)($item['produit_id'] ?? 0);
                if (!$pid) jsonErreur("ID produit manquant dans les articles envoyés");
                if (!$p) jsonErreur("Produit #$pid introuvable ou n'appartient pas à ce service");
                if (!$p['disponible']) jsonErreur("{$p['nom']} n'est plus disponible");
                $qty = max(1, (int)($item['quantite'] ?? 1));
                $st  = $p['prix'] * $qty;
                $sous_total += $st;
                $items_valides[] = [...$p, 'quantite'=>$qty, 'sous_total'=>$st];
            }

            $frais  = FRAIS_LIVRAISON_BASE;
            $total  = $sous_total + $frais;
            $ref    = genererReference();
            $cmd_id = uniqid('cmd-', true);
            $pay_id = uniqid('pay-', true);

            Database::beginTransaction();
            try {
                Database::execute(
                    "INSERT INTO commandes (id,reference,client_id,service_id,adresse_livraison,statut,sous_total,frais_livraison,total)
                     VALUES (?,?,?,?,?,'en_attente',?,?,?)",
                    [$cmd_id,$ref,$user['id'],$service_id,$adresse,$sous_total,$frais,$total]);

                foreach ($items_valides as $item) {
                    Database::execute(
                        "INSERT INTO commande_items (commande_id,produit_id,nom_produit,prix_unitaire,quantite,sous_total)
                         VALUES (?,?,?,?,?,?)",
                        [$cmd_id,$item['id'],$item['nom'],$item['prix'],$item['quantite'],$item['sous_total']]);
                }
                Database::execute(
                    "INSERT INTO paiements (id,commande_id,methode,montant,statut) VALUES (?,?,?,?,'en_attente')",
                    [$pay_id,$cmd_id,$methode,$total]);

                // Notification au client
                Database::execute(
                    "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json) VALUES (?,?,'commande','Commande reçue !',?,?)",
                    [uniqid('n-',true), $user['id'],
                     "Commande $ref enregistrée. Total : ".number_format($total,0,',',' ')." FCFA",
                     json_encode(['commande_id'=>$cmd_id,'reference'=>$ref])]);

                // Résumé des articles pour les livreurs
                $articles_resume = implode(', ', array_map(fn($it) => $it['quantite'].'× '.$it['nom'], $items_valides));
                $msg_livreur = "Nouvelle commande $ref — $articles_resume — ".number_format($total,0,',',' ')." FCFA";

                $livreur_exclu_id = $user['role'] === 'livreur' ? $user['id'] : null;
                $assignation_auto = assignerCommandeAutomatiquement(
                    $cmd_id,
                    $service_id,
                    $frais,
                    $ref,
                    $livreur_exclu_id
                );
                if ($assignation_auto) {
                    Database::execute(
                        "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json)
                         VALUES (?,?,'livraison','Livreur assigné',?,?)",
                        [uniqid('n-', true), $user['id'],
                         "Votre commande $ref est confirmée. {$assignation_auto['livreur_nom']} prend la livraison.",
                         json_encode(['commande_id'=>$cmd_id,'reference'=>$ref,'livreur_id'=>$assignation_auto['livreur_id']], JSON_UNESCAPED_UNICODE)]);
                } else {
                    // Aucun livreur assignable maintenant : notifier les livreurs disponibles.
                    $livreurs_dispo = Database::query(
                        "SELECT id
                         FROM livreurs
                         WHERE statut='disponible'
                           AND documents_valides=1
                           AND (? IS NULL OR id<>?)",
                        [$livreur_exclu_id, $livreur_exclu_id]
                    );
                    foreach ($livreurs_dispo as $lv) {
                        Database::execute(
                            "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json) VALUES (?,?,'commande','Nouvelle commande disponible',?,?)",
                            [uniqid('n-',true), $lv['id'], $msg_livreur,
                             json_encode(['commande_id'=>$cmd_id,'reference'=>$ref,'articles'=>$articles_resume], JSON_UNESCAPED_UNICODE)]);
                    }
                    $admins = Database::query("SELECT id FROM utilisateurs WHERE role='admin' AND actif=1");
                    foreach ($admins as $adm) {
                        Database::execute(
                            "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json)
                             VALUES (?,?,'commande','Commande à assigner',?,?)",
                            [uniqid('n-', true), $adm['id'],
                             "Aucun livreur disponible pour la commande $ref.",
                             json_encode(['commande_id'=>$cmd_id,'reference'=>$ref], JSON_UNESCAPED_UNICODE)]);
                    }
                }

                Database::commit();
                jsonSucces('Commande passée avec succès !', [
                    'commande_id' => $cmd_id,
                    'reference' => $ref,
                    'total' => $total,
                    'assignation_auto' => $assignation_auto,
                ]);
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Erreur lors de la commande: ' . $e->getMessage());
            }

        case 'changer_statut_commande':
            if (!$user || !in_array($user['role'],['admin','livreur'])) jsonErreur('Non autorisé', 403);
            $id     = $input['commande_id'] ?? '';
            $statut = $input['statut'] ?? '';
            if ($statut === 'livree') {
                jsonErreur('La livraison doit être confirmée par le livreur avec le code du client');
            }
            $cols   = ['confirmee'=>'confirmee_le','en_preparation'=>'preparee_le',
                       'en_route'=>'prise_en_charge_le','annulee'=>'annulee_le'];
            if (!isset($cols[$statut])) jsonErreur('Statut invalide');
            $col = $cols[$statut];
            Database::execute(
                "UPDATE commandes SET statut=?, $col=NOW() WHERE id=?", [$statut, $id]);
            jsonSucces("Statut mis à jour : $statut");

        case 'annuler_commande':
            if (!$user) jsonErreur('Non connecté', 401);
            $id     = $input['commande_id'] ?? '';
            $raison = trim($input['raison'] ?? '');
            $c = Database::queryOne("SELECT statut,client_id FROM commandes WHERE id=?", [$id]);
            if (!$c) jsonErreur('Introuvable', 404);
            if ($user['role']!=='admin' && $c['client_id']!==$user['id']) jsonErreur('Accès refusé', 403);
            if (in_array($c['statut'], ['en_route','livree'])) jsonErreur('Impossible d\'annuler cette commande');
            Database::execute(
                "UPDATE commandes SET statut='annulee', annulation_raison=?, annulee_le=NOW() WHERE id=?",
                [$raison, $id]);
            jsonSucces('Commande annulée');

        // ====================================================
        //  LIVRAISONS
        // ====================================================
        case 'assigner_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $cmd_id  = $input['commande_id'] ?? '';
            $livr_id = $input['livreur_id'] ?? '';
            if (!$cmd_id || !$livr_id) jsonErreur('Données manquantes');

            $c = Database::queryOne("SELECT reference,frais_livraison FROM commandes WHERE id=?", [$cmd_id]);
            if (!$c) jsonErreur('Commande introuvable', 404);

            Database::beginTransaction();
            try {
                $assignation = assignerLivreurACommande($cmd_id, $livr_id, (int)$c['frais_livraison'], $c['reference']);
                Database::commit();
                jsonSucces('Livreur assigné', $assignation);
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur($e->getMessage());
            }

        case 'auto_assigner_commandes':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $commandes = Database::query(
                "SELECT c.id, c.reference, c.service_id, c.frais_livraison, c.client_id
                 FROM commandes c
                 LEFT JOIN livraisons l ON l.commande_id=c.id
                 WHERE c.statut IN ('en_attente','confirmee')
                   AND l.id IS NULL
                 ORDER BY c.cree_le ASC
                 LIMIT 50"
            );

            $assignees = [];
            $ignorees = [];
            Database::beginTransaction();
            try {
                foreach ($commandes as $cmd) {
                    $livreur = choisirLivreurAutomatique(
                        (int)$cmd['service_id'],
                        $cmd['client_id']
                    );
                    if (!$livreur) {
                        $ignorees[] = $cmd['reference'];
                        break;
                    }
                    try {
                        $assignees[] = assignerLivreurACommande(
                            $cmd['id'],
                            $livreur['id'],
                            (int)$cmd['frais_livraison'],
                            $cmd['reference']
                        );
                    } catch (Exception $e) {
                        $ignorees[] = $cmd['reference'];
                    }
                }
                Database::commit();
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Assignation automatique impossible : ' . $e->getMessage());
            }

            jsonSucces(count($assignees) . ' commande(s) assignée(s)', [
                'assignees' => count($assignees),
                'ignorees' => $ignorees,
            ]);

        case 'maj_position_livreur':
            if (!$user || $user['role']!=='livreur') jsonErreur('Non autorisé', 403);
            $lat = $input['latitude'] ?? null;
            $lng = $input['longitude'] ?? null;
            $eta = $input['eta_minutes'] ?? null;
            $liv_id = $input['livraison_id'] ?? '';
            Database::execute(
                "UPDATE livreurs SET position_lat=?, position_lng=?, position_maj_le=NOW() WHERE id=?",
                [$lat,$lng,$user['id']]);
            if ($liv_id) {
                Database::execute(
                    "UPDATE livraisons SET position_lat=?, position_lng=?, eta_minutes=? WHERE id=?",
                    [$lat,$lng,$eta,$liv_id]);
            }
            jsonSucces('Position mise à jour');

        case 'confirmer_livraison':
            if (!$user || $user['role']!=='livreur') jsonErreur('Non autorisé', 403);
            $liv_id = $input['livraison_id'] ?? '';
            $code   = trim($input['code'] ?? '');
            $liv = Database::queryOne(
                "SELECT * FROM livraisons WHERE id=? AND livreur_id=?",
                [$liv_id, $user['id']]
            );
            if (!$liv) jsonErreur('Livraison introuvable ou non assignée à ce livreur', 404);
            if (in_array($liv['statut'], ['livree','echouee'], true)) jsonErreur('Cette livraison est déjà terminée');
            if ($liv['code_confirmation'] !== $code) jsonErreur('Code incorrect');
            Database::execute("UPDATE livraisons SET statut='livree', livree_le=NOW() WHERE id=?", [$liv_id]);
            Database::execute("UPDATE commandes SET statut='livree', livree_le=NOW() WHERE id=?", [$liv['commande_id']]);
            Database::execute(
                "UPDATE livreurs SET statut='disponible', nb_livraisons_total=nb_livraisons_total+1, gains_total=gains_total+? WHERE id=?",
                [$liv['gain_livreur']??0, $user['id']]);
            jsonSucces('Livraison confirmée !');

        // ====================================================
        //  LIVREURS
        // ====================================================
        case 'livreurs_disponibles':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $rows = Database::query(
                "SELECT l.id, CONCAT(u.prenom,' ',u.nom) AS nom, u.telephone, l.zone_id, l.type_vehicule, l.note_moyenne, z.nom AS zone_nom
                 FROM livreurs l JOIN utilisateurs u ON u.id=l.id LEFT JOIN zones z ON z.id=l.zone_id
                 WHERE l.statut='disponible' AND l.documents_valides=1");
            jsonReponse($rows);

        case 'toggle_statut_livreur':
            if (!$user || $user['role']!=='livreur') jsonErreur('Non autorisé', 403);
            $statut = $input['statut'] ?? '';
            if (!in_array($statut,['disponible','hors_ligne'])) jsonErreur('Statut invalide');
            $dossier = Database::queryOne(
                "SELECT documents_valides FROM livreurs WHERE id=?",
                [$user['id']]
            );
            if ($statut === 'disponible' && empty($dossier['documents_valides'])) {
                jsonErreur('Vos documents doivent être validés par un administrateur avant de passer en ligne', 403);
            }
            Database::execute("UPDATE livreurs SET statut=? WHERE id=?", [$statut,$user['id']]);
            jsonSucces('Statut mis à jour', ['statut' => $statut]);

        // ====================================================
        //  NOTIFICATIONS
        // ====================================================
        case 'notifs_liste':
            if (!$user) jsonErreur('Non connecté', 401);
            $rows = Database::query(
                "SELECT * FROM notifications WHERE utilisateur_id=? ORDER BY cree_le DESC LIMIT 20",
                [$user['id']]);
            jsonReponse($rows);

        case 'notif_lire':
            if (!$user) jsonErreur('Non connecté', 401);
            $id = $input['id'] ?? '';
            Database::execute("UPDATE notifications SET lu=1 WHERE id=? AND utilisateur_id=?", [$id,$user['id']]);
            jsonSucces('Lu');

        case 'notifs_tout_lire': case 'notifs_tout_lure':
            if (!$user) jsonErreur('Non connecté', 401);
            Database::execute("UPDATE notifications SET lu=1 WHERE utilisateur_id=?", [$user['id']]);
            jsonSucces('Tout marqué comme lu');

        // ====================================================
        //  ÉVALUATIONS
        // ====================================================
        case 'ajouter_evaluation':
            if (!$user || !in_array($user['role'], ['client','livreur'], true)) jsonErreur('Non autorisé', 403);
            $cmd_id    = $input['commande_id'] ?? '';
            $note      = (int)($input['note'] ?? 0);
            $commentaire = trim($input['commentaire'] ?? '');
            $cible_type  = $input['cible_type'] ?? '';
            $cible_id    = $input['cible_id'] ?? '';
            if ($note<1||$note>5) jsonErreur('Note entre 1 et 5');
            $commande = Database::queryOne(
                "SELECT id FROM commandes WHERE id=? AND client_id=? AND statut='livree'",
                [$cmd_id, $user['id']]
            );
            if (!$commande) jsonErreur('Commande livrée introuvable ou accès refusé', 403);
            $ev_id = uniqid('ev-', true);
            Database::execute(
                "INSERT INTO evaluations (id,commande_id,auteur_id,cible_type,cible_id,note,commentaire)
                 VALUES (?,?,?,?,?,?,?)",
                [$ev_id,$cmd_id,$user['id'],$cible_type,$cible_id,$note,$commentaire]);
            // Recalculer note
            if ($cible_type === 'service') {
                Database::execute(
                    "UPDATE services s SET note_moyenne=(SELECT ROUND(AVG(e.note),2) FROM evaluations e WHERE e.cible_type='service' AND e.cible_id=?), nb_evaluations=(SELECT COUNT(*) FROM evaluations e WHERE e.cible_type='service' AND e.cible_id=?) WHERE s.id=?",
                    [$cible_id,$cible_id,$cible_id]);
            }
            jsonSucces('Évaluation ajoutée, merci !');

        // ====================================================
        //  ADMIN — STATS
        // ====================================================
        case 'stats_dashboard':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $jour = Database::queryOne(
                "SELECT COUNT(*) AS total, SUM(total) AS ca,
                        SUM(statut='livree') AS livrees, SUM(statut='en_attente') AS en_attente,
                        SUM(statut='annulee') AS annulees
                 FROM commandes WHERE DATE(cree_le)=CURDATE()");
            $mois = Database::queryOne(
                "SELECT COUNT(*) AS total, SUM(total) AS ca
                 FROM commandes WHERE MONTH(cree_le)=MONTH(NOW()) AND YEAR(cree_le)=YEAR(NOW()) AND statut='livree'");
            $livreurs = Database::query("SELECT statut, COUNT(*) AS nb FROM livreurs GROUP BY statut");
            $top_services = Database::query(
                "SELECT s.nom, COUNT(c.id) AS commandes, COALESCE(SUM(c.total),0) AS ca
                 FROM services s LEFT JOIN commandes c ON c.service_id=s.id AND c.statut='livree'
                 GROUP BY s.id, s.nom ORDER BY commandes DESC LIMIT 5");
            jsonReponse(compact('jour','mois','livreurs','top_services'));

        case 'stats_evolution':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $rows = Database::query(
                "SELECT DATE(cree_le) AS jour, COUNT(*) AS total,
                        SUM(statut='livree') AS livrees,
                        COALESCE(SUM(CASE WHEN statut='livree' THEN total ELSE 0 END),0) AS ca
                 FROM commandes WHERE cree_le >= NOW() - INTERVAL 30 DAY
                 GROUP BY DATE(cree_le) ORDER BY jour");
            jsonReponse($rows);

        // ====================================================
        //  ADMIN — SERVICES
        // ====================================================
        case 'upload_image':
            if (!$user) jsonErreur('Non connecté : reconnectez-vous avec le compte admin', 401);
            if (($user['role'] ?? '') !== 'admin') jsonErreur("Compte connecté non admin (rôle actuel : {$user['role']})", 403);
            if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                jsonErreur('Aucune image reçue');
            }

            $type = $input['type'] ?? ($_POST['type'] ?? 'products');
            $type = in_array($type, ['services', 'products'], true) ? $type : 'products';
            $file = $_FILES['image'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) jsonErreur('Erreur upload image');
            if (($file['size'] ?? 0) > 3 * 1024 * 1024) jsonErreur('Image trop lourde (max 3 Mo)');

            $info = @getimagesize($file['tmp_name']);
            if (!$info || empty($info['mime'])) jsonErreur('Fichier image invalide');
            $extByMime = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            if (!isset($extByMime[$info['mime']])) jsonErreur('Format image non accepté (JPG, PNG, WEBP, GIF)');

            $uploadDir = __DIR__ . '/../public/uploads/' . $type;
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
                jsonErreur('Impossible de créer le dossier upload', 500);
            }
            @chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                jsonErreur("Le dossier d'upload n'est pas accessible en écriture : public/uploads/$type", 500);
            }
            $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extByMime[$info['mime']];
            $dest = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $last = error_get_last();
                $detail = $last['message'] ?? 'permission ou chemin invalide';
                jsonErreur("Impossible d’enregistrer l’image ($detail)", 500);
            }
            @chmod($dest, 0644);
            jsonSucces('Image envoyée', ['url' => '/rapidliv-php/public/uploads/' . $type . '/' . $filename]);

        case 'creer_service':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $fields = ['categorie_id','nom','description','adresse','telephone','delai_min','delai_max','commande_min'];
            $data   = array_intersect_key($input, array_flip($fields));
            $logo_url = trim($input['logo_url'] ?? '');
            if (Database::columnExists('services', 'logo_url')) {
                Database::execute(
                    "INSERT INTO services (categorie_id,nom,description,logo_url,adresse,telephone,delai_min,delai_max,commande_min)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [$data['categorie_id']??1,$data['nom']??'',trim($data['description']??''),$logo_url ?: null,
                     trim($data['adresse']??''),trim($data['telephone']??''),
                     (int)($data['delai_min']??15),(int)($data['delai_max']??45),(int)($data['commande_min']??1000)]);
            } else {
                Database::execute(
                    "INSERT INTO services (categorie_id,nom,description,adresse,telephone,delai_min,delai_max,commande_min)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$data['categorie_id']??1,$data['nom']??'',trim($data['description']??''),
                     trim($data['adresse']??''),trim($data['telephone']??''),
                     (int)($data['delai_min']??15),(int)($data['delai_max']??45),(int)($data['commande_min']??1000)]);
            }
            jsonSucces('Service créé');

        case 'toggle_service':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id  = (int)($input['id'] ?? 0);
            $val = (int)($input['actif'] ?? 0);
            Database::execute("UPDATE services SET actif=? WHERE id=?", [$val,$id]);
            jsonSucces('Service mis à jour');

        // ====================================================
        //  PARAMÈTRES
        // ====================================================
        // ====================================================
        //  SUPPRIMER / MODIFIER SERVICE
        // ====================================================
        case 'modifier_service':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id   = (int)($input['id'] ?? 0);
            $nom  = trim($input['nom'] ?? '');
            if (!$id || !$nom) jsonErreur('Données manquantes');
            $params = [
                $nom,
                trim($input['description'] ?? ''),
            ];
            $setLogo = '';
            if (Database::columnExists('services', 'logo_url')) {
                $setLogo = ' logo_url=?,';
                $params[] = trim($input['logo_url'] ?? '') ?: null;
            }
            $params = array_merge($params, [
                trim($input['adresse']     ?? ''),
                trim($input['telephone']   ?? ''),
                (int)($input['delai_min']   ?? 20),
                (int)($input['delai_max']   ?? 40),
                (int)($input['commande_min']?? 2000),
                $id
            ]);
            Database::execute(
                "UPDATE services SET nom=?, description=?,$setLogo adresse=?, telephone=?,
                 delai_min=?, delai_max=?, commande_min=? WHERE id=?",
                $params);
            jsonSucces('Service modifié');

        case 'generer_produits_service':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $service_id = (int)($input['service_id'] ?? 0);
            if (!$service_id) jsonErreur('Service manquant');

            $service = Database::queryOne(
                "SELECT s.id, s.nom, cs.nom AS cat_nom
                 FROM services s JOIN categories_service cs ON cs.id=s.categorie_id
                 WHERE s.id=?", [$service_id]);
            if (!$service) jsonErreur('Service introuvable', 404);

            $cat = strtolower($service['cat_nom'] ?? '');
            if (str_contains($cat, 'rest')) {
                $catProduitNom = 'Menus populaires';
                $catalogue = [
                    ['Thiébou dieun', 'Riz au poisson, légumes mijotés et sauce tomate maison', 4000, '🍛', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&w=700&q=80'],
                    ['Yassa poulet', 'Poulet mariné citron-oignons avec riz blanc parfumé', 3500, '🍗', 'https://images.unsplash.com/photo-1598515214141-89d3c73ae83b?auto=format&fit=crop&w=700&q=80'],
                    ['Mafé bœuf', 'Sauce arachide onctueuse, bœuf tendre et riz', 4500, '🥩', 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=700&q=80'],
                    ['Bissap frais 33cl', 'Boisson hibiscus fraîche légèrement sucrée', 500, '🧃', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=80'],
                    ['Eau minérale 0.5L', 'Bouteille fraîche pour accompagner le repas', 300, '💧', 'https://images.unsplash.com/photo-1559839914-17aae19cec71?auto=format&fit=crop&w=700&q=80'],
                ];
            } elseif (str_contains($cat, 'pharma')) {
                $catProduitNom = 'Essentiels pharmacie';
                $catalogue = [
                    ['Paracétamol 500mg', 'Boîte de comprimés, usage selon conseil médical', 1500, '💊', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=700&q=80'],
                    ['Vitamine C 1000mg', 'Comprimés effervescents vitamine C', 3000, '🍊', 'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&w=700&q=80'],
                    ['Gel hydroalcoolique', 'Flacon de gel désinfectant mains', 1200, '🧴', 'https://images.unsplash.com/photo-1584744982491-665216d95f8b?auto=format&fit=crop&w=700&q=80'],
                    ['Thermomètre digital', 'Thermomètre électronique familial', 3500, '🌡️', 'https://images.unsplash.com/photo-1584473457406-6240486418e9?auto=format&fit=crop&w=700&q=80'],
                    ['Masques chirurgicaux x10', 'Paquet de dix masques à usage quotidien', 1000, '😷', 'https://images.unsplash.com/photo-1584634731339-252c581abfc5?auto=format&fit=crop&w=700&q=80'],
                ];
            } elseif (str_contains($cat, 'super') || str_contains($cat, 'épicer') || str_contains($cat, 'epicer')) {
                $catProduitNom = 'Courses essentielles';
                $catalogue = [
                    ['Riz parfumé 5kg', 'Sac de riz parfumé long grain', 4500, '🍚', 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=700&q=80'],
                    ['Lait UHT 1L', 'Lait longue conservation demi-écrémé', 1200, '🥛', 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=700&q=80'],
                    ['Pain de mie complet', 'Pain tranché moelleux pour petit déjeuner', 800, '🍞', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=700&q=80'],
                    ['Œufs frais plateau 30', 'Œufs frais calibre moyen', 2500, '🥚', 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=700&q=80'],
                    ['Savon parfumé x3', 'Lot de trois savons pour la maison', 1200, '🧴', 'https://images.unsplash.com/photo-1607006483224-4f75f8f8f0b5?auto=format&fit=crop&w=700&q=80'],
                ];
            } else {
                $catProduitNom = 'Accessoires populaires';
                $catalogue = [
                    ['Chargeur USB-C 25W', 'Chargeur rapide compatible smartphones USB-C', 4500, '🔌', 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=700&q=80'],
                    ['Écouteurs Bluetooth', 'Écouteurs sans fil avec boîtier de charge', 18000, '🎧', 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&w=700&q=80'],
                    ['Câble HDMI 2m', 'Câble HDMI haute vitesse pour TV et ordinateur', 3500, '🖥️', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=700&q=80'],
                    ['Power bank 10000mAh', 'Batterie externe compacte pour téléphone', 12000, '🔋', 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?auto=format&fit=crop&w=700&q=80'],
                    ['Support téléphone voiture', 'Support ajustable pour tableau de bord', 5000, '📱', 'https://images.unsplash.com/photo-1556656793-08538906a9f8?auto=format&fit=crop&w=700&q=80'],
                ];
            }

            $categorie = Database::queryOne(
                "SELECT id FROM categories_produit WHERE service_id=? AND nom=?",
                [$service_id, $catProduitNom]);
            if (!$categorie) {
                Database::execute(
                    "INSERT INTO categories_produit (service_id,nom,ordre) VALUES (?,?,0)",
                    [$service_id, $catProduitNom]);
                $categorie_id = (int)Database::lastInsertId();
            } else {
                $categorie_id = (int)$categorie['id'];
            }

            $hasImageUrl = Database::columnExists('produits', 'image_url');
            $ajoutes = 0;
            foreach ($catalogue as [$nomProduit, $description, $prix, $icone, $imageUrl]) {
                if (Database::queryOne("SELECT id FROM produits WHERE service_id=? AND nom=?", [$service_id, $nomProduit])) {
                    continue;
                }
                if ($hasImageUrl) {
                    Database::execute(
                        "INSERT INTO produits (service_id,categorie_id,nom,description,prix,image_url,icone,disponible)
                         VALUES (?,?,?,?,?,?,?,1)",
                        [$service_id,$categorie_id,$nomProduit,$description,$prix,$imageUrl,$icone]);
                } else {
                    Database::execute(
                        "INSERT INTO produits (service_id,categorie_id,nom,description,prix,icone,disponible)
                         VALUES (?,?,?,?,?,?,1)",
                        [$service_id,$categorie_id,$nomProduit,$description,$prix,$icone]);
                }
                $ajoutes++;
            }

            jsonSucces("$ajoutes produit(s) généré(s)", ['ajoutes' => $ajoutes]);

        case 'creer_produit':
        case 'ajouter_produit':
        case 'produit_ajouter':
            if (!$user) jsonErreur('Non connecté : reconnectez-vous avec le compte admin', 401);
            if (($user['role'] ?? '') !== 'admin') jsonErreur("Compte connecté non admin (rôle actuel : {$user['role']})", 403);
            $service_id = (int)($input['service_id'] ?? 0);
            $nom        = trim($input['nom'] ?? '');
            $description= trim($input['description'] ?? '');
            $prix       = (int)($input['prix'] ?? 0);
            $prix_promo = isset($input['prix_promo']) && $input['prix_promo'] !== '' ? (int)$input['prix_promo'] : null;
            $categorie_id = isset($input['categorie_id']) && $input['categorie_id'] !== '' ? (int)$input['categorie_id'] : null;
            $icone      = trim($input['icone'] ?? '📦');
            $image_url  = trim($input['image_url'] ?? '');

            if (!$service_id) jsonErreur('Service manquant');
            if (!$nom) jsonErreur('Nom du produit obligatoire');
            if ($prix <= 0) jsonErreur('Prix du produit invalide');
            if (!Database::queryOne("SELECT id FROM services WHERE id=?", [$service_id])) jsonErreur('Service introuvable', 404);
            if ($categorie_id && !Database::queryOne("SELECT id FROM categories_produit WHERE id=? AND service_id=?", [$categorie_id, $service_id])) {
                jsonErreur('Catégorie produit invalide');
            }

            $hasImageUrl = Database::columnExists('produits', 'image_url');
            if ($hasImageUrl) {
                Database::execute(
                    "INSERT INTO produits (service_id,categorie_id,nom,description,prix,prix_promo,image_url,icone,disponible)
                     VALUES (?,?,?,?,?,?,?,?,1)",
                    [$service_id,$categorie_id,$nom,$description,$prix,$prix_promo,$image_url ?: null,$icone ?: '📦']);
            } else {
                Database::execute(
                    "INSERT INTO produits (service_id,categorie_id,nom,description,prix,prix_promo,icone,disponible)
                     VALUES (?,?,?,?,?,?,?,1)",
                    [$service_id,$categorie_id,$nom,$description,$prix,$prix_promo,$icone ?: '📦']);
            }
            jsonSucces('Produit ajouté', ['id' => Database::lastInsertId()]);

        case 'modifier_produit':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id         = (int)($input['id'] ?? 0);
            $nom        = trim($input['nom'] ?? '');
            $description= trim($input['description'] ?? '');
            $prix       = (int)($input['prix'] ?? 0);
            $prix_promo = isset($input['prix_promo']) && $input['prix_promo'] !== '' ? (int)$input['prix_promo'] : null;
            $disponible = isset($input['disponible']) ? (int)!empty($input['disponible']) : 1;
            $icone      = trim($input['icone'] ?? '📦');
            $image_url  = trim($input['image_url'] ?? '');

            if (!$id) jsonErreur('Produit manquant');
            if (!$nom) jsonErreur('Nom du produit obligatoire');
            if ($prix <= 0) jsonErreur('Prix du produit invalide');
            if (Database::columnExists('produits', 'image_url')) {
                Database::execute(
                    "UPDATE produits SET nom=?, description=?, prix=?, prix_promo=?, image_url=?, icone=?, disponible=? WHERE id=?",
                    [$nom,$description,$prix,$prix_promo,$image_url ?: null,$icone ?: '📦',$disponible,$id]);
            } else {
                Database::execute(
                    "UPDATE produits SET nom=?, description=?, prix=?, prix_promo=?, icone=?, disponible=? WHERE id=?",
                    [$nom,$description,$prix,$prix_promo,$icone ?: '📦',$disponible,$id]);
            }
            jsonSucces('Produit modifié');

        case 'supprimer_produit':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonErreur('Produit manquant');
            try {
                assurerProduitCommandeNullable();
            } catch (Exception $e) {
                error_log('Migration commande_items.produit_id nullable impossible: ' . $e->getMessage());
            }
            Database::beginTransaction();
            try {
                Database::execute("UPDATE commande_items SET produit_id=NULL WHERE produit_id=?", [$id]);
                Database::execute("DELETE FROM produits WHERE id=?", [$id]);
                Database::commit();
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Erreur suppression : ' . $e->getMessage());
            }
            jsonSucces('Produit supprimé');

        case 'supprimer_service':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonErreur('ID manquant');

            $service = Database::queryOne("SELECT id, actif FROM services WHERE id=?", [$id]);
            if (!$service) jsonErreur('Service introuvable', 404);

            // Le modal demande déjà une confirmation explicite. On purge donc les données
            // liées au service, même si une commande est encore active.
            try {
                assurerProduitCommandeNullable();
            } catch (Exception $e) {
                error_log('Migration commande_items.produit_id nullable impossible: ' . $e->getMessage());
            }

            Database::beginTransaction();
            try {
                Database::execute(
                    "UPDATE livreurs l
                     JOIN livraisons lv ON lv.livreur_id=l.id
                     JOIN commandes c ON c.id=lv.commande_id
                     SET l.statut='disponible'
                     WHERE c.service_id=?
                       AND c.statut NOT IN ('livree','annulee')
                       AND l.statut='en_course'", [$id]);
                Database::execute(
                    "UPDATE commandes
                     SET statut='annulee',
                         annulation_raison='Service supprimé par l’administration',
                         annulee_le=COALESCE(annulee_le, NOW())
                     WHERE service_id=? AND statut NOT IN ('livree','annulee')", [$id]);
                Database::execute(
                    "UPDATE commande_items ci
                     JOIN produits p ON p.id=ci.produit_id
                     SET ci.produit_id=NULL
                     WHERE p.service_id=?", [$id]);
                Database::execute(
                    "DELETE e FROM evaluations e
                     JOIN commandes c ON c.id=e.commande_id
                     WHERE c.service_id=?", [$id]);
                Database::execute(
                    "DELETE p FROM paiements p
                     JOIN commandes c ON c.id=p.commande_id
                     WHERE c.service_id=?", [$id]);
                Database::execute(
                    "DELETE lv FROM livraisons lv
                     JOIN commandes c ON c.id=lv.commande_id
                     WHERE c.service_id=?", [$id]);
                Database::execute(
                    "DELETE ci FROM commande_items ci
                     JOIN commandes c ON c.id=ci.commande_id
                     WHERE c.service_id=?", [$id]);
                Database::execute("DELETE FROM commandes WHERE service_id=?", [$id]);
                Database::execute("DELETE FROM produits WHERE service_id=?", [$id]);
                Database::execute("DELETE FROM categories_produit WHERE service_id=?", [$id]);
                Database::execute("DELETE FROM services WHERE id=?", [$id]);
                Database::commit();
                jsonSucces('Service et données liées supprimés définitivement');
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Erreur suppression : ' . $e->getMessage());
            }

        // ====================================================
        //  MODIFIER / SUPPRIMER LIVREUR
        // ====================================================
        case 'creer_livreur':
        case 'ajouter_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);

            $prenom = trim($input['prenom'] ?? '');
            $nom    = trim($input['nom'] ?? '');
            $email  = trim($input['email'] ?? '');
            $tel    = trim($input['telephone'] ?? '');
            $mdp    = $input['mot_de_passe'] ?? '';
            $zone_id = !empty($input['zone_id']) ? (int)$input['zone_id'] : null;
            $type_vehicule = trim($input['type_vehicule'] ?? 'Moto');

            if (!$prenom || !$nom || !$email || !$tel || !$mdp) jsonErreur('Tous les champs obligatoires sont requis');
            if (!validerEmail($email)) jsonErreur('Email invalide');
            if (strlen($mdp) < 6) jsonErreur('Mot de passe trop court (6 caractères minimum)');
            if (!in_array($type_vehicule, ['Moto','Vélo','Voiture'], true)) jsonErreur('Type de véhicule invalide');

            $existe = Database::queryOne("SELECT id FROM utilisateurs WHERE email=? OR telephone=?", [$email, $tel]);
            if ($existe) jsonErreur('Email ou téléphone déjà utilisé');

            if ($zone_id && !Database::queryOne("SELECT id FROM zones WHERE id=? AND actif=1", [$zone_id])) {
                jsonErreur('Zone invalide');
            }

            $id = uniqid('u-livreur-', true);
            Database::beginTransaction();
            try {
                Database::execute(
                    "INSERT INTO utilisateurs (id,nom,prenom,email,telephone,mot_de_passe,role,email_verifie,tel_verifie)
                     VALUES (?,?,?,?,?,?,'livreur',1,1)",
                    [$id,$nom,$prenom,$email,$tel,hasherMotDePasse($mdp)]
                );
                Database::execute(
                    "INSERT INTO livreurs (id,zone_id,type_vehicule,statut,documents_valides)
                     VALUES (?,?,?,'hors_ligne',0)",
                    [$id,$zone_id,$type_vehicule]
                );
                Database::commit();
                jsonSucces('Livreur créé', ['id' => $id]);
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Erreur création livreur : ' . $e->getMessage(), 500);
            }

        case 'modifier_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = $input['id'] ?? '';
            if (!$id) jsonErreur('ID manquant');

            // Mettre à jour utilisateurs
            if (!empty($input['prenom']) && !empty($input['nom'])) {
                Database::execute(
                    "UPDATE utilisateurs SET nom=?, prenom=?, telephone=? WHERE id=?",
                    [trim($input['nom']), trim($input['prenom']), trim($input['telephone']??''), $id]);
            }
            // Mettre à jour livreurs
            $zone_id          = !empty($input['zone_id']) ? (int)$input['zone_id'] : null;
            $type_vehicule    = trim($input['type_vehicule'] ?? 'Moto');

            $set_parts = ["type_vehicule=?"];
            $set_vals  = [$type_vehicule];
            if ($zone_id !== null) { $set_parts[] = "zone_id=?"; $set_vals[] = $zone_id; }
            $set_vals[] = $id;
            Database::execute("UPDATE livreurs SET ".implode(',',$set_parts)." WHERE id=?", $set_vals);
            jsonSucces('Livreur modifié');

        case 'mettre_a_jour_documents_livreur':
            if (!$user || $user['role']!=='livreur') jsonErreur('Non autorisé', 403);
            if (empty($_FILES['photo_profil']) || empty($_FILES['carte_identite'])) {
                jsonErreur('La photo de profil et la carte d’identité sont obligatoires');
            }

            $ancien = Database::queryOne(
                "SELECT u.photo_url, l.carte_identite_fichier
                 FROM utilisateurs u JOIN livreurs l ON l.id=u.id
                 WHERE u.id=?",
                [$user['id']]
            );
            $photoNom = null;
            $identiteNom = null;
            try {
                $photoNom = enregistrerImageUpload(
                    $_FILES['photo_profil'],
                    __DIR__ . '/../public/uploads/livreurs',
                    'profil'
                );
                $identiteNom = enregistrerImageUpload(
                    $_FILES['carte_identite'],
                    __DIR__ . '/../storage/livreurs',
                    'identite'
                );
            } catch (RuntimeException $e) {
                if ($photoNom) @unlink(__DIR__ . '/../public/uploads/livreurs/' . $photoNom);
                if ($identiteNom) @unlink(__DIR__ . '/../storage/livreurs/' . $identiteNom);
                jsonErreur($e->getMessage());
            }

            $photoUrl = '/rapidliv-php/public/uploads/livreurs/' . $photoNom;
            Database::beginTransaction();
            try {
                Database::execute("UPDATE utilisateurs SET photo_url=? WHERE id=?", [$photoUrl, $user['id']]);
                Database::execute(
                    "UPDATE livreurs
                     SET carte_identite_fichier=?, documents_valides=0, documents_statut='en_attente',
                         documents_motif=NULL, documents_verifies_le=NULL, documents_verifies_par=NULL,
                         statut=CASE WHEN statut='en_course' THEN statut ELSE 'hors_ligne' END
                     WHERE id=?",
                    [$identiteNom, $user['id']]
                );
                Database::commit();
            } catch (Throwable $e) {
                Database::rollback();
                @unlink(__DIR__ . '/../public/uploads/livreurs/' . $photoNom);
                @unlink(__DIR__ . '/../storage/livreurs/' . $identiteNom);
                jsonErreur('Impossible de mettre à jour les documents', 500);
            }

            $anciennePhoto = basename($ancien['photo_url'] ?? '');
            $ancienneIdentite = basename($ancien['carte_identite_fichier'] ?? '');
            if ($anciennePhoto && $anciennePhoto !== $photoNom) {
                @unlink(__DIR__ . '/../public/uploads/livreurs/' . $anciennePhoto);
            }
            if ($ancienneIdentite && $ancienneIdentite !== $identiteNom) {
                @unlink(__DIR__ . '/../storage/livreurs/' . $ancienneIdentite);
            }
            jsonSucces('Documents envoyés. Ils sont en attente de validation.');

        case 'document_identite_livreur':
            if (!$user) jsonErreur('Non connecté', 401);
            $livreurId = $_GET['id'] ?? '';
            if ($user['role'] !== 'admin' && $user['id'] !== $livreurId) {
                jsonErreur('Accès refusé', 403);
            }
            $document = Database::queryOne(
                "SELECT carte_identite_fichier FROM livreurs WHERE id=?",
                [$livreurId]
            );
            $nomFichier = basename($document['carte_identite_fichier'] ?? '');
            if ($nomFichier === '') jsonErreur('Carte d’identité non disponible', 404);

            $chemin = __DIR__ . '/../storage/livreurs/' . $nomFichier;
            if (!is_file($chemin)) jsonErreur('Fichier introuvable', 404);
            $info = @getimagesize($chemin);
            if (!$info) jsonErreur('Document invalide', 500);

            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: ' . $info['mime']);
            header('Content-Length: ' . filesize($chemin));
            header('Content-Disposition: inline; filename="carte-identite-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $livreurId) . '.' . pathinfo($nomFichier, PATHINFO_EXTENSION) . '"');
            header('Cache-Control: private, no-store');
            readfile($chemin);
            exit;

        case 'valider_documents_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = $input['id'] ?? '';
            $dossier = Database::queryOne(
                "SELECT l.carte_identite_fichier, u.photo_url
                 FROM livreurs l JOIN utilisateurs u ON u.id=l.id
                 WHERE l.id=?",
                [$id]
            );
            if (!$dossier) jsonErreur('Livreur introuvable', 404);
            if (empty($dossier['photo_url']) || empty($dossier['carte_identite_fichier'])) {
                jsonErreur('La photo de profil et la carte d’identité doivent être fournies avant validation');
            }
            Database::execute(
                "UPDATE livreurs
                 SET documents_valides=1, documents_statut='valides', documents_motif=NULL,
                     documents_verifies_le=NOW(), documents_verifies_par=?
                 WHERE id=?",
                [$user['id'], $id]
            );
            Database::execute(
                "INSERT INTO notifications (id,utilisateur_id,type,titre,message)
                 VALUES (?,?,'systeme','Documents validés','Votre dossier livreur est validé. Vous pouvez maintenant passer en ligne.')",
                [uniqid('n-', true), $id]
            );
            jsonSucces('Documents validés');

        case 'rejeter_documents_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = $input['id'] ?? '';
            $motif = trim($input['motif'] ?? '');
            if (!$id || !$motif) jsonErreur('Le motif du rejet est obligatoire');
            Database::execute(
                "UPDATE livreurs
                 SET documents_valides=0, documents_statut='rejetes', documents_motif=?,
                     documents_verifies_le=NOW(), documents_verifies_par=?,
                     statut=CASE WHEN statut='en_course' THEN statut ELSE 'hors_ligne' END
                 WHERE id=?",
                [$motif, $user['id'], $id]
            );
            Database::execute(
                "INSERT INTO notifications (id,utilisateur_id,type,titre,message)
                 VALUES (?,?,'systeme','Documents rejetés',?)",
                [uniqid('n-', true), $id, 'Votre dossier livreur a été rejeté : ' . $motif]
            );
            jsonSucces('Documents rejetés');

        case 'changer_statut_livreur_admin':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id     = $input['id'] ?? '';
            $statut = $input['statut'] ?? '';
            if (!in_array($statut, ['disponible','hors_ligne','suspendu'])) jsonErreur('Statut invalide');
            if ($statut === 'disponible') {
                $dossier = Database::queryOne("SELECT documents_valides FROM livreurs WHERE id=?", [$id]);
                if (empty($dossier['documents_valides'])) {
                    jsonErreur('Impossible d’activer un livreur dont les documents ne sont pas validés');
                }
            }
            Database::execute("UPDATE livreurs SET statut=? WHERE id=?", [$statut, $id]);
            jsonSucces('Statut livreur mis à jour');

        case 'supprimer_livreur':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            $id = $input['id'] ?? '';
            if (!$id) jsonErreur('ID manquant');
            $fichiersLivreur = Database::queryOne(
                "SELECT u.photo_url, l.carte_identite_fichier
                 FROM utilisateurs u JOIN livreurs l ON l.id=u.id
                 WHERE u.id=?",
                [$id]
            );

            // Vérifier missions actives
            $missions = Database::queryOne(
                "SELECT COUNT(*) AS nb FROM livraisons
                 WHERE livreur_id=? AND statut NOT IN ('livree','echouee')", [$id]);
            if ((int)$missions['nb'] > 0) {
                jsonErreur("Ce livreur a {$missions['nb']} mission(s) en cours. Attendez la fin avant de supprimer.");
            }

            Database::beginTransaction();
            try {
                // Détacher le livreur des livraisons terminées (historique conservé avec livreur_id NULL)
                Database::execute(
                    "UPDATE livraisons SET livreur_id=NULL WHERE livreur_id=? AND statut IN ('livree','echouee')", [$id]);
                // Supprimer les évaluations du livreur
                Database::execute("DELETE FROM evaluations WHERE cible_type='livreur' AND cible_id=?", [$id]);
                // Supprimer les notifications
                Database::execute("DELETE FROM notifications WHERE utilisateur_id=?", [$id]);
                // Supprimer entrée livreurs
                Database::execute("DELETE FROM livreurs WHERE id=?", [$id]);
                // Supprimer le compte utilisateur
                Database::execute("DELETE FROM utilisateurs WHERE id=?", [$id]);
                Database::commit();
                $photoNom = basename($fichiersLivreur['photo_url'] ?? '');
                $identiteNom = basename($fichiersLivreur['carte_identite_fichier'] ?? '');
                if ($photoNom) @unlink(__DIR__ . '/../public/uploads/livreurs/' . $photoNom);
                if ($identiteNom) @unlink(__DIR__ . '/../storage/livreurs/' . $identiteNom);
                jsonSucces('Livreur supprimé définitivement');
            } catch (Exception $e) {
                Database::rollback();
                jsonErreur('Erreur suppression : ' . $e->getMessage());
            }

        case 'sauver_parametres':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            foreach ($input as $cle => $valeur) {
                if (in_array($cle, ['frais_livraison','commission','rayon_max','maintenance','auto_assign_livreur'])) {
                    Database::execute(
                        "INSERT INTO parametres (cle,valeur) VALUES (?,?) ON DUPLICATE KEY UPDATE valeur=?",
                        [$cle, $valeur, $valeur]);
                }
            }
            jsonSucces('Paramètres sauvegardés');

        // ====================================================
        //  PROFIL
        // ====================================================
        case 'maj_profil':
            if (!$user) jsonErreur('Non connecté', 401);
            $nom    = trim($input['nom'] ?? '');
            $prenom = trim($input['prenom'] ?? '');
            $tel    = trim($input['telephone'] ?? '');
            if (!$nom||!$prenom) jsonErreur('Champs requis');
            Database::execute(
                "UPDATE utilisateurs SET nom=?, prenom=?, telephone=? WHERE id=?",
                [$nom,$prenom,$tel,$user['id']]);
            $_SESSION['user_nom'] = "$prenom $nom";
            jsonSucces('Profil mis à jour');

        // ====================================================
        //  LIVREUR : Signaler intérêt pour une mission
        // ====================================================
        case 'signaler_interet_mission':
            if (!$user || $user['role']!=='livreur') jsonErreur('Non autorisé', 403);
            $dossier = Database::queryOne("SELECT documents_valides FROM livreurs WHERE id=?", [$user['id']]);
            if (empty($dossier['documents_valides'])) {
                jsonErreur('Vos documents doivent être validés avant de demander une mission', 403);
            }
            $cmd_id = $input['commande_id'] ?? '';
            // Vérifier que la commande est toujours en attente
            $cmd = Database::queryOne("SELECT id,reference FROM commandes WHERE id=? AND statut='en_attente'", [$cmd_id]);
            if (!$cmd) jsonErreur('Cette mission n\'est plus disponible');
            // Notifier les admins
            $admins = Database::query("SELECT id FROM utilisateurs WHERE role='admin'");
            $livreur_nom = Database::queryOne("SELECT CONCAT(prenom,' ',nom) AS nom FROM utilisateurs WHERE id=?", [$user['id']])['nom'] ?? 'Livreur';
            foreach ($admins as $adm) {
                Database::execute(
                    "INSERT INTO notifications (id,utilisateur_id,type,titre,message,donnees_json) VALUES (?,?,'commande','🏍️ Livreur intéressé',?,?)",
                    [uniqid('n-',true), $adm['id'],
                     "$livreur_nom souhaite prendre la commande {$cmd['reference']}",
                     json_encode(['commande_id'=>$cmd_id,'livreur_id'=>$user['id']])]);
            }
            jsonSucces('Intérêt signalé aux admins');

        // ====================================================
        //  GESTION ADMINS (super-admin uniquement)
        // ====================================================
        case 'creer_admin':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            if (!estSuperAdmin()) jsonErreur('Seul le super-admin peut créer des admins', 403);

            $prenom = trim($input['prenom'] ?? '');
            $nom    = trim($input['nom']    ?? '');
            $email  = trim($input['email']  ?? '');
            $tel    = trim($input['telephone'] ?? '');
            $mdp    = $input['mot_de_passe'] ?? '';
            $droits = $input['droits'] ?? [];

            if (!$prenom||!$nom||!$email||!$mdp) jsonErreur('Champs obligatoires manquants');
            $existe = Database::queryOne("SELECT id FROM utilisateurs WHERE email=? OR telephone=?", [$email,$tel]);
            if ($existe) jsonErreur('Email ou téléphone déjà utilisé');

            $id   = uniqid('u-admin-', true);
            $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost'=>12]);
            $droits_json = !empty($droits) ? json_encode($droits) : null;
            if (Database::columnExists('utilisateurs', 'super_admin') && Database::columnExists('utilisateurs', 'droits_json')) {
                Database::execute(
                    "INSERT INTO utilisateurs (id,nom,prenom,email,telephone,mot_de_passe,role,super_admin,droits_json,email_verifie,tel_verifie)
                     VALUES (?,?,?,?,?,?,'admin',0,?,1,1)",
                    [$id,$nom,$prenom,$email,$tel,$hash,$droits_json]);
            } else {
                Database::execute(
                    "INSERT INTO utilisateurs (id,nom,prenom,email,telephone,mot_de_passe,role,email_verifie,tel_verifie)
                     VALUES (?,?,?,?,?,?,'admin',1,1)",
                    [$id,$nom,$prenom,$email,$tel,$hash]);
            }
            jsonSucces('Compte admin créé', ['id'=>$id]);

        case 'modifier_droits_admin':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            if (!estSuperAdmin()) jsonErreur('Seul le super-admin peut modifier les droits', 403);
            if (!Database::columnExists('utilisateurs', 'super_admin') || !Database::columnExists('utilisateurs', 'droits_json')) {
                jsonSucces('Droits ignorés : la base actuelle ne gère pas les droits admin détaillés');
            }
            $cible_id = $input['admin_id'] ?? '';
            // Ne peut pas modifier un autre super-admin
            $cible = Database::queryOne("SELECT super_admin FROM utilisateurs WHERE id=?", [$cible_id]);
            if ($cible && $cible['super_admin']) jsonErreur('Impossible de modifier un super-admin');
            $droits_json = !empty($input['droits']) ? json_encode($input['droits']) : null;
            Database::execute("UPDATE utilisateurs SET droits_json=? WHERE id=? AND role='admin' AND super_admin=0",
                [$droits_json, $cible_id]);
            jsonSucces('Droits mis à jour');

        case 'liste_admins':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            if (Database::columnExists('utilisateurs', 'super_admin') && Database::columnExists('utilisateurs', 'droits_json')) {
                $admins = Database::query(
                    "SELECT id, nom, prenom, email, telephone, super_admin, droits_json, cree_le
                     FROM utilisateurs WHERE role='admin' ORDER BY super_admin DESC, nom");
            } else {
                $admins = Database::query(
                    "SELECT id, nom, prenom, email, telephone, 1 AS super_admin, NULL AS droits_json, cree_le
                     FROM utilisateurs WHERE role='admin' ORDER BY nom");
            }
            foreach ($admins as &$a) {
                $a['droits'] = $a['droits_json'] ? json_decode($a['droits_json'], true) : [];
                unset($a['droits_json']);
            }
            jsonReponse($admins);

        case 'supprimer_admin':
            if (!$user || $user['role']!=='admin') jsonErreur('Non autorisé', 403);
            if (!estSuperAdmin()) jsonErreur('Seul le super-admin peut supprimer des admins', 403);
            $cible_id = $input['admin_id'] ?? '';
            if ($cible_id === $user['id']) jsonErreur('Vous ne pouvez pas vous supprimer vous-même');
            if (Database::columnExists('utilisateurs', 'super_admin')) {
                $cible = Database::queryOne("SELECT super_admin FROM utilisateurs WHERE id=?", [$cible_id]);
                if ($cible && $cible['super_admin']) jsonErreur('Impossible de supprimer un super-admin');
                Database::execute("DELETE FROM utilisateurs WHERE id=? AND role='admin' AND super_admin=0", [$cible_id]);
            } else {
                Database::execute("DELETE FROM utilisateurs WHERE id=? AND role='admin'", [$cible_id]);
            }
            jsonSucces('Admin supprimé');

        // ---- Compter notifications non lues (polling badge) ----
        case 'notifs_count':
            if (!$user) jsonReponse(['nb' => 0]);
            $row = Database::queryOne(
                "SELECT COUNT(*) AS nb FROM notifications WHERE utilisateur_id=? AND lu=0",
                [$user['id']]);
            jsonReponse(['nb' => (int)($row['nb'] ?? 0)]);

        // ---- Adresses client ----
        case 'adresses_liste':
            if (!$user) jsonErreur('Non connecté', 401);
            jsonReponse(Database::query(
                "SELECT * FROM adresses WHERE utilisateur_id=? ORDER BY principale DESC",
                [$user['id']]));

        case 'adresse_ajouter':
            if (!$user) jsonErreur('Non connecté', 401);
            $adr_id = uniqid('adr-', true);
            // Si principale, décocher les autres
            if (!empty($input['principale'])) {
                Database::execute("UPDATE adresses SET principale=0 WHERE utilisateur_id=?", [$user['id']]);
            }
            Database::execute(
                "INSERT INTO adresses (id,utilisateur_id,label,rue,quartier,ville,principale)
                 VALUES (?,?,?,?,?,?,?)",
                [$adr_id, $user['id'],
                 trim($input['label'] ?? 'Adresse'),
                 trim($input['rue'] ?? ''),
                 trim($input['quartier'] ?? ''),
                 trim($input['ville'] ?? 'Dakar'),
                 empty($input['principale']) ? 0 : 1]);
            jsonSucces('Adresse ajoutée', ['id' => $adr_id]);

        case 'adresse_supprimer':
            if (!$user) jsonErreur('Non connecté', 401);
            Database::execute(
                "DELETE FROM adresses WHERE id=? AND utilisateur_id=?",
                [$input['id'] ?? '', $user['id']]);
            jsonSucces('Adresse supprimée');

        default:
            jsonErreur("Action '$action' inconnue", 404);
    }
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    jsonErreur('Erreur base de données', 500);
} catch (Exception $e) {
    jsonErreur($e->getMessage(), 500);
} catch (Throwable $e) {
    error_log("API Fatal Error: " . $e->getMessage());
    jsonErreur('Erreur serveur : ' . $e->getMessage(), 500);
}
