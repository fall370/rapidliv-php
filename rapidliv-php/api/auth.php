<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

demarrerSession();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'deconnexion') {
    deconnecterUtilisateur();
    rediriger('/rapidliv-php/public/index.php?page=connexion');
}
