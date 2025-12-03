# 🔍 Checklist Pre-Push

## ✅ Completato

### Struttura Directory
- [x] Cartelle create correttamente
- [x] File spostati nelle posizioni giuste
- [x] Cartelle vuote rimosse

### Percorsi PHP
- [x] `require_once` aggiornati in tutti i file
- [x] Percorsi `data/` aggiornati con `__DIR__`
- [x] Avatar path cambiato in `data/avatars/`

### Assets
- [x] CSS linkati con `../assets/css/`
- [x] JS linkati con `../assets/js/`
- [x] Immagini spostate in `assets/images/`

### Redirect (header Location)
- [x] API redirect corretti per nuova struttura
- [x] Login/Register redirect corretti
- [x] Home redirect corretti

### Link Interni (href)
- [x] Link a `all_tournaments.php` → `../views/all_tournaments.php`
- [x] Link a `view_profile.php` → `../views/view_profile.php`
- [x] Link a `view_tournament.php` → `../views/view_tournament.php`
- [x] Link a `settings.php` → `../forms/settings.php`
- [x] Link a `admin_panel.php` → `../admin/index.php`
- [x] Link a `create_tournament.php` → `../forms/create_tournament.php`

### Docker
- [x] Dockerfile aggiornato (document root = public/)
- [x] docker-compose.yml semplificato
- [x] Cartella `data/avatars/` creata

### Sicurezza
- [x] Password hashing implementato
- [x] CSRF protection attiva
- [x] Rate limiting configurato
- [x] Input sanitization attiva

## ⚠️ Da Verificare Dopo Deploy

### Test Funzionali
- [ ] Login funziona
- [ ] Registrazione funziona
- [ ] Home page carica
- [ ] Tornei visualizzabili
- [ ] Link navigazione funzionano
- [ ] Admin panel accessibile
- [ ] Upload avatar (in data/avatars/)

### Test Sicurezza
- [ ] Password hashate salvate correttamente
- [ ] CSRF token validato
- [ ] Rate limiting attivo su login

## 📝 Note

**Avatar Path**: Gli avatar ora vanno in `data/avatars/` invece di `uploads/`

**Document Root**: Con Docker, il document root è `public/`, quindi:
- `http://localhost:8000/` → `public/index.php`
- `http://localhost:8000/login.php` → `public/login.php`

**Percorsi Relativi**: Tutti i file in sottocartelle usano `../` per risalire:
- Da `admin/` → `../public/`, `../assets/`, `../data/`
- Da `views/` → `../public/`, `../assets/`, `../data/`
- Da `forms/` → `../public/`, `../assets/`, `../data/`
- Da `api/` → `../public/`, `../assets/`, `../data/`

## 🚀 Pronto per il Push

Tutti i percorsi sono stati aggiornati e verificati. Il codice dovrebbe funzionare correttamente dopo il deploy su Portainer.
