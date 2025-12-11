# 🔍 Debug Upload Avatar - Istruzioni

## Modifiche Applicate

Ho aggiunto logging temporaneo in `api/user_actions.php` per capire cosa succede quando carichi l'avatar.

## Come Testare

1. **Fai il push** delle modifiche
2. **Vai su** `https://halloffame.grandius.it/forms/settings.php?page=profile`
3. **Carica un'immagine** e clicca "Salva Modifiche"
4. **Controlla i log Docker**:
   ```bash
   docker logs <container_name> --tail 100 | grep DEBUG
   ```

## Cosa Cercare nei Log

Dovresti vedere una di queste sequenze:

### ✅ Caso 1: Upload Funziona
```
DEBUG: Processing update_profile action
DEBUG: FILES array: Array ( [avatar] => Array ( [name] => ... [error] => 0 ) )
DEBUG: Avatar file received, processing upload
DEBUG: File uploaded successfully to: /var/www/html/data/avatars/avatar_123_...jpg
```

### ❌ Caso 2: File Non Arriva
```
DEBUG: Processing update_profile action
DEBUG: FILES array: Array ( [avatar] => Array ( [error] => 4 ) )
```
→ Significa che il form non sta inviando il file

### ❌ Caso 3: Upload Fallisce
```
DEBUG: Processing update_profile action
DEBUG: FILES array: Array ( [avatar] => Array ( [name] => ... [error] => 0 ) )
DEBUG: Avatar file received, processing upload
DEBUG: File upload FAILED. Target: /var/www/html/data/avatars/avatar_...
```
→ Significa che c'è un problema di permessi o percorso

## Prossimi Passi

Dopo aver visto i log, saprò esattamente dove sta il problema e potrò fixarlo!

**IMPORTANTE**: Questi log sono temporanei. Una volta risolto il problema, li rimuoverò.
