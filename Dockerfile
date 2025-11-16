# Usa un'immagine ufficiale di PHP con il server Apache
FROM php:8.2-apache

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia i file dell'applicazione dalla directory corrente alla directory di lavoro nel container
# Escludiamo la cartella .git e altri file non necessari
COPY . .

# Apache ha bisogno dei permessi per scrivere nella cartella 'data'
# Cambiamo il proprietario della cartella 'data' all'utente con cui gira Apache (www-data)
# e ci assicuriamo che abbia i permessi di scrittura.
RUN chown -R www-data:www-data data && chmod -R 775 data

# Il server Apache verrà avviato automaticamente quando il container parte
