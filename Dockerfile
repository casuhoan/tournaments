# Usa un'immagine ufficiale di PHP con il server Apache
FROM php:8.2-apache

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia i file dell'applicazione dalla directory corrente alla directory di lavoro nel container
COPY . .

# Apache ha bisogno dei permessi per scrivere nella cartella 'data'
RUN chown -R www-data:www-data data && chmod -R 775 data

# Crea la cartella avatars se non esiste
RUN mkdir -p data/avatars && chown -R www-data:www-data data/avatars && chmod -R 775 data/avatars

# Configura Apache per servire da public/ come document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Configura Apache per servire le altre directory come alias
RUN echo '<Directory /var/www/html>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /assets /var/www/html/assets' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /views /var/www/html/views' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /forms /var/www/html/forms' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /admin /var/www/html/admin' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /api /var/www/html/api' >> /etc/apache2/sites-available/000-default.conf && \
    echo 'Alias /data /var/www/html/data' >> /etc/apache2/sites-available/000-default.conf

# Abilita mod_rewrite per Apache
RUN a2enmod rewrite

# Il server Apache verrà avviato automaticamente quando il container parte
