# ✅ Fix Upload Avatar

## Problemi Trovati in `api/user_actions.php`

### 1. Upload Directory Relativo
```php
// PRIMA (sbagliato)
$upload_dir = '../data/avatars/';

// DOPO (corretto)
$upload_dir = __DIR__ . '/../data/avatars/';
```

### 2. Move Uploaded File
```php
// PRIMA (sbagliato)
move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)

// DOPO (corretto)  
move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $new_filename)
```

### 3. Redirect Relativi
```php
// PRIMA (sbagliato)
header('Location: ../forms/settings.php?page=profile');

// DOPO (corretto)
header('Location: /forms/settings.php?page=profile');
```

### 4. File Exists per Vecchi Avatar
```php
// PRIMA (sbagliato)
file_exists('../' . $users[$user_key]['avatar'])

// DOPO (corretto)
file_exists(__DIR__ . '/../' . $users[$user_key]['avatar'])
```

## Percorso Avatar Salvato

L'avatar viene salvato come:
```php
$users[$user_key]['avatar'] = 'data/avatars/' . $new_filename;
```

Questo è un percorso **relativo dalla root del progetto**, che poi viene convertito in URL assoluto quando visualizzato:
```php
$avatar_path = '/' . $current_user['avatar'];  // '/data/avatars/avatar_123.jpg'
```

## Test

Dopo il push:
1. Vai su Impostazioni → Profilo
2. Carica una nuova immagine
3. Clicca "Salva Modifiche"
4. L'avatar dovrebbe aggiornarsi immediatamente

**Tutto fixato! 🎉**
