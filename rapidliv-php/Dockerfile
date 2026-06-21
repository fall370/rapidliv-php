# 1. Utiliser l'image officielle PHP avec Apache sous Linux
FROM php:8.2-apache

# 2. Installer les extensions PDO MySQL indispensables pour ton site
RUN docker-php-ext-install pdo pdo_mysql

# 3. Activer le module de réécriture d'Apache (indispensable pour ton .htaccess)
RUN a2enmod rewrite

# 4. Copier tout le code du projet dans le dossier du serveur Apache
COPY . /var/www/html/

# 5. Configurer Apache pour écouter sur le port dynamique fourni par Render
# (Render injecte une variable $PORT, Apache doit s'y adapter)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# 6. Exposer le port par défaut (informatif)
EXPOSE 80
