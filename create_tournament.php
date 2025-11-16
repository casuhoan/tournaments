<?php
session_start();

// Controlla se l'utente è loggato e ha i permessi (admin o moderator)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator')) {
    // Potresti mostrare un messaggio di errore o reindirizzare
    die('Accesso negato. Devi essere un amministratore o un moderatore per creare un torneo.');
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raccolta dei dati dal form
    $name = $_POST['name'] ?? '';
    $date = $_POST['date'] ?? '';
    $match_type = $_POST['match_type'] ?? 'bo1';
    $tournament_type = $_POST['tournament_type'] ?? 'swiss';
    $rounds = $_POST['rounds'] ?? 4;
    $decklist_req = $_POST['decklist_req'] ?? 'optional';
    $decklist_visibility = $_POST['decklist_visibility'] ?? 'private';

    // Validazione
    if (empty($name) || empty($date)) {
        $error_message = 'Nome del torneo e data sono obbligatori.';
    } else {
        $tournaments_file = 'data/tournaments.json';
        $tournaments = [];
        if (file_exists($tournaments_file)) {
            $tournaments = json_decode(file_get_contents($tournaments_file), true);
        }

        // Genera un nuovo ID e un link personalizzato
        $new_id = count($tournaments) > 0 ? max(array_column($tournaments, 'id')) + 1 : 101;
        $custom_link = strtolower(str_replace(' ', '-', $name)) . '-' . $new_id;

        $new_tournament = [
            'id' => $new_id,
            'name' => $name,
            'date' => $date,
            'organizerId' => $_SESSION['user_id'],
            'link' => $custom_link,
            'status' => 'pending', // pending, in_progress, completed
            'settings' => [
                'match_type' => $match_type,
                'tournament_type' => $tournament_type,
                'rounds' => (int)$rounds,
                'decklist_req' => $decklist_req,
                'decklist_visibility' => $decklist_visibility,
            ],
            'participants' => [],
            'matches' => [],
        ];

        $tournaments[] = $new_tournament;
        file_put_contents($tournaments_file, json_encode($tournaments, JSON_PRETTY_PRINT));

        $success_message = 'Torneo creato con successo! Link diretto: <a href="http://torneo.grandius.it/tournament.php?link=' . htmlspecialchars($custom_link) . '" target="_blank">http://torneo.grandius.it/tournament.php?link=' . htmlspecialchars($custom_link) . '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Torneo - Gestione Tornei</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1>Crea Nuovo Torneo</h1>
            <a href="home.php" class="btn-modern">Torna alla Home</a>
        </div>
    </header>

    <main class="modern-main">
        <section id="create-tournament-form" class="card">
            <?php if ($error_message): ?><p class="error-message"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>
            <?php if ($success_message): ?><p class="success-message"><?php echo $success_message; ?></p><?php endif; ?>
            
            <form action="create_tournament.php" method="POST" class="modern-form">
                <div class="form-group">
                    <label for="name">Nome Torneo:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="date">Data Torneo:</label>
                    <input type="date" id="date" name="date" required>
                </div>
                
                <fieldset>
                    <legend>Impostazioni Partita</legend>
                    <div class="form-group">
                        <label>Formato Partita:</label>
                        <input type="radio" id="bo1" name="match_type" value="bo1" checked> <label for="bo1">Best of 1 (Bo1)</label>
                        <input type="radio" id="bo3" name="match_type" value="bo3"> <label for="bo3">Best of 3 (Bo3)</label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Impostazioni Torneo</legend>
                    <div class="form-group">
                        <label>Tipo di Torneo:</label>
                        <input type="radio" id="swiss" name="tournament_type" value="swiss" checked> <label for="swiss">Alla Svizzera</label>
                        <input type="radio" id="elimination" name="tournament_type" value="elimination"> <label for="elimination">Eliminazione Diretta</label>
                    </div>
                    <div class="form-group">
                        <label for="rounds">Numero di Turni (per Svizzera):</label>
                        <input type="number" id="rounds" name="rounds" min="1" value="4">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Impostazioni Lista Mazzo (Decklist)</legend>
                    <div class="form-group">
                        <label>Requisito Lista:</label>
                        <input type="radio" id="decklist_optional" name="decklist_req" value="optional" checked> <label for="decklist_optional">Opzionale</label>
                        <input type="radio" id="decklist_mandatory" name="decklist_req" value="mandatory"> <label for="decklist_mandatory">Obbligatoria</label>
                    </div>
                    <div class="form-group">
                        <label>Visibilità Liste:</label>
                        <input type="radio" id="decklist_private" name="decklist_visibility" value="private" checked> <label for="decklist_private">Private</label>
                        <input type="radio" id="decklist_public" name="decklist_visibility" value="public"> <label for="decklist_public">Pubbliche</label>
                    </div>
                </fieldset>

                <button type="submit" class="btn-modern">Crea Torneo</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
