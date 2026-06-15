# Utilise l'image officielle PHP avec Apache
FROM php:8.2-apache

# Active le module de réécriture d'URL d'Apache (très utile pour le routage PHP)
RUN a2enmod rewrite

# Copie tous les fichiers de votre projet dans le dossier du serveur web
COPY . /var/www/html/

# Donne les bonnes permissions aux fichiers
RUN chown -R www-data:www-data /var/www/html

# Expose le port 80 pour le trafic web
EXPOSE 80
