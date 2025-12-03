<?php

// File per le funzioni di utilità condivise in tutto il progetto

// Include security functions
if (file_exists(__DIR__ . '/security.php')) {
    require_once __DIR__ . '/security.php';
}

/**
 * Legge e decodifica un file JSON.
 * @param string $file_path Il percorso del file.
 * @return array I dati decodificati o un array vuoto.
 */
function read_json($file_path)
{
    if (!file_exists($file_path)) {
        return [];
    }
    $json_data = file_get_contents($file_path);
    return json_decode($json_data, true);
}

/**
 * Scrive dati in un file JSON.
 * @param string $file_path Il percorso del file.
 * @param array $data I dati da scrivere.
 */
function write_json($file_path, $data)
{
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Trova un torneo tramite il suo link personalizzato.
 * @param string $link Il link da cercare.
 * @param array $tournaments L'array dei tornei.
 * @return array|null Il torneo trovato o null.
 */
function find_tournament_by_link($link, $tournaments)
{
    foreach ($tournaments as $tournament) {
        if (isset($tournament['link']) && $tournament['link'] === $link) {
            return $tournament;
        }
    }
    return null;
}

/**
 * Trova un utente tramite il suo ID.
 * @param array $users L'array degli utenti.
 * @param int $id L'ID da cercare.
 * @return array|null L'utente trovato o null.
 */
function find_user_by_id($users, $id)
{
    foreach ($users as $user) {
        if ($user['id'] == $id) {
            return $user;
        }
    }
    return null;
}

?>