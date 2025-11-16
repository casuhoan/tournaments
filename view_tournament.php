<?php
session_start();
require_once 'helpers.php';

$tournament_id = $_GET['tid'] ?? null;

if (!$tournament_id) {
    die('ID torneo mancante.');
}

$tournaments = read_json('data/tournaments.json');
$users = read_json('data/users.json');
$user_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = $user['username'];
}

$tournament_data = null;
foreach ($tournaments as $t) {
    if ($t['id'] == $tournament_id) {
        $tournament_data = $t;
        break;
    }
}

if ($tournament_data === null) {
    die('Torneo non trovato.');
}

// Ordina i partecipanti per la classifica finale
$participants = $tournament_data['participants'];
usort($participants, function($a, $b) {
    $a_score = $a['score'] ?? 0;
    $b_score = $b['score'] ?? 0;
    if ($a_score !== $b_score) return $b_score - $a_score;
    
    $a_gwp = ($a['games_won'] + $a['games_lost'] > 0) ? $a['games_won'] / ($a['games_won'] + $a['games_lost']) : 0;
    $b_gwp = ($b['games_won'] + $b['games_lost'] > 0) ? $b['games_won'] / ($b['games_won'] + $b['games_lost']) : 0;
    if (abs($a_gwp - $b_gwp) > 0.0001) return $b_gwp > $a_gwp ? 1 : -1;

    $a_malus = $a['malus'] ?? 0;
    $b_malus = $b['malus'] ?? 0;
    if ($a_malus !== $b_malus) return $a_malus - $b_malus;

    return rand(-1, 1);
});

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Torneo: <?php echo htmlspecialchars($tournament_data['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars($tournament_data['name']); ?></h1>
            <a href="home.php" class="btn-modern">Torna alla Home</a>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Dettagli Torneo</h2>
            <ul class="list-group mb-4">
                <li class="list-group-item"><strong>Data:</strong> <?php echo htmlspecialchars($tournament_data['date']); ?></li>
                <li class="list-group-item"><strong>Stato:</strong> <span class="badge bg-<?php echo $tournament_data['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars(ucfirst($tournament_data['status'])); ?></span></li>
            </ul>
        </section>
        <section class="card">
            <h3>Classifica Finale</h3>
            <table class="standings-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Giocatore</th>
                        <th>Risultato (V-S-P)</th>
                        <th>Mazzo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $index => $p): ?>
                        <?php
                        // Calcola W-L-D per questo giocatore
                        $wins = 0; $losses = 0; $draws = 0;
                        if (isset($tournament_data['matches']) && is_array($tournament_data['matches'])) {
                            foreach ($tournament_data['matches'] as $round) {
                                foreach ($round as $match) {
                                    if ($match['player1'] == $p['userId'] || $match['player2'] == $p['userId']) {
                                        if ($match['winner'] === 'draw') $draws++;
                                        elseif ($match['winner'] == $p['userId']) $wins++;
                                        elseif ($match['winner'] !== null) $losses++;
                                    }
                                }
                            }
                        }
                        $wld_score = "$wins-$losses-$draws";
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <a href="view_profile.php?uid=<?php echo $p['userId']; ?>">
                                    <?php echo htmlspecialchars($user_map[$p['userId']] ?? 'Sconosciuto'); ?>
                                </a>
                            </td>
                            <td><?php echo $wld_score; ?></td>
                            <td>
                                <?php if (!empty($p['decklist_name'])): ?>
                                    <a href="view_decklist.php?tid=<?php echo $tournament_id; ?>&uid=<?php echo $p['userId']; ?>">
                                        <?php echo htmlspecialchars($p['decklist_name']); ?>
                                    </a>
                                <?php else: ?>
                                    N/D
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
