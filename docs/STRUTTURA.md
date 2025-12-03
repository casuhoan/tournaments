# 📁 Nuova Struttura Directory - Tournament Manager

## Struttura Organizzata

```
tournaments/
├── public/                    ✅ Pagine pubbliche
│   ├── index.php             (landing page)
│   ├── login.php             (autenticazione)
│   ├── register.php          (registrazione)
│   ├── home.php              (dashboard)
│   └── tournament.php        (pagina torneo)
│
├── admin/                     ✅ Pannello amministrazione
│   ├── index.php             (dashboard admin)
│   ├── users.php             (gestione utenti)
│   ├── tournaments.php       (gestione tornei)
│   └── decklists.php         (gestione liste)
│
├── views/                     ✅ Pagine visualizzazione
│   ├── all_tournaments.php   (lista tornei)
│   ├── view_tournament.php   (dettaglio torneo)
│   ├── view_profile.php      (profilo utente)
│   └── view_decklist.php     (dettaglio lista)
│
├── forms/                     ✅ Form e modifica
│   ├── create_tournament.php
│   ├── create_user.php
│   ├── edit_tournament.php
│   ├── edit_user.php
│   ├── edit_decklist.php
│   ├── profile.php
│   └── settings.php
│
├── api/                       ✅ Endpoint API
│   ├── admin_actions.php
│   ├── tournament_actions.php
│   └── user_actions.php
│
├── includes/                  ✅ File PHP condivisi
│   ├── security.php
│   └── helpers.php
│
├── assets/                    ✅ File statici
│   ├── css/
│   │   ├── premium_design.css
│   │   ├── modern_style.css
│   │   ├── components.css
│   │   └── ...
│   ├── js/
│   │   ├── theme-toggle.js
│   │   └── main.js
│   └── images/
│       ├── icona.png
│       └── default_avatar.png
│
├── data/                      🔒 Dati persistenti (non sincronizzato)
│   ├── users.json
│   ├── tournaments.json
│   └── avatars/              ✨ Avatar utenti
│       └── default_avatar.png
│
├── docs/                      📚 Documentazione
│   ├── AVVIO-LOCALE.md
│   ├── PROJECT_STATE.md
│   ├── istruzioni.txt
│   └── demo.html
│
├── scripts/                   🛠️ Script utility
│   ├── avvia-locale.ps1
│   ├── ferma-locale.ps1
│   └── serverlocale.bat
│
├── Dockerfile                 🐳 Configurazione Docker
└── docker-compose.yml
```

## Modifiche Principali

### ✅ Percorsi Aggiornati

**PHP Includes:**
- `require_once 'helpers.php'` → `require_once __DIR__ . '/../includes/helpers.php'`

**Assets:**
- `css/` → `../assets/css/`
- `js/` → `../assets/js/`
- `img/` → `../assets/images/`

**Data:**
- `data/users.json` → `__DIR__ . '/../data/users.json'`
- Avatar: `data/avatars/` (invece di `uploads/`)

### ✅ Docker Aggiornato

- Document root: `/var/www/html/public`
- Volume unico per `data/` (include avatars)
- Cartella `data/avatars/` creata automaticamente

### ✅ Vantaggi

- 📂 Organizzazione professionale
- 🔍 Facile navigazione
- 🔒 Separazione sicurezza (public/ come root)
- 📦 Migliore per version control
- 🚀 Più facile da mantenere

## Note

- La cartella `data/` NON viene sincronizzata (come richiesto)
- La cartella `uploads/` è stata rimossa (avatar in `data/avatars/`)
- Tutti i percorsi nei file PHP sono stati aggiornati
- Docker configurato per servire da `public/`
