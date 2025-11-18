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
        // Gestione dell'upload dell'avatar
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
                $new_filename = 'avatar_' . $user_id_post . '_' . time() . '.' . $extension;

                // Trova l'utente per eliminare il vecchio avatar
                foreach ($users as &$user_to_update) {
                    if ($user_to_update['id'] == $user_id_post) {
                        if (!empty($user_to_update['avatar']) && $user_to_update['avatar'] !== 'img/default_avatar.png' && file_exists('../' . $user_to_update['avatar'])) {
                            unlink('../' . $user_to_update['avatar']);
                        }
                        if (!empty($user_to_update['avatar_large']) && $user_to_update['avatar_large'] !== 'img/default_avatar_large.png' && file_exists('../' . $user_to_update['avatar_large'])) {
                           unlink('../' . $user_to_update['avatar_large']);
                        }
                        break;
                    }
                }
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                    // Aggiorna il percorso dell'avatar per l'utente corrente
                     foreach ($users as &$user_to_update_path) {
                        if ($user_to_update_path['id'] == $user_id_post) {
                            $user_to_update_path['avatar'] = 'data/avatars/' . $new_filename;
                            unset($user_to_update_path['avatar_large']);
                            break;
                        }
                    }
                } else {
                     $_SESSION['error'] = 'Errore durante il caricamento del file.';
                    header('Location: ../edit_user.php?id=' . $user_id_post);
                    exit();
                }
            } else {
                $_SESSION['error'] = 'Tipo di file non valido o dimensione eccessiva (max 5MB).';
                header('Location: ../edit_user.php?id=' . $user_id_post);
                exit();
            }
        }

        // Aggiorna gli altri dati
        foreach ($users as &$user) {
            if ($user['id'] == $user_id_post) {
                $user['username'] = $username;
                $user['email'] = $email;
                $user['role'] = $role;
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
                'avatar' => 'img/default_avatar.png'
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
