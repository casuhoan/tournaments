# 🔧 Fix CSS 404 - Istruzioni

## Problema
I file CSS/JS non venivano caricati perché con `public/` come document root, i percorsi `../assets/` andavano fuori dalla root del server.

## Soluzione Applicata

### 1. Percorsi Aggiornati
Cambiati tutti i percorsi da relativi ad assoluti:
- ❌ `href="../assets/css/style.css"`
- ✅ `href="/assets/css/style.css"`

### 2. Dockerfile Aggiornato
Aggiunto alias Apache per servire `/assets` dalla directory principale:
```apache
Alias /assets /var/www/html/assets
```

## 🚀 Come Applicare il Fix

### Opzione 1: Docker (Consigliato)
```powershell
# Ferma il container
docker-compose down

# Ricostruisci con le nuove modifiche
docker-compose up -d --build

# Apri il browser
start http://localhost:8000
```

### Opzione 2: Riavvio Rapido
Se il container è già in esecuzione:
```powershell
docker-compose restart
```

## ✅ Verifica
Dopo il riavvio, controlla che:
1. La pagina carica senza errori 404
2. Il CSS viene applicato correttamente
3. Il dark mode toggle funziona

## 📝 File Modificati
- Tutti i file `.php` in `public/`, `admin/`, `views/`, `forms/`
- `Dockerfile` (aggiunto alias Apache)

## 🔍 Debug
Se continui a vedere 404:
1. Controlla i log: `docker-compose logs -f`
2. Verifica che `/assets` sia accessibile: `http://localhost:8000/assets/css/style.css`
3. Ricostruisci completamente: `docker-compose down && docker-compose up -d --build`
