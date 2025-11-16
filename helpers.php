<?php

// File per le funzioni di utilità condivise in tutto il progetto

if (!function_exists('read_json')) {
    /**
     * Legge e decodifica un file JSON.
     * @param string $file_path Il percorso del file.
     * @return array I dati decodificati o un array vuoto.
     */
    function read_json($file_path) {
        if (!file_exists($file_path)) {
            return [];
        }
        $json_data = file_get_contents($file_path);
        return json_decode($json_data, true);
    }
}

if (!function_exists('write_json')) {
    /**
     * Scrive dati in un file JSON.
     * @param string $file_path Il percorso del file.
     * @param array $data I dati da scrivere.
     */
    function write_json($file_path, $data) {
        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('find_tournament_by_link')) {
    /**
     * Trova un torneo tramite il suo link personalizzato.
     * @param string $link Il link da cercare.
     * @param array $tournaments L'array dei tornei.
     * @return array|null Il torneo trovato o null.
     */
    function find_tournament_by_link($link, $tournaments) {
        foreach ($tournaments as $tournament) {
            if (isset($tournament['link']) && $tournament['link'] === $link) {
                return $tournament;
            }
        }
        return null;
    }
}

if (!function_exists('find_user_by_id')) {
    /**
     * Trova un utente tramite il suo ID.
     * @param array $users L'array degli utenti.
     * @param int $id L'ID da cercare.
     * @return array|null L'utente trovato o null.
     */
    function find_user_by_id($users, $id) {
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
}

if (!function_exists('debug_log')) {

    /**

     * Logs a message to a debug file.

     * @param mixed $message The message to log.

     */

    function debug_log($message) {

        $log_file = 'debug.log';

        $timestamp = date('Y-m-d H:i:s');

        $log_message = is_string($message) ? $message : print_r($message, true);

        file_put_contents($log_file, "[$timestamp] " . $log_message . "\n", FILE_APPEND);

    }

}



if (!function_exists('get_current_user')) {

    /**

     * Gets the current logged-in user's data.

     * @return array|null The user data or null if not logged in.

     */

    function get_current_user() {

        debug_log("Inside get_current_user()");

        if (!isset($_SESSION['user_id'])) {

            debug_log("user_id not in session");

            return null;

        }

        debug_log("user_id in session: " . $_SESSION['user_id']);



        $users = read_json('data/users.json');

        debug_log("Result of read_json:");

        debug_log($users);



        $current_user = find_user_by_id($users, $_SESSION['user_id']);

        debug_log("Result of find_user_by_id:");

        debug_log($current_user);



        if ($current_user && (empty($current_user['avatar']) || !file_exists($current_user['avatar']))) {

            $current_user['avatar'] = 'img/default_avatar.png';

        }



        debug_log("Final current_user being returned:");

        debug_log($current_user);



        return $current_user;

    }

}

if (!function_exists('load_theme')) {
    /**
     * Outputs the appropriate theme stylesheet link.
     */
    function load_theme() {
        $current_user = get_current_user();
        $theme = $current_user['theme'] ?? 'light';
        if ($theme === 'dark') {
            echo '<link rel="stylesheet" href="css/dark_theme.css">';
        }
    }
}

if (!function_exists('body_class')) {
    /**
     * Outputs the body tag with the appropriate theme class.
     */
    function body_class() {
        $current_user = get_current_user();
        $theme = $current_user['theme'] ?? 'light';
        echo 'class="' . $theme . '-theme"';
    }
}

?>
