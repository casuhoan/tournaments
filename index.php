<?php
session_start();
require_once 'helpers.php'; // Include il file delle funzioni helper

// Leggi i dati
$users = read_json('data/users.json');
$tournaments = read_json('data/tournaments.json');

// Crea una mappa da userId a username per un accesso rapido
$user_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = $user['username'];
}

// Ordina i tornei per data, dal più recente al più vecchio
usort($tournaments, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Prendi gli ultimi 3 tornei
$recent_tournaments = array_slice($tournaments, 0, 3);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Tornei</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <h1>Gestione Tornei</h1>
    </header>

    <main class="modern-main">
        <section id="recent-results" class="card">
            <h2>Ultimi Risultati</h2>
            <?php if (empty($recent_tournaments)): ?>
                <p>Nessun torneo recente da mostrare.</p>
            <?php else: ?>
                <?php foreach ($recent_tournaments as $tournament): ?>
                    <div class="tournament-summary">
                        <h3><?php echo htmlspecialchars($tournament['name']); ?> (<?php echo htmlspecialchars($tournament['date']); ?>)</h3>
                        <ul>
                            <?php
                            $participants = $tournament['participants'];
                            // Ordina i partecipanti per calcolare la classifica
                            usort($participants, function($a, $b) {
                                // Assicurati che le chiavi esistano per evitare warning
                                $a_score = $a['score'] ?? 0;
                                $b_score = $b['score'] ?? 0;
                                $a_malus = $a['malus'] ?? 0;
                                $b_malus = $b['malus'] ?? 0;
                                $a_games_won = $a['games_won'] ?? 0;
                                $a_games_lost = $a['games_lost'] ?? 0;
                                $b_games_won = $b['games_won'] ?? 0;
                                $b_games_lost = $b['games_lost'] ?? 0;

                                if ($a_score !== $b_score) {
                                    return $b_score - $a_score;
                                }
                                $gwp_a = ($a_games_won + $a_games_lost > 0) ? $a_games_won / ($a_games_won + $a_games_lost) : 0;
                                $gwp_b = ($b_games_won + $b_games_lost > 0) ? $b_games_won / ($b_games_won + $b_games_lost) : 0;
                                if (abs($gwp_a - $gwp_b) > 0.0001) {
                                    return $gwp_b > $gwp_a ? 1 : -1;
                                }
                                if ($a_malus !== $b_malus) {
                                    return $a_malus - $b_malus;
                                }
                                return rand(-1, 1);
                            });
                            
                            // Prendi i primi 3
                            $top_players = array_slice($participants, 0, 3);

                            foreach ($top_players as $index => $player) {
                                $username = isset($user_map[$player['userId']]) ? $user_map[$player['userId']] : 'Sconosciuto';
                                $rank = $index + 1;
                                echo '<li><strong>Rank ' . $rank . ':</strong> ' . htmlspecialchars($username) . '</li>';
                            }
                            ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <section id="login-prompt" class="card">
                <h2>Accedi per Gestire</h2>
                <p>Accedi o registrati per creare i tuoi tornei, partecipare e vedere le classifiche complete.</p>
                <a href="login.php" class="btn-modern">Accedi</a>
                <a href="register.php" class="btn-modern">Registrati</a>
            </section>
        <?php else: ?>
            <section id="dashboard-link" class="card">
                <h2>Bentornato, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Vai alla tua home per gestire i tornei.</p>
                <a href="home.php" class="btn-modern">Vai alla Home</a>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
