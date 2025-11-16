<?php
session_start();
require_once 'helpers.php';

$user_id_to_view = $_GET['uid'] ?? null;

if (!$user_id_to_view) {
    die('ID utente mancante.');
}

$users = read_json('data/users.json');
$tournaments = read_json('data/tournaments.json');

$user_data = find_user_by_id($users, $user_id_to_view);

if ($user_data === null) {
    die('Utente non trovato.');
}

// Trova i tornei a cui l'utente ha partecipato
$participated_tournaments = [];
foreach ($tournaments as $tournament) {
    $found_participant = null;
    foreach ($tournament['participants'] as $participant) {
        if ($participant['userId'] == $user_id_to_view) {
            $found_participant = $participant;
            break;
        }
    }

    if ($found_participant) {
        // Calcola W-L-D
        $wins = 0; $losses = 0; $draws = 0;
        foreach ($tournament['matches'] as $round) {
            foreach ($round as $match) {
                if ($match['player1'] == $user_id_to_view || $match['player2'] == $user_id_to_view) {
                    if ($match['winner'] === 'draw') $draws++;
                    elseif ($match['winner'] == $user_id_to_view) $wins++;
                    elseif ($match['winner'] !== null) $losses++;
                }
            }
        }

        // Calcola la classifica
        $sorted_participants = $tournament['participants'];
        usort($sorted_participants, function($a, $b) {
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
        $rank = array_search($user_id_to_view, array_column($sorted_participants, 'userId')) + 1;


        $participated_tournaments[] = [
            'tournament_id' => $tournament['id'],
            'tournament_name' => $tournament['name'],
            'rank' => $rank,
            'wld' => "$wins-$losses-$draws",
            'decklist_name' => $found_participant['decklist_name'] ?? 'N/D',
            'decklist_format' => $found_participant['decklist_format'] ?? 'N/D',
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo di <?php echo htmlspecialchars($user_data['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
    <?php load_theme(); ?>
</head>
<body <?php body_class(); ?>>
    <header class="modern-header">
        <div class="header-content">
            <h1>Profilo di <?php echo htmlspecialchars($user_data['username']); ?></h1>
            <a href="home.php" class="btn-modern">Torna alla Home</a>
        </div>
    </header>

    <main class="modern-main">
        <section class="card text-center mb-4">
            <?php
            $avatar_path = !empty($user_data['avatar']) && file_exists($user_data['avatar']) 
                ? $user_data['avatar'] 
                : 'img/default_avatar.png';
            ?>
            <img src="<?php echo $avatar_path; ?>" alt="User Avatar" class="user-avatar-large">
            <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
        </section>

        <section class="card">
            <h3>Storico Tornei</h3>
            <table class="standings-table">
                <thead>
                    <tr>
                        <th>Torneo</th>
                        <th>Classifica</th>
                        <th>Risultato (V-S-P)</th>
                        <th>Mazzo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($participated_tournaments)): ?>
                        <tr><td colspan="4">Questo utente non ha partecipato a nessun torneo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($participated_tournaments as $participation): ?>
                            <tr>
                                <td><a href="view_tournament.php?tid=<?php echo $participation['tournament_id']; ?>"><?php echo htmlspecialchars($participation['tournament_name']); ?></a></td>
                                <td><?php echo $participation['rank']; ?></td>
                                <td><?php echo htmlspecialchars($participation['wld']); ?></td>
                                <td>
                                    <?php if ($participation['decklist_name'] !== 'N/D'): ?>
                                        <a href="view_decklist.php?tid=<?php echo $participation['tournament_id']; ?>&uid=<?php echo $user_id_to_view; ?>">
                                            <?php echo htmlspecialchars($participation['decklist_name']); ?>
                                        </a>
                                        <small class="text-muted">(<?php echo htmlspecialchars($participation['decklist_format']); ?>)</small>
                                    <?php else: ?>
                                        N/D
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
