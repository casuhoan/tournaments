<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    die('Accesso negato.');
}

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Metodo non consentito.');
}

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $users_file = __DIR__ . '/../data/users.json';
    $users = read_json($users_file);
    $user_id = $_SESSION['user_id'];
    $user_key = null;

    // Trova l'utente
    foreach ($users as $key => $user) {
        if ($user['id'] == $user_id) {
            $user_key = $key;
            break;
        }
    }

    if ($user_key === null) {
        $_SESSION['error'] = 'Utente non trovato.';
        header('Location: /forms/settings.php?page=profile');
        exit();
    }

    // === GESTIONE UPLOAD AVATAR ===
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];

        // Validazione tipo file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $_SESSION['error'] = 'Tipo di file non valido. Usa JPG, PNG, GIF o WebP.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }

        // Validazione dimensione (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'File troppo grande. Massimo 5MB.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }

        // Crea directory se non esiste
        $upload_dir = __DIR__ . '/../data/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Genera nome file univoco
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $new_filename;

        // Elimina vecchio avatar se esiste
        if (
            !empty($users[$user_key]['avatar']) &&
            $users[$user_key]['avatar'] !== 'data/avatars/default_avatar.png'
        ) {
            $old_avatar = __DIR__ . '/../' . $users[$user_key]['avatar'];
            if (file_exists($old_avatar)) {
                @unlink($old_avatar);
            }
        }

        // Carica il nuovo file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Salva il percorso relativo nel database
            $users[$user_key]['avatar'] = 'data/avatars/' . $new_filename;
        } else {
            $_SESSION['error'] = 'Errore durante il caricamento del file.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }
    }

    // === AGGIORNA USERNAME E EMAIL ===
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');

    if (empty($new_username) || empty($new_email)) {
        $_SESSION['error'] = 'Username e email sono obbligatori.';
        header('Location: /forms/settings.php?page=profile');
        exit();
    }

    // Verifica che username/email non siano già in uso
    foreach ($users as $key => $user) {
        if ($key !== $user_key) {
            if ($user['username'] === $new_username) {
                $_SESSION['error'] = 'Questo username è già in uso.';
                header('Location: /forms/settings.php?page=profile');
                exit();
            }
            if ($user['email'] === $new_email) {
                $_SESSION['error'] = 'Questa email è già in uso.';
                header('Location: /forms/settings.php?page=profile');
                exit();
            }
        }
    }

    $users[$user_key]['username'] = $new_username;
    $users[$user_key]['email'] = $new_email;
    $_SESSION['username'] = $new_username;

    // === AGGIORNA PASSWORD (opzionale) ===
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';

    if (!empty($new_password)) {
        // Se vuole cambiare password, tutti i campi devono essere compilati
        if (empty($current_password) || empty($confirm_password)) {
            $_SESSION['error'] = 'Per cambiare la password, compila tutti i campi.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }

        // Verifica password attuale
        if (!password_verify($current_password, $users[$user_key]['password'])) {
            $_SESSION['error'] = 'Password attuale non corretta.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }

        // Verifica che le nuove password corrispondano
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'Le nuove password non corrispondono.';
            header('Location: /forms/settings.php?page=profile');
            exit();
        }

        // Aggiorna password
        $users[$user_key]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // === SALVA MODIFICHE ===
    write_json($users_file, $users);

    $_SESSION['feedback'] = 'Profilo aggiornato con successo!';
    header('Location: /forms/settings.php?page=profile');
    exit();
}

// Azione non valida
$_SESSION['error'] = 'Azione non valida.';
header('Location: /forms/settings.php?page=profile');
exit();
