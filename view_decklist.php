<?php
session_start();
require_once 'helpers.php';

$tournament_id = $_GET['tid'] ?? null;
$user_id = $_GET['uid'] ?? null;

if (!$tournament_id || !$user_id) {
    die('ID torneo o utente mancanti.');
}

$tournaments = read_json('data/tournaments.json');
$users = read_json('data/users.json');

$tournament_data = null;
$participant_data = null;
$player_name = 'Sconosciuto';

// Trova il torneo e il partecipante
foreach ($tournaments as $t) {
    if ($t['id'] == $tournament_id) {
        $tournament_data = $t;
        foreach ($t['participants'] as $p) {
            if ($p['userId'] == $user_id) {
                $participant_data = $p;
                break;
            }
        }
        break;
    }
}

// Trova il nome del giocatore
$player = find_user_by_id($users, $user_id);
if ($player) {
    $player_name = $player['username'];
}

if ($tournament_data === null || $participant_data === null) {
    die('Dati della lista non trovati.');
}

// Calcola il punteggio W-L-D
$wins = 0;
$losses = 0;
$draws = 0;
foreach ($tournament_data['matches'] as $round) {
    foreach ($round as $match) {
        if ($match['player1'] == $user_id || $match['player2'] == $user_id) {
            if ($match['winner'] === 'draw') {
                $draws++;
            } elseif ($match['winner'] == $user_id) {
                $wins++;
            } elseif ($match['winner'] !== null) {
                $losses++;
            }
        }
    }
}
$wld_score = "$wins-$losses-$draws";

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Lista: <?php echo htmlspecialchars($participant_data['decklist_name'] ?: 'N/D'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars($participant_data['decklist_name'] ?: 'N/D'); ?></h1>
            <a href="home.php" class="btn-modern">Torna alla Home</a>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Dettagli Lista</h2>
            <ul class="list-group">
                <li class="list-group-item">
                    <strong>Giocatore:</strong> 
                    <a href="view_profile.php?uid=<?php echo $user_id; ?>"><?php echo htmlspecialchars($player_name); ?></a>
                </li>
                <li class="list-group-item">
                    <strong>Torneo:</strong> 
                    <a href="view_tournament.php?tid=<?php echo $tournament_id; ?>"><?php echo htmlspecialchars($tournament_data['name']); ?></a>
                </li>
                <li class="list-group-item">
                    <strong>Formato:</strong> <?php echo htmlspecialchars($participant_data['decklist_format'] ?: 'N/D'); ?>
                </li>
                <li class="list-group-item">
                    <strong>Risultato:</strong> <?php echo $wld_score; ?>
                </li>
            </ul>
        </section>
        <section class="card">
            <h3>Lista Mazzo</h3>
            <pre class="decklist-box"><?php echo htmlspecialchars($participant_data['decklist']); ?></pre>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
