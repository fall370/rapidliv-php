<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RapidLiv — Installation</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f5f7fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:16px;padding:36px;width:100%;max-width:460px;box-shadow:0 4px 24px rgba(0,0,0,.08);border:1px solid #e5e7eb}
.logo{font-size:28px;font-weight:800;color:#1D9E75;text-align:center;margin-bottom:6px}.logo span{color:#D85A30}
.subtitle{text-align:center;color:#6b7280;font-size:14px;margin-bottom:28px}
.step-badge{background:#E1F5EE;color:#0F6E56;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;display:inline-block;margin-bottom:20px}
label{display:block;font-size:13px;font-weight:600;color:#4b5563;margin-bottom:5px;margin-top:14px}
input{width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;transition:.15s;font-family:inherit}
input:focus{outline:none;border-color:#1D9E75;box-shadow:0 0 0 3px rgba(29,158,117,.12)}
.btn{width:100%;padding:12px;background:#1D9E75;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;margin-top:20px;transition:.15s}
.btn:hover{background:#0F6E56}
.btn:disabled{opacity:.6;cursor:not-allowed}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-top:16px;display:none}
.alert-success{background:#E1F5EE;color:#0F6E56;border:1px solid #9FE1CB}
.alert-error  {background:#FCEBEB;color:#A32D2D;border:1px solid #F09595}
.warning-box{background:#FAEEDA;border:1px solid #FAC775;border-radius:8px;padding:12px 16px;font-size:13px;color:#633806;margin-bottom:20px}
.check-list{list-style:none;margin:8px 0}
.check-list li{padding:3px 0;font-size:13px;color:#4b5563}
.check-list li::before{content:'✓ ';color:#1D9E75;font-weight:700}
.divider{height:1px;background:#e5e7eb;margin:20px 0}
.hint{font-size:12px;color:#9ca3af;margin-top:4px}
a{color:#1D9E75}
</style>
</head>
<body>
<?php
// ============================================================
//  RAPIDLIV — Page d'installation (créer le compte admin)
//  Fichier : install.php
//  ⚠️  SUPPRIMER CE FICHIER après utilisation !
// ============================================================

// Sécurité : fichier utilisable une seule fois
// (désactiver après usage en ajoutant un fichier .installed)
if (file_exists(__DIR__ . '/.installed')) {
    die('<div style="font-family:sans-serif;text-align:center;padding:60px;color:#A32D2D;background:#FCEBEB;min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:40px">🔒</div><h2>Installation déjà effectuée</h2><p style="margin-top:8px">Ce fichier a été désactivé pour des raisons de sécurité.</p><p><a href="public/index.php?page=connexion" style="color:#1D9E75">→ Aller à la connexion</a></p></div>');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$type    = '';
$succes  = false;

// Vérifier la connexion BDD
$bdd_ok = false;
try {
    Database::getInstance();
    $bdd_ok = true;
} catch (Exception $e) {
    $bdd_ok = false;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bdd_ok) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $tel    = trim($_POST['telephone'] ?? '');
    $mdp    = $_POST['mot_de_passe']  ?? '';
    $mdp2   = $_POST['confirmation']  ?? '';
    $cle    = trim($_POST['cle_installation'] ?? '');

    // Clé d'installation (protection basique)
    $CLE_REQUISE = 'rapidliv2024';

    if ($cle !== $CLE_REQUISE) {
        $message = "Clé d'installation incorrecte.";
        $type    = 'error';
    } elseif (!$prenom || !$nom || !$email || !$tel || !$mdp) {
        $message = 'Tous les champs sont obligatoires.';
        $type    = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse email invalide.';
        $type    = 'error';
    } elseif (strlen($mdp) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $type    = 'error';
    } elseif ($mdp !== $mdp2) {
        $message = 'Les deux mots de passe ne correspondent pas.';
        $type    = 'error';
    } else {
        // Vérifier si un admin existe déjà
        $admin_existe = Database::queryOne(
            "SELECT id FROM utilisateurs WHERE role='admin' LIMIT 1");

        if ($admin_existe) {
            $message = "Un compte administrateur existe déjà. Connectez-vous avec les identifiants existants.";
            $type    = 'error';
        } else {
            try {
                $id   = 'u-admin-' . substr(uniqid(), -8);
                $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);

                Database::execute(
                    "INSERT INTO utilisateurs (id, nom, prenom, email, telephone, mot_de_passe, role, email_verifie, tel_verifie)
                     VALUES (?, ?, ?, ?, ?, ?, 'admin', 1, 1)",
                    [$id, $nom, $prenom, $email, $tel, $hash]);

                // Marquer comme installé
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s') . ' — Admin créé : ' . $email);

                $message = "Compte administrateur créé avec succès ! Vous pouvez maintenant vous connecter.";
                $type    = 'success';
                $succes  = true;

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $message = "Cet email ou téléphone est déjà utilisé.";
                } else {
                    $message = "Erreur base de données : " . $e->getMessage();
                }
                $type = 'error';
            }
        }
    }
}
?>

<div class="card">
  <div class="logo">Rapid<span>Liv</span></div>
  <div class="subtitle">Assistant d'installation</div>

  <?php if ($succes): ?>
  <!-- Succès -->
  <div class="alert alert-success" style="display:block">
    ✅ <?= htmlspecialchars($message) ?>
  </div>
  <div class="divider"></div>
  <div style="font-size:14px;color:#4b5563;margin-bottom:16px">
    <strong>Votre compte admin :</strong><br>
    Email : <strong><?= htmlspecialchars($_POST['email']) ?></strong><br>
    Mot de passe : celui que vous venez de définir
  </div>
  <div class="warning-box">
    ⚠️ <strong>Sécurité :</strong> Ce fichier <code>install.php</code> a été automatiquement désactivé.
    Il est recommandé de le <strong>supprimer définitivement</strong> de votre serveur.
  </div>
  <a href="public/index.php?page=connexion">
    <button class="btn" style="margin-top:0">→ Aller à la connexion</button>
  </a>

  <?php else: ?>

  <?php if (!$bdd_ok): ?>
  <div class="warning-box">
    ❌ <strong>Base de données non connectée.</strong><br>
    Vérifiez les paramètres dans <code>includes/config.php</code> et assurez-vous que MySQL est démarré.
  </div>
  <?php else: ?>
  <div class="step-badge">✓ Base de données connectée</div>
  <?php endif; ?>

  <div class="warning-box">
    ⚠️ <strong>Utilisez cette page une seule fois.</strong>
    Elle sera automatiquement désactivée après création du compte.
  </div>

  <?php if ($message && !$succes): ?>
  <div class="alert alert-error" style="display:block;margin-bottom:16px">❌ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:4px">Clé d'installation</div>
    <input type="text" name="cle_installation" placeholder="Entrez la clé : rapidliv2024" required>
    <div class="hint">Clé par défaut : <strong>rapidliv2024</strong> — changez-la dans ce fichier en production</div>

    <div class="divider"></div>

    <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:2px">Informations du compte admin</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px">
      <div>
        <label>Prénom</label>
        <input type="text" name="prenom" placeholder="Moussa" value="<?= htmlspecialchars($_POST['prenom']??'') ?>" required>
      </div>
      <div>
        <label>Nom</label>
        <input type="text" name="nom" placeholder="Diallo" value="<?= htmlspecialchars($_POST['nom']??'') ?>" required>
      </div>
    </div>

    <label>Email administrateur</label>
    <input type="email" name="email" placeholder="admin@votresite.sn" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>

    <label>Téléphone</label>
    <input type="tel" name="telephone" placeholder="+221 77 xxx xx xx" value="<?= htmlspecialchars($_POST['telephone']??'') ?>" required>

    <label>Mot de passe (min. 8 caractères)</label>
    <input type="password" name="mot_de_passe" placeholder="••••••••••" required minlength="8">

    <label>Confirmer le mot de passe</label>
    <input type="password" name="confirmation" placeholder="••••••••••" required>

    <button type="submit" class="btn" <?= !$bdd_ok?'disabled':'' ?>>
      Créer le compte administrateur
    </button>
  </form>

  <div class="divider"></div>
  <div style="font-size:12px;color:#9ca3af;text-align:center">
    Déjà un compte ? <a href="public/index.php?page=connexion">Se connecter</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
