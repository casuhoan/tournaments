# 🧪 Test Upload - Istruzioni

## Problema

Il form di upload avatar non sembra funzionare. Per diagnosticare, ho creato una pagina di test.

## Come Testare

1. **Fai il push** di `test_upload.php`
2. **Vai su** `https://halloffame.grandius.it/test_upload.php`
3. **Carica un'immagine** e clicca "Invia Test"
4. **Osserva il risultato**

## Cosa Aspettarsi

### ✅ Se Funziona
Dovresti vedere:
```
✅ Form Ricevuto!
POST data: Array ( [username] => test_user )
FILES data: Array ( [avatar] => Array ( [name] => ... [error] => 0 ) )
✅ File ricevuto correttamente!
```

### ❌ Se NON Funziona
Potresti vedere:
- Nessun output (form non inviato)
- Error code diverso da 0
- FILES array vuoto

## Possibili Problemi

### Problema 1: Form Non Si Invia
**Causa**: JavaScript o HTML malformato
**Soluzione**: Controllare console browser per errori

### Problema 2: File Non Arriva (error code 1-8)
**Codici errore**:
- `1` = File troppo grande (php.ini upload_max_filesize)
- `2` = File troppo grande (MAX_FILE_SIZE nel form)
- `3` = Upload parziale
- `4` = Nessun file caricato
- `6` = Directory temporanea mancante
- `7` = Scrittura su disco fallita
- `8` = Estensione PHP ha bloccato l'upload

### Problema 3: Configurazione PHP
Controlla `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
file_uploads = On
```

## Prossimi Passi

Dopo il test, dimmi cosa vedi e potrò identificare esattamente il problema!

**IMPORTANTE**: Questo è solo un file di test. Una volta risolto, lo elimineremo.
