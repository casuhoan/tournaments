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

# Copia la configurazione Apache personalizzata
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Abilita mod_rewrite per Apache
RUN a2enmod rewrite

# Il server Apache verrà avviato automaticamente quando il container parte
