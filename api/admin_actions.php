<?php
ob_start(); // Start output buffering
session_start();
require_once '../helpers.php'; // Include il file delle funzioni helper

// --- Logica Principale ---

// Solo gli admin possono eseguire queste azioni
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

// Determina l'azione e l'ID in base al metodo della richiesta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';
}

if (empty($action)) {
    die('Azione non specificata.');
}


switch ($action) {
    case 'delete_tournament':
        if (empty($id)) die('ID non specificato.');
        $tournaments_file = '../data/tournaments.json';
        $tournaments = read_json($tournaments_file);
        $tournaments_after_delete = array_values(array_filter($tournaments, fn($t) => $t['id'] != $id));
        write_json($tournaments_file, $tournaments_after_delete);
        $_SESSION['feedback'] = 'Torneo ID ' . htmlspecialchars($id) . ' eliminato con successo.';
        header('Location: ../admin_panel.php?page=tournaments');
        exit();

    case 'delete_user':
        if (empty($id)) die('ID non specificato.');
        if ($id == $_SESSION['user_id']) die('Non puoi eliminare te stesso.');
        $users_file = '../data/users.json';
        $users = read_json($users_file);
        $users_after_delete = array_values(array_filter($users, fn($u) => $u['id'] != $id));
        write_json($users_file, $users_after_delete);
        $_SESSION['feedback'] = 'Utente ID ' . htmlspecialchars($id) . ' eliminato con successo.';
        header('Location: ../admin_panel.php?page=users');
        exit();

    case 'update_decklist':
        $tournament_id_post = $_POST['tournament_id'] ?? null;
        $user_id_post = $_POST['user_id'] ?? null;
        $decklist_name = $_POST['decklist_name'] ?? '';
        $decklist_format = $_POST['decklist_format'] ?? '';

        if (!$tournament_id_post || !$user_id_post || empty($decklist_name)) {
            die('Dati mancanti per aggiornare la lista.');
        }

        $tournaments_file = '../data/tournaments.json';
        $tournaments = read_json($tournaments_file);
        $updated = false;

        foreach ($tournaments as &$tournament) {
            if ($tournament['id'] == $tournament_id_post) {
                foreach ($tournament['participants'] as &$participant) {
                    if ($participant['userId'] == $user_id_post) {
                        $participant['decklist_name'] = $decklist_name;
                        $participant['decklist_format'] = $decklist_format;
                        $updated = true;
                        break 2;
                    }
                }
            }
        }

        if ($updated) {
            write_json($tournaments_file, $tournaments);
            $_SESSION['feedback'] = 'Lista aggiornata con successo.';
        } else {
            $_SESSION['error'] = 'Impossibile trovare la lista da aggiornare.';
        }
        
        header('Location: ../admin_panel.php?page=decklists');
        exit();

    case 'update_tournament':
        $tournament_id_post = $_POST['tournament_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $date = $_POST['date'] ?? '';

        if (!$tournament_id_post || empty($name) || empty($date)) {
            die('Dati mancanti per aggiornare il torneo.');
        }

        $tournaments_file = '../data/tournaments.json';
        $tournaments = read_json($tournaments_file);
        $updated = false;

        foreach ($tournaments as &$tournament) {
            if ($tournament['id'] == $tournament_id_post) {
                $tournament['name'] = $name;
                $tournament['date'] = $date;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            write_json($tournaments_file, $tournaments);
            $_SESSION['feedback'] = 'Torneo aggiornato con successo.';
        } else {
            $_SESSION['error'] = 'Impossibile trovare il torneo da aggiornare.';
        }
        
        header('Location: ../admin_panel.php?page=tournaments');
        exit();

    case 'update_user':
        $user_id_post = $_POST['user_id'] ?? null;
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!$user_id_post || empty($username) || empty($email) || empty($role)) {
            die('Dati mancanti per aggiornare l\'utente.');
        }

        $users_file = '../data/users.json';
        $users = read_json($users_file);
        $updated = false;
        $new_avatar_path = null;
        $new_avatar_large_path = null;

        $target_small_width = 40;
        $target_small_height = 40;
        $target_large_width = 120;
        $target_large_height = 120;

        // Gestione dell'upload dell'avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatar_tmp_path = $_FILES['avatar']['tmp_name'];
            $avatar_name = basename($_FILES['avatar']['name']);
            $avatar_ext = strtolower(pathinfo($avatar_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($avatar_ext, $allowed_exts)) {
                // Genera nomi file unici per le due dimensioni
                $base_filename = 'avatar_' . $user_id_post . '_' . time();
                $target_small_filename = $base_filename . '.' . $avatar_ext;
                $target_large_filename = $base_filename . '_large.' . $avatar_ext;

                $target_upload_small_path = '../data/avatars/' . $target_small_filename;
                $target_upload_large_path = '../data/avatars/' . $target_large_filename;

                // Move the uploaded original file to a temporary location to process
                $temp_original_path = sys_get_temp_dir() . '/' . uniqid('avatar_orig_') . '.' . $avatar_ext;
                if (!move_uploaded_file($avatar_tmp_path, $temp_original_path)) {
                    $_SESSION['error'] = 'Errore nel caricamento del file originale.';
                    header('Location: ../edit_user.php?id=' . $user_id_post);
                    exit();
                }

                list($original_width, $original_height) = getimagesize($temp_original_path);

                // Funzione per il ridimensionamento
                $resize_image = function($source_file, $target_file, $width, $height, $ext) {
                    $image_p = imagecreatetruecolor($width, $height);
                    $image = null;
                    switch ($ext) {
                        case 'jpg':
                        case 'jpeg':
                            $image = imagecreatefromjpeg($source_file);
                            break;
                        case 'png':
                            $image = imagecreatefrompng($source_file);
                            imagealphablending($image_p, false);
                            imagesavealpha($image_p, true);
                            break;
                        case 'gif':
                            $image = imagecreatefromgif($source_file);
                            break;
                    }

                    if ($image) {
                        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
                        switch ($ext) {
                            case 'jpg':
                            case 'jpeg':
                                imagejpeg($image_p, $target_file, 90);
                                break;
                            case 'png':
                                imagepng($image_p, $target_file);
                                break;
                            case 'gif':
                                imagegif($image_p, $target_file);
                                break;
                        }
                        imagedestroy($image_p);
                        imagedestroy($image);
                        return true;
                    }
                    return false;
                };

                // Ridimensiona e salva l'avatar piccolo
                if ($resize_image($temp_original_path, $target_upload_small_path, $target_small_width, $target_small_height, $avatar_ext)) {
                    $new_avatar_path = 'data/avatars/' . $target_small_filename;
                } else {
                    $_SESSION['error'] = 'Errore nel ridimensionamento dell\'avatar piccolo.';
                    unlink($temp_original_path);
                    header('Location: ../edit_user.php?id=' . $user_id_post);
                    exit();
                }

                // Ridimensiona e salva l'avatar grande
                if ($resize_image($temp_original_path, $target_upload_large_path, $target_large_width, $target_large_height, $avatar_ext)) {
                    $new_avatar_large_path = 'data/avatars/' . $target_large_filename;
                } else {
                    $_SESSION['error'] = 'Errore nel ridimensionamento dell\'avatar grande.';
                    unlink($temp_original_path);
                    // Elimina anche il piccolo se già creato
                    if (file_exists($target_upload_small_path)) unlink($target_upload_small_path);
                    header('Location: ../edit_user.php?id=' . $user_id_post);
                    exit();
                }
                unlink($temp_original_path); // Elimina il file temporaneo originale
                
            } else {
                $_SESSION['error'] = 'Formato file non supportato per l\'avatar.';
                header('Location: ../edit_user.php?id=' . $user_id_post);
                exit();
            }
        }

        foreach ($users as &$user) {
            if ($user['id'] == $user_id_post) {
                // Elimina i vecchi avatar se ne vengono caricati di nuovi
                if ($new_avatar_path) {
                    if (!empty($user['avatar']) && $user['avatar'] !== 'img/default_avatar.png' && file_exists('../' . $user['avatar'])) {
                        unlink('../' . $user['avatar']);
                    }
                    if (!empty($user['avatar_large']) && $user['avatar_large'] !== 'img/default_avatar_large.png' && file_exists('../' . $user['avatar_large'])) {
                        unlink('../' . $user['avatar_large']);
                    }
                }

                // Aggiorna i dati
                $user['username'] = $username;
                $user['email'] = $email;
                $user['role'] = $role;
                if ($new_avatar_path) {
                    $user['avatar'] = $new_avatar_path;
                    $user['avatar_large'] = $new_avatar_large_path;
                }
                $updated = true;
                break;
            }
        }

        if ($updated) {
            write_json($users_file, $users);
            $_SESSION['feedback'] = 'Utente aggiornato con successo.';
        } else {
            $_SESSION['error'] = 'Impossibile trovare l\'utente da aggiornare.';
        }
        
        header('Location: ../admin_panel.php?page=users');
        exit();
        
    case 'create_user':
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'player';

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Tutti i campi sono obbligatori.';
            header('Location: ../create_user.php');
            exit();
        }

        $users_file = '../data/users.json';
        $users = read_json($users_file);

        $email_exists = false;
        $username_exists = false;
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $email_exists = true;
                break;
            }
            if ($user['username'] === $username) {
                $username_exists = true;
                break;
            }
        }

        if ($email_exists) {
            $_SESSION['error'] = 'Questa email è già registrata.';
            header('Location: ../create_user.php');
            exit();
        } elseif ($username_exists) {
            $_SESSION['error'] = 'Questo username è già in uso.';
            header('Location: ../create_user.php');
            exit();
        } else {
            $new_user_id = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;

            $new_user = [
                'id' => $new_user_id,
                'username' => $username,
                'email' => $email,
                'password' => $password, // In un'applicazione reale, hashare la password!
                'role' => $role,
                'avatar' => 'img/default_avatar.png',
                'avatar_large' => 'img/default_avatar_large.png'
            ];

            $users[] = $new_user;
            write_json($users_file, $users);

            $_SESSION['feedback'] = 'Nuovo utente creato con successo.';
            header('Location: ../admin_panel.php?page=users');
            exit();
        }
        break;

    default:
        die('Azione non valida.');
}
ob_end_flush(); // End output buffering and send all output
