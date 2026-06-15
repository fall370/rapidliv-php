</main>

<!--<footer class="footer">
</footer>-->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    /* --- CODES CSS DU FOOTER --- */
    .main-footer {
        background-color: #111827; /* Noir asphalte professionnel */
        color: #9ca3af; /* Texte gris clair */
        padding: 60px 0 20px 0;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        width: 100%;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Section supérieure : Logo et téléchargement */
    .footer-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        border-bottom: 1px solid #1f2937;
        padding-bottom: 40px;
        margin-bottom: 40px;
    }

    .footer-brand {
        max-width: 350px;
        margin-bottom: 20px;
    }

    .footer-brand h2 {
        color: #ffffff;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .footer-brand h2 span {
        color: #00ff66; /* Couleur d'accentuation (Vert livraison) */
    }

    .footer-download {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .btn-app {
        display: flex;
        align-items: center;
        background-color: #1f2937;
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: background 0.3s, transform 0.2s;
        border: 1px solid #374151;
    }

    .btn-app:hover {
        background-color: #374151;
        transform: translateY(-2px);
    }

    .btn-app i {
        font-size: 24px;
        margin-right: 12px;
    }

    .btn-text {
        display: flex;
        flex-direction: column;
    }

    .btn-text .small-txt {
        font-size: 10px;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .btn-text .big-txt {
        font-size: 14px;
        font-weight: 500;
    }

    /* Section du milieu : Les menus */
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-bottom: 40px;
    }

    .footer-column h3 {
        color: #ffffff;
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 20px;
    }

    .footer-column ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-column ul li {
        margin-bottom: 12px;
    }

    .footer-column ul li a {
        color: #9ca3af;
        text-decoration: none;
        transition: color 0.2s;
    }

    .footer-column ul li a:hover {
        color: #00ff66;
    }

    /* Section inférieure : Réseaux et mentions légales */
    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        border-top: 1px solid #1f2937;
        padding-top: 25px;
    }

    .footer-socials {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }

    .footer-socials a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color: #1f2937;
        color: #ffffff;
        border-radius: 50%;
        text-decoration: none;
        transition: background 0.3s, color 0.3s;
    }

    .footer-socials a:hover {
        background-color: #00ff66;
        color: #111827;
    }

    .footer-legal-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .footer-legal-links a {
        color: #6b7280;
        text-decoration: none;
        font-size: 13px;
    }

    .footer-legal-links a:hover {
        color: #9ca3af;
    }

    .copyright {
        color: #6b7280;
        font-size: 13px;
        width: 100%;
        margin-top: 15px;
        text-align: center;
    }

    /* --- RESPONSIVE MOBILE --- */
    @media (max-width: 900px) {
        .footer-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .footer-top {
            flex-direction: column;
            align-items: flex-start;
        }
        .footer-download {
            margin-top: 20px;
        }
    }

    @media (max-width: 500px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
        .footer-bottom {
            flex-direction: column;
            align-items: flex-start;
        }
        .footer-legal-links {
            margin-top: 15px;
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<footer class="main-footer">
    <div class="footer-container">
        
        <div class="footer-top">
            <div class="footer-brand">
                <h2>Rapid<span>Liv</span></h2>
                <p>Vos plats et produits préférés, livrés à votre porte en un instant.</p>
            </div>
            <div class="footer-download">
                <a href="index.php?page=connexion" class="btn-app">
                    <i class="fa-brands fa-apple"></i>
                    <div class="btn-text">
                        <span class="small-txt">Télécharger dans l'</span>
                        <span class="big-txt">App Store</span>
                    </div>
                </a>
                <a href="index.php?page=connexion" class="btn-app">
                    <i class="fa-brands fa-google-play"></i>
                    <div class="btn-text">
                        <span class="small-txt">Disponible sur</span>
                        <span class="big-txt">Google Play</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="footer-grid">
            
            <div class="footer-column">
                <h3>Pour les Clients</h3>
                <ul>
                    <li><a href="index.php?page=inscription">Créer un compte</a></li>
                    <li><a href="index.php?page=accueil#zones-livraison">Zones de livraison</a></li>
                    <li><a href="mailto:support@rapidliv.sn?subject=Abonnement%20Premium">Abonnement Premium</a></li>
                    <li><a href="mailto:support@rapidliv.sn?subject=Aide%20RapidLiv">Centre d'aide / FAQ</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Partenaires</h3>
                <ul>
                    <li><a href="mailto:partenaires@rapidliv.sn?subject=Restaurant%20partenaire">Devenir Restaurant Partenaire</a></li>
                    <li><a href="mailto:partenaires@rapidliv.sn?subject=Commerce%20partenaire">Devenir Commerce Partenaire</a></li>
                    <li><a href="index.php?page=connexion">Portail Flash Pro</a></li>
                    <li><a href="mailto:partenaires@rapidliv.sn?subject=FAQ%20Partenaires">FAQ Partenaires</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Livreurs</h3>
                <ul>
                    <li><a href="index.php?page=inscription&role=livreur">Devenir Livreur</a></li>
                    <li><a href="index.php?page=inscription&role=livreur#documents-livreur">Équipement requis</a></li>
                    <li><a href="index.php?page=connexion">Application Coursier</a></li>
                    <li><a href="mailto:support@rapidliv.sn?subject=FAQ%20Livreurs">FAQ Coursiers</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Entreprise</h3>
                <ul>
                    <li><a href="index.php?page=accueil">À propos de nous</a></li>
                    <li><a href="mailto:recrutement@rapidliv.sn?subject=Candidature">Carrières / Recrutement</a></li>
                    <li><a href="index.php?page=accueil#services">Notre Blog</a></li>
                    <li><a href="mailto:support@rapidliv.sn">Contact & Support</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            
            <div class="footer-socials">
                <a href="mailto:support@rapidliv.sn?subject=Facebook%20RapidLiv" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="mailto:support@rapidliv.sn?subject=Instagram%20RapidLiv" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="mailto:support@rapidliv.sn?subject=TikTok%20RapidLiv" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                <a href="mailto:partenaires@rapidliv.sn?subject=LinkedIn%20RapidLiv" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>

            <div class="footer-legal-links">
                <a href="mailto:legal@rapidliv.sn?subject=Mentions%20legales">Mentions Légales</a>
                <a href="mailto:legal@rapidliv.sn?subject=CGU%20Clients">CGU Clients</a>
                <a href="mailto:legal@rapidliv.sn?subject=Confidentialite">Politique de Confidentialité</a>
                <a href="mailto:legal@rapidliv.sn?subject=Cookies">Cookies</a>
            </div>

            <div class="copyright">
          &copy; <?= date('Y') ?> <?= APP_NAME ?> — Livraison rapide à Dakar · v<?= APP_VERSION ?>
            </div>

        </div>

    </div>
</footer>


<script src="/rapidliv-php/public/js/app.js?v=<?= urlencode(APP_VERSION) ?>&t=<?= filemtime(__DIR__ . '/../public/js/app.js') ?>"></script>
</body>
</html>
