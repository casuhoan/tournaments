# Avvio Rapido del Sito dei Tornei

Questo documento spiega come avviare il sito in locale sul tuo PC.

## Requisiti

- **Docker Desktop** installato e in esecuzione
  - Scarica da: https://www.docker.com/products/docker-desktop/

## Come Avviare il Sito

### Metodo 1: Script Automatico (Consigliato)

1. **Doppio click** su `avvia-locale.ps1`
2. Se Windows chiede conferma, clicca **"Esegui comunque"**
3. Attendi che il container si avvii (la prima volta ci vogliono 2-3 minuti)
4. Il browser si aprirà automaticamente su `http://localhost:8000`

### Metodo 2: Manuale

Apri PowerShell nella cartella del progetto e esegui:

```powershell
docker-compose up -d --build
```

Poi apri il browser su: http://localhost:8000

## Come Fermare il Sito

### Metodo 1: Script Automatico

1. **Doppio click** su `ferma-locale.ps1`

### Metodo 2: Manuale

```powershell
docker-compose down
```

## Risoluzione Problemi

### "Docker non è in esecuzione"
- Avvia Docker Desktop dal menu Start
- Attendi che l'icona Docker nella system tray diventi verde
- Riprova

### "Porta 8000 già in uso"
- Ferma altri servizi sulla porta 8000
- Oppure modifica la porta in `docker-compose.yml` (riga 11)

### "Errore durante la build"
- Assicurati di avere connessione internet
- Prova a eseguire: `docker system prune -a` (pulisce la cache)
- Riprova

## Accesso Admin

Dopo il primo avvio, puoi accedere come admin con:
- **Username**: admin
- **Email**: casuhoan@gmail.com  
- **Password**: grandius

**IMPORTANTE**: Cambia la password admin dopo il primo accesso!

## Note

- I dati sono salvati in volumi Docker persistenti
- Le modifiche al codice richiedono un rebuild: `docker-compose up -d --build`
- I log sono visibili con: `docker-compose logs -f`
