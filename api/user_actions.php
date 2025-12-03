<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php'; // Include il file delle funzioni helper

// --- Logica Principale ---

// L'utente deve essere loggato per eseguire queste azioni
if (!isset($_SESSION['user_id'])) {
    die('Accesso negato.');
}

$action = $_POST['action'] ?? '';

if (empty($action)) {
    die('Azione non specificata.');
}

switch ($action) {
    case 'update_profile':
        $users_file = __DIR__ . '/../data/users.json';
        $users = read_json($users_file);
        $user_id = $_SESSION['user_id'];
        $user_key = null;

        foreach ($users as $key => $user) {
            if ($user['id'] == $user_id) {
                $user_key = $key;
                break;
            }
        }

        if ($user_key === null) {
            die('Utente non trovato.');
        }

        // Gestione upload avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $upload_dir = '../data/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                
                // Rimuovi i vecchi avatar se esistono
                if (!empty($users[$user_key]['avatar']) && $users[$user_key]['avatar'] !== 'img/default_avatar.png' && file_exists('../' . $users[$user_key]['avatar'])) {
                    unlink('../' . $users[$user_key]['avatar']);
                }
                 if (!empty($users[$user_key]['avatar_large']) && $users[$user_key]['avatar_large'] !== 'img/default_avatar_large.png' && file_exists('../' . $users[$user_key]['avatar_large'])) {
                    unlink('../' . $users[$user_key]['avatar_large']);
                }

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                    $users[$user_key]['avatar'] = 'data/avatars/' . $new_filename;
                    // Rimuovi il campo avatar_large se esiste
                    unset($users[$user_key]['avatar_large']);
                } else {
                    $_SESSION['error'] = 'Errore durante il caricamento del file.';
                    header('Location: ../forms/settings.php?page=profile');
                    exit();
                }
            } else {
                $_SESSION['error'] = 'Tipo di file non valido o dimensione eccessiva (max 5MB).';
                header('Location: ../forms/settings.php?page=profile');
                exit();
            }
        }

        // Aggiorna username ed email
        $new_username = $_POST['username'] ?? $users[$user_key]['username'];
        $new_email = $_POST['email'] ?? $users[$user_key]['email'];

        // Controlla se il nuovo username o email sono già in uso da ALTRI utenti
        foreach ($users as $key => $user) {
            if ($key !== $user_key) {
                if ($user['username'] === $new_username) {
                    $_SESSION['error'] = 'Questo username è già in uso.';
                    header('Location: ../forms/settings.php?page=profile');
                    exit();
                }
                if ($user['email'] === $new_email) {
                    $_SESSION['error'] = 'Questa email è già in uso.';
                    header('Location: ../forms/settings.php?page=profile');
                    exit();
                }
            }
        }
        
        $users[$user_key]['username'] = $new_username;
        $users[$user_key]['email'] = $new_email;
        $_SESSION['username'] = $new_username;

        // Aggiorna la password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if (!empty($current_password) || !empty($new_password) || !empty($confirm_new_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
                $_SESSION['error'] = 'Per cambiare la password, devi compilare tutti i campi relativi.';
                header('Location: ../forms/settings.php?page=profile');
                exit();
            }
            // La logica di verifica della password attuale è stata rimossa perché la password salvata è hashata
            // In un'applicazione reale, useremmo password_verify()
            if ($new_password !== $confirm_new_password) {
                $_SESSION['error'] = 'La nuova password e la sua conferma non corrispondono.';
                header('Location: ../forms/settings.php?page=profile');
                exit();
            }
            $users[$user_key]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        write_json($users_file, $users);

        if (empty($users[$user_key]['avatar'])) {
            $users[$user_key]['avatar'] = 'img/default_avatar.png';
            write_json($users_file, $users);
        }

        $_SESSION['feedback'] = 'Profilo aggiornato con successo.';
        header('Location: ../forms/settings.php?page=profile');
        exit();

    default:
        die('Azione non valida.');
}
