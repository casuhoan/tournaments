# 🌐 Fix Domini e Percorsi

## Problema
Con il dominio `https://halloffame.grandius.it/` e `public/` come document root, i percorsi relativi `../views/` non funzionano perché escono dalla root del server.

## Soluzione

### 1. Dockerfile Aggiornato
Aggiunto alias Apache per tutte le directory:
```apache
Alias /views /var/www/html/views
Alias /forms /var/www/html/forms
Alias /admin /var/www/html/admin
Alias /api /var/www/html/api
Alias /assets /var/www/html/assets
Alias /data /var/www/html/data
```

### 2. Link Aggiornati
Tutti i link ora usano percorsi assoluti dal dominio:
- ❌ `href="../views/all_tournaments.php"`
- ✅ `href="/views/all_tournaments.php"`

### 3. Redirect Aggiornati
Tutti i `header('Location:` ora usano percorsi assoluti:
- ❌ `header('Location: ../public/home.php')`
- ✅ `header('Location: /home.php')`

## Struttura URL

Con il dominio `https://halloffame.grandius.it/`:

```
https://halloffame.grandius.it/              → public/index.php
https://halloffame.grandius.it/login.php     → public/login.php
https://halloffame.grandius.it/home.php      → public/home.php
https://halloffame.grandius.it/views/...     → views/...
https://halloffame.grandius.it/forms/...     → forms/...
https://halloffame.grandius.it/admin/...     → admin/...
https://halloffame.grandius.it/api/...       → api/...
https://halloffame.grandius.it/assets/...    → assets/...
```

## Deploy

Dopo il push su GitHub, Portainer ricostruirà il container con il nuovo Dockerfile e tutti i link funzioneranno correttamente! 🚀
