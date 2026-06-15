# RapidLiv PHP — Guide complet jusqu'au déploiement

## Stack technique
- **Backend** : PHP 8.1+ (natif, aucun framework)
- **Base de données** : MySQL 8.0+
- **Frontend** : HTML5 + CSS3 + JavaScript vanilla
- **Serveur** : Apache (XAMPP local / hébergeur mutualisé / VPS)

---

## Structure du projet

```
rapidliv-php/
├── .htaccess                   ← Règles Apache (sécurité + URL)
├── database.sql                ← Schéma + données de test MySQL
├── api/
│   ├── api.php                 ← API AJAX unifiée (toutes les actions)
│   └── auth.php                ← Déconnexion
├── includes/
│   ├── config.php              ← Configuration (BDD, constantes)
│   ├── Database.php            ← Classe PDO singleton
│   ├── functions.php           ← Fonctions utilitaires
│   ├── header.php              ← En-tête HTML global
│   └── footer.php              ← Pied de page HTML
├── pages/
│   ├── accueil.php             ← Page d'accueil publique
│   ├── connexion.php           ← Formulaire de connexion
│   ├── inscription.php         ← Formulaire d'inscription
│   ├── client/
│   │   ├── accueil.php         ← Tableau de bord client
│   │   ├── shop.php            ← Catalogue d'un service
│   │   ├── panier.php          ← Panier et commande
│   │   ├── commandes.php       ← Historique commandes
│   │   ├── suivi.php           ← Suivi commande en cours
│   │   └── profil.php          ← Profil client
│   ├── livreur/
│   │   ├── dashboard.php       ← Tableau de bord livreur
│   │   ├── missions.php        ← Missions disponibles
│   │   ├── historique.php      ← Historique livraisons
│   │   └── profil.php          ← Profil livreur
│   └── admin/
│       ├── dashboard.php       ← Tableau de bord admin
│       ├── commandes.php       ← Gestion commandes
│       ├── livreurs.php        ← Gestion livreurs
│       ├── services.php        ← Gestion services
│       ├── stats.php           ← Statistiques
│       └── parametres.php      ← Configuration
└── public/
    ├── index.php               ← Routeur principal
    ├── css/
    │   └── app.css             ← Styles complets
    └── js/
        └── app.js              ← JavaScript global
```

---

## ÉTAPE 1 — Installation en local (XAMPP)

### 1.1 Installer XAMPP
- Télécharger : https://www.apachefriends.org/fr/index.html
- Installer et démarrer Apache + MySQL depuis le panneau de contrôle

### 1.2 Copier le projet
```bash
# Copier le dossier rapidliv-php dans :
# Windows : C:\xampp\htdocs\rapidliv-php
# Linux   : /opt/lampp/htdocs/rapidliv-php
# macOS   : /Applications/XAMPP/htdocs/rapidliv-php
```

### 1.3 Créer la base de données
```
1. Ouvrir http://localhost/phpmyadmin
2. Cliquer "Nouvelle base de données"
3. Nom : rapidliv → Créer
4. Onglet "Importer" → Choisir database.sql → Exécuter
```

Ou via ligne de commande :
```bash
mysql -u root -p < database.sql
```

### 1.4 Configurer l'application
Éditer `includes/config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rapidliv');
define('DB_USER', 'root');
define('DB_PASS', '');          // Laisser vide sur XAMPP par défaut
define('APP_URL', 'http://localhost/rapidliv-php/public');
```

### 1.5 Accéder à l'application
```
http://localhost/rapidliv-php/public/index.php
```

---

## Comptes de test

| Rôle    | Email                    | Mot de passe |
|---------|--------------------------|--------------|
| Client  | moussa.k@email.sn        | password     |
| Client  | fatou.s@email.sn         | password     |
| Livreur | ibou.d@livreur.sn        | password     |
| Admin   | admin@rapidliv.sn        | password     |

---

## ÉTAPE 2 — Déploiement sur hébergeur mutualisé (ex: Hostinger, o2switch, Herbergeur.sn)

### 2.1 Préparer les fichiers
```bash
# Modifier includes/config.php avec les infos du vrai serveur
define('APP_URL', 'https://votre-domaine.sn/public');
define('DB_HOST', 'localhost');      # Souvent localhost chez les hébergeurs
define('DB_NAME', 'votre_bdd');
define('DB_USER', 'votre_user_bdd');
define('DB_PASS', 'votre_mdp_bdd');
```

### 2.2 Uploader via FTP (FileZilla)
```
Hôte : ftp.votre-domaine.sn
Identifiant + mot de passe FTP (dans cPanel de l'hébergeur)
Port : 21

Uploader tout le dossier rapidliv-php/ dans public_html/
```

### 2.3 Créer la base de données chez l'hébergeur
```
1. cPanel → MySQL Database Wizard
2. Créer une BDD : ex. user_rapidliv
3. Créer un utilisateur MySQL avec mot de passe fort
4. Donner tous les privilèges à l'utilisateur sur la BDD
5. Importer database.sql via phpMyAdmin
```

