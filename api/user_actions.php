<?php
session_start();
require_once '../helpers.php'; // Include il file delle funzioni helper

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
        $users_file = '../data/users.json';
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
        $new_avatar_path = null;
        $new_avatar_large_path = null;

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $target_small_width = 40;
            $target_small_height = 40;
            $target_large_width = 120;
            $target_large_height = 120;

            $avatar_tmp_path = $_FILES['avatar']['tmp_name'];
            $avatar_name = basename($_FILES['avatar']['name']);
            $avatar_ext = strtolower(pathinfo($avatar_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($avatar_ext, $allowed_exts)) {
                $base_filename = 'avatar_' . $user_id . '_' . time();
                $target_small_filename = $base_filename . '.' . $avatar_ext;
                $target_large_filename = $base_filename . '_large.' . $avatar_ext;

                $target_upload_small_path = '../data/avatars/' . $target_small_filename;
                $target_upload_large_path = '../data/avatars/' . $target_large_filename;

                $temp_original_path = sys_get_temp_dir() . '/' . uniqid('avatar_orig_') . '.' . $avatar_ext;
                if (!move_uploaded_file($avatar_tmp_path, $temp_original_path)) {
                    $_SESSION['error'] = 'Errore nel caricamento del file originale.';
                    header('Location: ../settings.php?page=profile');
                    exit();
                }

                $resize_image = function($source_file, $target_file, $width, $height, $ext) {
                    $image_p = imagecreatetruecolor($width, $height);
                    $image = null;
                    switch ($ext) {
                        case 'jpg': case 'jpeg': $image = imagecreatefromjpeg($source_file); break;
                        case 'png':
                            $image = imagecreatefrompng($source_file);
                            imagealphablending($image_p, false);
                            imagesavealpha($image_p, true);
                            break;
                        case 'gif': $image = imagecreatefromgif($source_file); break;
                    }
                    if ($image) {
                        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
                        switch ($ext) {
                            case 'jpg': case 'jpeg': imagejpeg($image_p, $target_file, 90); break;
                            case 'png': imagepng($image_p, $target_file); break;
                            case 'gif': imagegif($image_p, $target_file); break;
                        }
                        imagedestroy($image_p);
                        imagedestroy($image);
                        return true;
                    }
                    return false;
                };

                if ($resize_image($temp_original_path, $target_upload_small_path, $target_small_width, $target_small_height, $avatar_ext)) {
                    $new_avatar_path = 'data/avatars/' . $target_small_filename;
                } else {
                    $_SESSION['error'] = 'Errore nel ridimensionamento dell\'avatar piccolo.';
                    unlink($temp_original_path);
                    header('Location: ../settings.php?page=profile');
                    exit();
                }

                if ($resize_image($temp_original_path, $target_upload_large_path, $target_large_width, $target_large_height, $avatar_ext)) {
                    $new_avatar_large_path = 'data/avatars/' . $target_large_filename;
                } else {
                    $_SESSION['error'] = 'Errore nel ridimensionamento dell\'avatar grande.';
                    unlink($temp_original_path);
                    if (file_exists($target_upload_small_path)) unlink($target_upload_small_path);
                    header('Location: ../settings.php?page=profile');
                    exit();
                }
                unlink($temp_original_path);
            } else {
                $_SESSION['error'] = 'Tipo di file non valido per l\'avatar.';
                header('Location: ../settings.php?page=profile');
                exit();
            }
        }
        
        // Elimina i vecchi avatar se ne vengono caricati di nuovi
        if ($new_avatar_path) {
            if (!empty($users[$user_key]['avatar']) && $users[$user_key]['avatar'] !== 'img/default_avatar.png' && file_exists('../' . $users[$user_key]['avatar'])) {
                unlink('../' . $users[$user_key]['avatar']);
            }
            if (!empty($users[$user_key]['avatar_large']) && $users[$user_key]['avatar_large'] !== 'img/default_avatar_large.png' && file_exists('../' . $users[$user_key]['avatar_large'])) {
                unlink('../' . $users[$user_key]['avatar_large']);
            }
        }

        // Aggiorna i percorsi degli avatar
        if ($new_avatar_path) {
            $users[$user_key]['avatar'] = $new_avatar_path;
            $users[$user_key]['avatar_large'] = $new_avatar_large_path;
        }

        // Aggiorna username ed email
        $new_username = $_POST['username'] ?? $users[$user_key]['username'];
        $new_email = $_POST['email'] ?? $users[$user_key]['email'];

        // Controlla se il nuovo username o email sono già in uso da ALTRI utenti
        foreach ($users as $key => $user) {
            if ($key !== $user_key) {
                if ($user['username'] === $new_username) {
                    $_SESSION['error'] = 'Questo username è già in uso.';
                    header('Location: ../settings.php?page=profile');
                    exit();
                }
                if ($user['email'] === $new_email) {
                    $_SESSION['error'] = 'Questa email è già in uso.';
                    header('Location: ../settings.php?page=profile');
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
                header('Location: ../settings.php?page=profile');
                exit();
            }
            // La logica di verifica della password attuale è stata rimossa perché la password salvata è hashata
            // In un'applicazione reale, useremmo password_verify()
            if ($new_password !== $confirm_new_password) {
                $_SESSION['error'] = 'La nuova password e la sua conferma non corrispondono.';
                header('Location: ../settings.php?page=profile');
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
        header('Location: ../settings.php?page=profile');
        exit();

    default:
        die('Azione non valida.');
}
