# Usa un'immagine ufficiale di PHP con il server Apache
FROM php:8.2-apache

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia i file dell'applicazione dalla directory corrente alla directory di lavoro nel container
COPY . .

# Apache ha bisogno dei permessi per scrivere nella cartella 'data'
# Cambiamo il proprietario della cartella 'data' all'utente con cui gira Apache (www-data)
# e ci assicuriamo che abbia i permessi di scrittura.
RUN chown -R www-data:www-data data && chmod -R 775 data

# Crea la cartella avatars se non esiste
RUN mkdir -p data/avatars && chown -R www-data:www-data data/avatars && chmod -R 775 data/avatars

# Configura Apache per servire da public/ come document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Il server Apache verrà avviato automaticamente quando il container parte