### 2.4 Pointer le domaine
Si votre site est dans `public_html/rapidliv-php/public/` :
Modifier `.htaccess` à la racine de `public_html` :
```apache
RewriteEngine On
RewriteRule ^(.*)$ rapidliv-php/public/index.php [QSA,L]
```

Ou déplacer le contenu de `public/` directement dans `public_html/`.

### 2.5 Vérifier les permissions
```bash
chmod 755 public/
chmod 644 public/index.php
chmod 755 api/
chmod 644 api/api.php
chmod 600 includes/config.php   # Lecture uniquement par PHP
```

---

## ÉTAPE 3 — Déploiement sur VPS (Ubuntu + Apache)

### 3.1 Préparer le serveur
```bash
# Connexion SSH
ssh root@votre-ip-vps

# Mettre à jour
apt update && apt upgrade -y

# Installer la stack LAMP
apt install apache2 mysql-server php8.2 php8.2-mysql php8.2-mbstring php8.2-xml libapache2-mod-php -y

# Activer mod_rewrite
a2enmod rewrite
systemctl restart apache2
```

### 3.2 Créer la base de données
```bash
mysql -u root -p
CREATE DATABASE rapidliv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rapidliv_user'@'localhost' IDENTIFIED BY 'MotDePasseFort123!';
GRANT ALL PRIVILEGES ON rapidliv.* TO 'rapidliv_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importer le schéma
mysql -u rapidliv_user -p rapidliv < /var/www/rapidliv-php/database.sql
```

### 3.3 Déployer les fichiers
```bash
# Copier le projet
cp -r rapidliv-php/ /var/www/rapidliv-php/
chown -R www-data:www-data /var/www/rapidliv-php/
chmod -R 755 /var/www/rapidliv-php/
chmod 600 /var/www/rapidliv-php/includes/config.php
```

### 3.4 Configurer Apache VirtualHost
```bash
nano /etc/apache2/sites-available/rapidliv.conf
```
```apache
<VirtualHost *:80>
    ServerName votre-domaine.sn
    ServerAlias www.votre-domaine.sn
    DocumentRoot /var/www/rapidliv-php/public

    <Directory /var/www/rapidliv-php/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/rapidliv-error.log
    CustomLog ${APACHE_LOG_DIR}/rapidliv-access.log combined
</VirtualHost>
```
```bash
a2ensite rapidliv.conf
a2dissite 000-default.conf
systemctl reload apache2
```

### 3.5 Mettre config.php à jour
```php
define('APP_URL', 'https://votre-domaine.sn');
define('DB_USER', 'rapidliv_user');
define('DB_PASS', 'MotDePasseFort123!');
```

### 3.6 Installer SSL (HTTPS gratuit avec Let's Encrypt)
```bash
apt install certbot python3-certbot-apache -y
certbot --apache -d votre-domaine.sn -d www.votre-domaine.sn
# Renouvellement automatique
certbot renew --dry-run
```

---

## ÉTAPE 4 — Checklist avant mise en production

### Sécurité
- [ ] Changer `JWT_SECRET` dans config.php
- [ ] Désactiver `display_errors` (passer ENV en 'production')
- [ ] Mettre un mot de passe fort pour la BDD
- [ ] Activer HTTPS (certificat SSL)
- [ ] Vérifier les permissions fichiers (600 pour config.php)
- [ ] Supprimer les comptes de test ou changer leurs mots de passe

### Performance
- [ ] Activer le cache PHP (`opcache` dans php.ini)
- [ ] Activer gzip Apache (`a2enmod deflate`)
- [ ] Mettre en cache les assets CSS/JS

### Fonctionnel
- [ ] Tester la connexion/inscription
- [ ] Passer une commande de bout en bout
- [ ] Vérifier les notifications
- [ ] Tester sur mobile (responsive)

### Base de données
- [ ] Planifier des sauvegardes automatiques
```bash
# Crontab pour backup quotidien
crontab -e
0 2 * * * mysqldump -u rapidliv_user -pMotDePasse rapidliv > /backup/rapidliv_$(date +\%Y\%m\%d).sql
```

---

## Variables d'environnement clés (config.php)

| Constante       | Valeur locale       | Valeur production              |
|----------------|---------------------|-------------------------------|
| ENV             | development         | production                    |
| APP_URL         | http://localhost/.. | https://votre-domaine.sn       |
| DB_PASS         | (vide)              | Mot de passe fort              |
| JWT_SECRET      | rapidliv_secret_... | Chaîne aléatoire 64 caractères |
| BCRYPT_COST     | 12                  | 12 (ne pas changer)           |

---

## Support et prochaines étapes

### Intégrations à ajouter
- **Orange Money** : API Côte d'Ivoire/Sénégal → https://developer.orange.com
- **Wave** : API Wave Sénégal
- **SMS** : Twilio ou Afrika's Talking pour les SMS
- **Google Maps** : Tracking GPS réel (remplacer le placeholder)
- **Upload photos** : Pour les preuves de livraison

### Améliorations possibles
- Ajouter un système de codes promo
- Notifications push navigateur (Service Worker)
- Application mobile (PWA)
- Tableau de bord livreur sur mobile
- Système d'abonnement mensuel
