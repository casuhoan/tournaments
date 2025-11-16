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

    default:
        die('Azione non valida.');
}
ob_end_flush(); // End output buffering and send all output
