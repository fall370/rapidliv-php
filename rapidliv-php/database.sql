-- ============================================================
--  RAPIDLIV — Base de données MySQL complète
--  Exécuter : mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `rapidliv`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rapidliv`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
--  NETTOYAGE
-- ============================================================
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS paiements;
DROP TABLE IF EXISTS livraisons;
DROP TABLE IF EXISTS commande_items;
DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS categories_produit;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS categories_service;
DROP TABLE IF EXISTS adresses;
DROP TABLE IF EXISTS livreurs;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS zones;
DROP TABLE IF EXISTS parametres;

-- ============================================================
--  ZONES DE LIVRAISON
-- ============================================================
CREATE TABLE zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    ville       VARCHAR(100) NOT NULL DEFAULT 'Dakar',
    frais_base  INT UNSIGNED NOT NULL DEFAULT 1500,
    rayon_km    DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    actif       TINYINT(1)   NOT NULL DEFAULT 1,
    cree_le     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  UTILISATEURS
-- ============================================================
CREATE TABLE utilisateurs (
    id              CHAR(36)     NOT NULL PRIMARY KEY,
    nom             VARCHAR(100) NOT NULL,
    prenom          VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    telephone       VARCHAR(20)  NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    role            ENUM('client','livreur','admin') NOT NULL DEFAULT 'client',
    photo_url       VARCHAR(500) DEFAULT NULL,
    actif           TINYINT(1)   NOT NULL DEFAULT 1,
    email_verifie   TINYINT(1)   NOT NULL DEFAULT 0,
    tel_verifie     TINYINT(1)   NOT NULL DEFAULT 0,
    token_reset     VARCHAR(100) DEFAULT NULL,
    token_expire    DATETIME     DEFAULT NULL,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  ADRESSES
-- ============================================================
CREATE TABLE adresses (
    id              CHAR(36)     NOT NULL PRIMARY KEY,
    utilisateur_id  CHAR(36)     NOT NULL,
    label           VARCHAR(50)  NOT NULL DEFAULT 'Domicile',
    rue             VARCHAR(255) NOT NULL,
    quartier        VARCHAR(100) DEFAULT NULL,
    ville           VARCHAR(100) NOT NULL DEFAULT 'Dakar',
    pays            VARCHAR(50)  NOT NULL DEFAULT 'Sénégal',
    latitude        DECIMAL(10,8) DEFAULT NULL,
    longitude       DECIMAL(11,8) DEFAULT NULL,
    principale      TINYINT(1)   NOT NULL DEFAULT 0,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  LIVREURS
-- ============================================================
CREATE TABLE livreurs (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    zone_id             INT UNSIGNED DEFAULT NULL,
    type_vehicule       VARCHAR(50)  NOT NULL DEFAULT 'Moto',
    numero_permis       VARCHAR(50)  DEFAULT NULL,
    numero_assurance    VARCHAR(50)  DEFAULT NULL,
    carte_identite_fichier VARCHAR(255) DEFAULT NULL,
    statut              ENUM('disponible','en_course','hors_ligne','suspendu') NOT NULL DEFAULT 'hors_ligne',
    position_lat        DECIMAL(10,8) DEFAULT NULL,
    position_lng        DECIMAL(11,8) DEFAULT NULL,
    position_maj_le     DATETIME     DEFAULT NULL,
    note_moyenne        DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    nb_evaluations      INT UNSIGNED NOT NULL DEFAULT 0,
    nb_livraisons_total INT UNSIGNED NOT NULL DEFAULT 0,
    gains_total         INT UNSIGNED NOT NULL DEFAULT 0,
    documents_valides   TINYINT(1)   NOT NULL DEFAULT 0,
    documents_statut    ENUM('en_attente','valides','rejetes') NOT NULL DEFAULT 'en_attente',
    documents_motif     VARCHAR(500) DEFAULT NULL,
    documents_verifies_le DATETIME DEFAULT NULL,
    documents_verifies_par CHAR(36) DEFAULT NULL,
    cree_le             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id)      REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  CATÉGORIES DE SERVICES
-- ============================================================
CREATE TABLE categories_service (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom     VARCHAR(100) NOT NULL UNIQUE,
    icone   VARCHAR(10)  NOT NULL DEFAULT '🏪',
    ordre   INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SERVICES (boutiques partenaires)
-- ============================================================
CREATE TABLE services (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id    INT UNSIGNED NOT NULL,
    zone_id         INT UNSIGNED DEFAULT NULL,
    nom             VARCHAR(200) NOT NULL,
    description     TEXT         DEFAULT NULL,
    logo_url        VARCHAR(500) DEFAULT NULL,
    adresse         VARCHAR(255) DEFAULT NULL,
    latitude        DECIMAL(10,8) DEFAULT NULL,
    longitude       DECIMAL(11,8) DEFAULT NULL,
    telephone       VARCHAR(20)  DEFAULT NULL,
    email           VARCHAR(255) DEFAULT NULL,
    heure_ouverture TIME         NOT NULL DEFAULT '08:00:00',
    heure_fermeture TIME         NOT NULL DEFAULT '22:00:00',
    delai_min       INT UNSIGNED NOT NULL DEFAULT 15,
    delai_max       INT UNSIGNED NOT NULL DEFAULT 45,
    commande_min    INT UNSIGNED NOT NULL DEFAULT 1000,
    note_moyenne    DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    nb_evaluations  INT UNSIGNED NOT NULL DEFAULT 0,
    actif           TINYINT(1)   NOT NULL DEFAULT 1,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories_service(id),
    FOREIGN KEY (zone_id)      REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  CATÉGORIES PRODUITS
-- ============================================================
CREATE TABLE categories_produit (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    nom        VARCHAR(100) NOT NULL,
    ordre      INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  PRODUITS
-- ============================================================
CREATE TABLE produits (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id      INT UNSIGNED NOT NULL,
    categorie_id    INT UNSIGNED DEFAULT NULL,
    nom             VARCHAR(200) NOT NULL,
    description     TEXT         DEFAULT NULL,
    prix            INT UNSIGNED NOT NULL,
    prix_promo      INT UNSIGNED DEFAULT NULL,
    image_url       VARCHAR(500) DEFAULT NULL,
    icone           VARCHAR(10)  DEFAULT '📦',
    stock           INT          DEFAULT NULL,
    disponible      TINYINT(1)   NOT NULL DEFAULT 1,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id)   REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories_produit(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  COMMANDES
-- ============================================================
CREATE TABLE commandes (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    reference           VARCHAR(25)  NOT NULL UNIQUE,
    client_id           CHAR(36)     NOT NULL,
    service_id          INT UNSIGNED NOT NULL,
    adresse_livraison   VARCHAR(500) NOT NULL,
    adresse_lat         DECIMAL(10,8) DEFAULT NULL,
    adresse_lng         DECIMAL(11,8) DEFAULT NULL,
    instructions        TEXT         DEFAULT NULL,
    statut              ENUM('en_attente','confirmee','en_preparation','en_route','livree','annulee') NOT NULL DEFAULT 'en_attente',
    sous_total          INT UNSIGNED NOT NULL,
    frais_livraison     INT UNSIGNED NOT NULL DEFAULT 1500,
    reduction           INT UNSIGNED NOT NULL DEFAULT 0,
    total               INT UNSIGNED NOT NULL,
    annulation_raison   TEXT         DEFAULT NULL,
    cree_le             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmee_le        DATETIME     DEFAULT NULL,
    preparee_le         DATETIME     DEFAULT NULL,
    prise_en_charge_le  DATETIME     DEFAULT NULL,
    livree_le           DATETIME     DEFAULT NULL,
    annulee_le          DATETIME     DEFAULT NULL,
    FOREIGN KEY (client_id)  REFERENCES utilisateurs(id),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  DÉTAIL COMMANDES
-- ============================================================
CREATE TABLE commande_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id     CHAR(36)     NOT NULL,
    produit_id      INT UNSIGNED DEFAULT NULL,
    nom_produit     VARCHAR(200) NOT NULL,
    prix_unitaire   INT UNSIGNED NOT NULL,
    quantite        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    sous_total      INT UNSIGNED NOT NULL,
    options_json    TEXT         DEFAULT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)  REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  LIVRAISONS
-- ============================================================
CREATE TABLE livraisons (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    commande_id         CHAR(36)     NOT NULL UNIQUE,
    livreur_id          CHAR(36)     DEFAULT NULL,
    statut              ENUM('assignee','en_route','arrivee','livree','echouee') NOT NULL DEFAULT 'assignee',
    position_lat        DECIMAL(10,8) DEFAULT NULL,
    position_lng        DECIMAL(11,8) DEFAULT NULL,
    eta_minutes         INT UNSIGNED DEFAULT NULL,
    distance_km         DECIMAL(6,2) DEFAULT NULL,
    code_confirmation   VARCHAR(10)  DEFAULT NULL,
    photo_preuve_url    VARCHAR(500) DEFAULT NULL,
    assignee_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    prise_en_charge_le  DATETIME     DEFAULT NULL,
    livree_le           DATETIME     DEFAULT NULL,
    gain_livreur        INT UNSIGNED DEFAULT NULL,
    cree_le             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (livreur_id)  REFERENCES livreurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  PAIEMENTS
-- ============================================================
CREATE TABLE paiements (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    commande_id         CHAR(36)     NOT NULL,
    methode             ENUM('carte','orange_money','free_money','wave','cash') NOT NULL,
    montant             INT UNSIGNED NOT NULL,
    statut              ENUM('en_attente','autorise','capture','rembourse','echoue') NOT NULL DEFAULT 'en_attente',
    reference_externe   VARCHAR(200) DEFAULT NULL,
    metadonnees_json    TEXT         DEFAULT NULL,
    cree_le             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    mis_a_jour_le       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  ÉVALUATIONS
-- ============================================================
CREATE TABLE evaluations (
    id              CHAR(36)     NOT NULL PRIMARY KEY,
    commande_id     CHAR(36)     NOT NULL,
    auteur_id       CHAR(36)     NOT NULL,
    cible_type      ENUM('service','livreur') NOT NULL,
    cible_id        VARCHAR(50)  NOT NULL,
    note            TINYINT UNSIGNED NOT NULL,
    commentaire     TEXT         DEFAULT NULL,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (note BETWEEN 1 AND 5),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id)   REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id              CHAR(36)     NOT NULL PRIMARY KEY,
    utilisateur_id  CHAR(36)     NOT NULL,
    type            ENUM('commande','livraison','paiement','promo','systeme') NOT NULL,
    titre           VARCHAR(200) NOT NULL,
    message         TEXT         NOT NULL,
    lu              TINYINT(1)   NOT NULL DEFAULT 0,
    donnees_json    TEXT         DEFAULT NULL,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  PARAMÈTRES
-- ============================================================
CREATE TABLE parametres (
    cle         VARCHAR(100) NOT NULL PRIMARY KEY,
    valeur      TEXT         NOT NULL,
    description VARCHAR(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  INDEX
-- ============================================================
CREATE INDEX idx_commandes_client   ON commandes(client_id);
CREATE INDEX idx_commandes_service  ON commandes(service_id);
CREATE INDEX idx_commandes_statut   ON commandes(statut);
CREATE INDEX idx_commandes_cree_le  ON commandes(cree_le DESC);
CREATE INDEX idx_livraisons_livreur ON livraisons(livreur_id);
CREATE INDEX idx_produits_service   ON produits(service_id);
CREATE INDEX idx_notifs_user        ON notifications(utilisateur_id, lu);

-- ============================================================
--  DONNÉES DE TEST
-- ============================================================

INSERT INTO parametres VALUES
  ('frais_livraison',  '1500', 'Frais de base FCFA'),
  ('commission',       '10',   'Commission livreur %'),
  ('rayon_max',        '15',   'Rayon max livraison km'),
  ('auto_assign_livreur','1',   'Assignation automatique livreur 0/1'),
  ('maintenance',      '0',    'Mode maintenance 0/1');

INSERT INTO zones (nom, ville, frais_base, rayon_km) VALUES
  ('Plateau',    'Dakar', 1500, 5),
  ('Almadies',   'Dakar', 2000, 8),
  ('Ouakam',     'Dakar', 1800, 6),
  ('Parcelles',  'Dakar', 2500, 10),
  ('Pikine',     'Dakar', 3000, 15);

INSERT INTO categories_service (nom, icone, ordre) VALUES
  ('Restaurant',   '🍽️', 1),
  ('Supermarché',  '🛒', 2),
  ('Pharmacie',    '💊', 3),
  ('Électronique', '📱', 4),
  ('Boulangerie',  '🥖', 5),
  ('Épicerie',     '🏪', 6);

-- Utilisateurs (mot de passe = "password123")
INSERT INTO utilisateurs (id, nom, prenom, email, telephone, mot_de_passe, role, email_verifie, tel_verifie) VALUES
  ('u-client-001',  'Kouyaté', 'Moussa',   'moussa.k@email.sn',  '+221774567890', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client',  1, 1),
  ('u-client-002',  'Sarr',    'Fatou',    'fatou.s@email.sn',   '+221765432109', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client',  1, 1),
  ('u-livreur-001', 'Diallo',  'Ibrahima', 'ibou.d@livreur.sn',  '+221771234567', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur', 1, 1),
  ('u-livreur-002', 'Ba',      'Omar',     'omar.b@livreur.sn',  '+221762345678', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur', 1, 1),
  ('u-admin-001',   'Admin',   'RapidLiv', 'admin@rapidliv.sn',  '+221700000000', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   1, 1);

INSERT INTO adresses (id, utilisateur_id, label, rue, quartier, ville, latitude, longitude, principale) VALUES
  ('adr-001', 'u-client-001', 'Domicile', '23 Rue Vincens',             'Plateau',  'Dakar', 14.69370, -17.44410, 1),
  ('adr-002', 'u-client-001', 'Bureau',   'Av. Léopold Sédar Senghor', 'Plateau',  'Dakar', 14.69280, -17.44670, 0),
  ('adr-003', 'u-client-002', 'Domicile', '45 Rue de Thiong',           'Almadies', 'Dakar', 14.73490, -17.49980, 1);

INSERT INTO livreurs (id, zone_id, type_vehicule, statut, position_lat, position_lng, note_moyenne, nb_livraisons_total, documents_valides) VALUES
  ('u-livreur-001', 1, 'Moto', 'disponible', 14.69370, -17.44410, 4.80, 312, 1),
  ('u-livreur-002', 2, 'Vélo', 'en_course',  14.73490, -17.49980, 4.60, 187, 1);

INSERT INTO services (categorie_id, zone_id, nom, description, logo_url, adresse, latitude, longitude, telephone, delai_min, delai_max, commande_min, note_moyenne) VALUES
  (1, 1, 'Resto Dakar Saveurs',   'Cuisine sénégalaise authentique',  'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80', '12 Av. Pompidou',   14.6920, -17.4450, '+221338200001', 25, 45, 3000, 4.8),
  (2, 1, 'Superm. Ndiarigane',    'Supermarché de quartier',          'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80', '5 Rue Moussé Diop', 14.6930, -17.4430, '+221338200002', 20, 35, 2000, 4.7),
  (3, 1, 'Pharmacie Santé Plus',  'Médicaments et parapharmacie',     'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=900&q=80', '8 Rue Carnot',      14.6945, -17.4420, '+221338200003', 15, 25, 1500, 4.9),
  (4, 2, 'Tech Express',          'Électronique et accessoires',      'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80', '34 Av. C.A. Diop',  14.7260, -17.4600, '+221338200004', 30, 50, 5000, 4.5);

INSERT INTO categories_produit (service_id, nom, ordre) VALUES
  (1,'Plats',1),(1,'Boissons',2),
  (2,'Épicerie',1),(2,'Frais',2),(2,'Hygiène',3),
  (3,'Médicaments',1),(3,'Vitamines',2),
  (4,'Accessoires',1),(4,'Audio',2);

INSERT INTO produits (service_id, categorie_id, nom, description, prix, icone, image_url) VALUES
  (1,1,'Thiébou dieun','Riz au poisson, légumes et sauce tomate maison',4000,'🍛','https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&w=700&q=80'),
  (1,1,'Yassa poulet','Poulet mariné au citron, oignons confits et riz blanc',3500,'🍗','https://images.unsplash.com/photo-1598515214141-89d3c73ae83b?auto=format&fit=crop&w=700&q=80'),
  (1,1,'Mafé bœuf','Sauce arachide, bœuf tendre et riz parfumé',4500,'🥩','https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=700&q=80'),
  (1,2,'Bissap frais 33cl','Boisson hibiscus fraîche, légèrement sucrée',500,'🧃','https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=80'),
  (1,2,'Eau Kirène 0.5L','Bouteille d’eau minérale fraîche',300,'💧','https://images.unsplash.com/photo-1559839914-17aae19cec71?auto=format&fit=crop&w=700&q=80'),
  (2,3,'Lait Candia 1L','Lait UHT demi-écrémé',1200,'🥛','https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=700&q=80'),
  (2,3,'Pain de mie complet','Pain tranché moelleux pour petit déjeuner',800,'🍞','https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=700&q=80'),
  (2,4,'Œufs frais plateau 30','Œufs frais calibre moyen',2500,'🥚','https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=700&q=80'),
  (2,3,'Riz parfumé 5kg','Sac de riz parfumé long grain',4500,'🍚','https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=700&q=80'),
  (2,5,'Savon Lux x3','Lot de trois savons parfumés',1200,'🧴','https://images.unsplash.com/photo-1607006483224-4f75f8f8f0b5?auto=format&fit=crop&w=700&q=80'),
  (3,6,'Paracétamol 500mg','Boîte de comprimés, usage selon conseil médical',1500,'💊','https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=700&q=80'),
  (3,6,'Amoxicilline 250mg','Antibiotique délivré selon disponibilité pharmacie',3500,'💊','https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=700&q=80'),
  (3,7,'Vitamine C 1000mg','Comprimés effervescents vitamine C',3000,'🍊','https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&w=700&q=80'),
  (4,8,'Chargeur USB-C 25W','Chargeur rapide compatible smartphones USB-C',4500,'🔌','https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=700&q=80'),
  (4,9,'Écouteurs Bluetooth','Écouteurs sans fil avec boîtier de charge',18000,'🎧','https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&w=700&q=80'),
  (4,8,'Câble HDMI 2m','Câble HDMI haute vitesse pour TV et ordinateur',3500,'🖥️','https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=700&q=80');

-- Commandes démo
INSERT INTO commandes (id, reference, client_id, service_id, adresse_livraison, statut, sous_total, frais_livraison, total, cree_le) VALUES
  ('cmd-001','CMD-240115-A1','u-client-001',2,'23 Rue Vincens, Plateau','en_route',      6700,1500,8200, NOW() - INTERVAL 1 HOUR),
  ('cmd-002','CMD-240115-A2','u-client-002',3,'45 Rue de Thiong',       'livree',         4500,1500,6000, NOW() - INTERVAL 3 HOUR),
  ('cmd-003','CMD-240115-A3','u-client-001',1,'23 Rue Vincens, Plateau','en_preparation', 8000,1500,9500, NOW() - INTERVAL 20 MINUTE),
  ('cmd-004','CMD-240115-A4','u-client-002',2,'45 Rue de Thiong',       'en_attente',     3800,1500,5300, NOW() - INTERVAL 5 MINUTE);

INSERT INTO commande_items (commande_id, produit_id, nom_produit, prix_unitaire, quantite, sous_total) VALUES
  ('cmd-001',6,'Lait Candia 1L',1200,2,2400),
  ('cmd-001',7,'Pain de mie',800,1,800),
  ('cmd-001',8,'Œufs (plateau 30)',2500,1,2500),
  ('cmd-002',11,'Paracétamol 500mg',1500,1,1500),
  ('cmd-002',13,'Vitamine C 1000mg',3000,1,3000),
  ('cmd-003',1,'Thiébou dieun',4000,2,8000),
  ('cmd-004',9,'Riz parfumé 5kg',4500,1,4500);

INSERT INTO livraisons (id, commande_id, livreur_id, statut, eta_minutes, code_confirmation, gain_livreur) VALUES
  ('liv-001','cmd-001','u-livreur-001','en_route',12,'4821',820),
  ('liv-002','cmd-002','u-livreur-002','livree',NULL,'3319',600);

INSERT INTO paiements (id, commande_id, methode, montant, statut) VALUES
  ('pay-001','cmd-001','orange_money',8200,'capture'),
  ('pay-002','cmd-002','cash',6000,'capture'),
  ('pay-003','cmd-003','carte',9500,'autorise'),
  ('pay-004','cmd-004','cash',5300,'en_attente');

INSERT INTO evaluations (id, commande_id, auteur_id, cible_type, cible_id, note, commentaire) VALUES
  ('ev-001','cmd-002','u-client-002','livreur','u-livreur-002',5,'Très rapide, merci !'),
  ('ev-002','cmd-002','u-client-002','service','2',5,'Médicaments bien emballés.');

INSERT INTO notifications (id, utilisateur_id, type, titre, message) VALUES
  ('n-001','u-client-001','livraison','Livreur en route !','Ibou D. est en route avec votre commande.'),
  ('n-002','u-client-001','commande','Commande confirmée','Votre commande est en préparation.'),
  ('n-003','u-livreur-001','commande','Nouvelle mission !','Une commande vous a été assignée.');

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'Base de données RapidLiv initialisée avec succès !' AS message;
