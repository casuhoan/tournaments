<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php'; // Include il file delle funzioni helper

// User data retrieval for header
$avatar_path = '/data/avatars/default_avatar.png';
$logged_in_username = null;
if (isset($_SESSION['user_id'])) {
    $users_data = read_json(__DIR__ . '/../data/users.json');
    $current_user = find_user_by_id($users_data, $_SESSION['user_id']);
    if ($current_user) {
        $avatar_path = !empty($current_user['avatar'])
            ? '/' . $current_user['avatar']
            : '/data/avatars/default_avatar.png';
    }
    $logged_in_username = $_SESSION['username'];
}

// --- Funzioni Helper Specifiche per questa pagina ---
function is_user_registered($user_id, $tournament)
{
    if (!isset($tournament['participants']))
        return false;
    $user_id_int = (int) $user_id; // Cast to int for robust comparison
    foreach ($tournament['participants'] as $participant) {
        if ($participant['userId'] === $user_id_int) {
            return true;
        }
    }
    return false;
}

// --- Logica Principale ---
$link = $_GET['link'] ?? '';
if (empty($link)) {
    die('Link del torneo non specificato.');
}

$tournaments = read_json(__DIR__ . '/../data/tournaments.json');
$tournament = find_tournament_by_link($link, $tournaments);

if ($tournament === null) {
    http_response_code(404);
    die('Torneo non trovato.');
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$is_registered = $is_logged_in ? is_user_registered($user_id, $tournament) : false;
$is_organizer = $is_logged_in && isset($tournament['organizerId']) && $tournament['organizerId'] === $user_id;

// Carica i nomi e gli avatar degli utenti per la visualizzazione
$users = read_json(__DIR__ . '/../data/users.json');
$user_map = [];
$avatar_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = $user['username'];
    $avatar_map[$user['id']] = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : '/data/avatars/default_avatar.png';
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tournament['name']); ?> - Gestione Tornei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/modern_style.css">
</head>

<body>
    <header class="modern-header">
        <div class="header-content">
            <a href="<?php echo isset($_SESSION['user_id']) ? '/home.php' : '/index.php'; ?>"
                class="site-brand">Gestione Tornei</a>
            <nav class="main-nav">
                <a href="<?php echo isset($_SESSION['user_id']) ? '/home.php' : '/index.php'; ?>">Home</a>
                <a href="/views/all_tournaments.php">Vedi tutti i tornei</a>
            </nav>
            <div class="user-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $avatar_path; ?>?t=<?php echo time(); ?>" alt="User Avatar"
                                class="user-avatar me-2">
                            <span class="username"><?php echo htmlspecialchars($logged_in_username); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item"
                                    href="/views/view_profile.php?uid=<?php echo $_SESSION['user_id']; ?>">Profilo</a></li>
                            <li><a class="dropdown-item" href="/forms/settings.php">Impostazioni</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/admin/index.php">Pannello Admin</a></li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/home.php?action=logout">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="/register.php" class="btn btn-primary">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="modern-main">
        <?php if ($tournament['status'] === 'pending' && !$is_logged_in): ?>
            <section id="login-to-join" class="card">
                <h2>Torneo Trovato!</h2>
                <p>Per partecipare a questo torneo o vederne i dettagli, devi prima accedere.</p>
                <a href="/login.php" class="btn-modern">Accedi per Partecipare</a>
            </section>
        <?php elseif ($tournament['status'] !== 'pending' && !$is_registered): ?>
            <section id="spectator-mode" class="card">
                <h2>Modalità Spettatore</h2>
                <p>Stai guardando questo torneo come spettatore.</p>
                <?php if (!$is_logged_in): ?>
                    <p><a href="/login.php">Accedi</a> per iscriverti ad altri tornei.</p>
                <?php endif; ?>
            </section>
        <?php else: // Logged in and (is_registered OR tournament is pending) ?>

            <?php // --- VISTA ORGANIZZATORE --- ?>
            <?php if ($is_organizer): ?>
                <?php if ($tournament['status'] === 'pending'): ?>
                    <section id="organizer-panel" class="card">
                        <h2>Pannello Organizzatore</h2>
                        <p>Ci sono <?php echo count($tournament['participants']); ?> partecipanti iscritti.</p>
                        <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                            <input type="hidden" name="action" value="start_tournament">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit" class="btn-modern" <?php echo count($tournament['participants']) < 2 ? 'disabled' : ''; ?>>Avvia Torneo</button>
                            <?php if (count($tournament['participants']) < 2): ?><small> (minimo 2 giocatori)</small><?php endif; ?>
                        </form>
                    </section>
                <?php elseif ($tournament['status'] === 'in_progress'):
                    $current_round_key = !empty($tournament['matches']) ? array_key_last($tournament['matches']) : null;
                    $current_round_num = (int) str_replace('round_', '', $current_round_key);
                    $all_matches_done = true;
                    if ($current_round_key) {
                        foreach ($tournament['matches'][$current_round_key] as $match) {
                            if ($match['winner'] === null) {
                                $all_matches_done = false;
                                break;
                            }
                        }
                    }
                    $can_start_next_round = $all_matches_done &&
                        $tournament['settings']['tournament_type'] === 'swiss' &&
                        $current_round_num < $tournament['settings']['rounds'];
                    ?>
                    <section id="organizer-panel" class="card">
                        <h2>Pannello Organizzatore</h2>
                        <p>Stato del round corrente: <?php echo $all_matches_done ? 'Completato' : 'In corso'; ?></p>
                        <?php if ($can_start_next_round): ?>
                            <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                                <input type="hidden" name="action" value="start_next_round">
                                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                <button type="submit" class="btn-modern">Avvia Round <?php echo $current_round_num + 1; ?></button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <?php // --- VISTA GIOCATORE --- ?>
            <section id="tournament-lobby" class="card">
                <h2>Lobby del Torneo</h2>
                <p><strong>Stato:</strong> <?php echo htmlspecialchars(ucfirst($tournament['status'])); ?></p>

                <?php if ($tournament['status'] === 'pending'): ?>
                    <p>Il torneo non è ancora iniziato. In attesa che l'organizzatore avvii il primo turno.</p>

                    <?php if (!$is_registered): ?>
                        <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                            <input type="hidden" name="action" value="join_tournament">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit" class="btn-modern">Iscriviti al Torneo</button>
                        </form>
                    <?php else: ?>
                        <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                            <input type="hidden" name="action" value="leave_tournament">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit" class="btn-modern btn-danger">Annulla Iscrizione</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($is_registered): ?>
                        <div id="decklist-submission">
                            <h3>Invia la tua Lista</h3>
                            <?php if ($tournament['settings']['decklist_req'] === 'mandatory'): ?>
                                <p><strong>L'invio della lista è obbligatorio per partecipare.</strong></p>
                            <?php else: ?>
                                <p>L'invio della lista è opzionale.</p>
                            <?php endif; ?>
                            <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                                <input type="hidden" name="action" value="submit_decklist">
                                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                <textarea name="decklist" rows="10" placeholder="Copia e incolla qui la tua lista..."
                                    class="form-control"></textarea>
                                <button type="submit" class="btn-modern">Invia Lista</button>
                            </form>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tournament['status'] === 'in_progress'): ?>

                    <div id="current-match">
                        <h3>Il Tuo Match Corrente</h3>
                        <?php
                        // Trova il round corrente (l'ultimo nel array 'matches')
                        $current_round_key = !empty($tournament['matches']) ? array_key_last($tournament['matches']) : null;
                        $current_match = null;
                        if ($current_round_key) {
                            foreach ($tournament['matches'][$current_round_key] as $match) {
                                if ($match['player1'] == $user_id || $match['player2'] == $user_id) {
                                    $current_match = $match;
                                    break;
                                }
                            }
                        }

                        if ($current_match):
                            $opponent_id = ($current_match['player1'] == $user_id) ? $current_match['player2'] : $current_match['player1'];
                            $opponent_name = $user_map[$opponent_id] ?? 'Sconosciuto';
                            ?>
                            <p>
                                <strong>Tavolo:</strong> <?php echo htmlspecialchars($current_match['table']); ?><br>
                                <strong>Avversario:</strong> <?php echo htmlspecialchars($opponent_name); ?>
                            </p>

                            <?php if ($current_match['winner'] === null): ?>
                                <div id="result-submission">
                                    <h4>Invia Risultato</h4>
                                    <form action="/api/tournament_actions.php" method="POST" class="modern-form">
                                        <input type="hidden" name="action" value="submit_result">
                                        <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                        <input type="hidden" name="round_key" value="<?php echo $current_round_key; ?>">
                                        <input type="hidden" name="player1_id" value="<?php echo $current_match['player1']; ?>">
                                        <input type="hidden" name="player2_id" value="<?php echo $current_match['player2']; ?>">

                                        <div class="score-ui">
                                            <div class="player-score">
                                                <span><?php echo htmlspecialchars($user_map[$current_match['player1']]); ?></span>
                                                <div class="score-controls">
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick="updateScore('score1', -1)">-</button>
                                                    <span id="score1-display" class="score-display">0</span>
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick="updateScore('score1', 1)">+</button>
                                                </div>
                                            </div>
                                            <div class="player-score">
                                                <span><?php echo htmlspecialchars($user_map[$current_match['player2']]); ?></span>
                                                <div class="score-controls">
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick="updateScore('score2', -1)">-</button>
                                                    <span id="score2-display" class="score-display">0</span>
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick="updateScore('score2', 1)">+</button>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="score1-input" name="score1" value="0">
                                        <input type="hidden" id="score2-input" name="score2" value="0">

                                        <div class="form-group">
                                            <input type="checkbox" id="is_draw" name="is_draw">
                                            <label for="is_draw">La partita è finita in pareggio?</label>
                                        </div>

                                        <button type="submit" class="btn-modern">Invia Risultato Finale</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p><strong>Risultato Inviato:</strong>
                                    <?php echo htmlspecialchars($current_match['score1'] . ' - ' . $current_match['score2']); ?></p>
                                <p>In attesa che tutti i giocatori completino il turno.</p>
                            <?php endif; ?>

                        <?php else: ?>
                            <p>Hai un turno di riposo (BYE) o il tuo match non è stato ancora generato. Ricarica tra poco.</p>
                        <?php endif; ?>
                    </div>
                    <a href="/tournament.php?link=<?php echo $link; ?>" class="btn-modern">Ricarica Pagina</a>

                <?php else: // 'completed' ?>
                    <p>Il torneo è concluso. Grazie per aver partecipato!</p>
                    <a href="/views/view_tournament.php?tid=<?php echo $tournament['id']; ?>" class="btn btn-primary">Vedi
                        Dettagli Torneo</a>
                    <?php // Qui verrà visualizzata la classifica finale ?>
                <?php endif; ?>
            </section>

            <?php
            // Sezione Classifica Parziale
            if ($tournament['status'] !== 'pending') {
                $standings = $tournament['participants'];
                // Ordina secondo le nuove regole di spareggio
                usort($standings, function ($a, $b) {
                    // 1. Punteggio (decrescente)
                    if ($a['score'] !== $b['score']) {
                        return $b['score'] - $a['score'];
                    }

                    // 2. Game Win Percentage (decrescente)
                    $gwp_a = ($a['games_won'] + $a['games_lost'] > 0) ? $a['games_won'] / ($a['games_won'] + $a['games_lost']) : 0;
                    $gwp_b = ($b['games_won'] + $b['games_lost'] > 0) ? $b['games_won'] / ($b['games_won'] + $b['games_lost']) : 0;
                    if ($gwp_a !== $gwp_b) {
                        return $gwp_b > $gwp_a ? 1 : -1;
                    }

                    // 3. Malus (crescente)
                    if ($a['malus'] !== $b['malus']) {
                        return $a['malus'] - $b['malus'];
                    }

                    // 4. Casuale
                    return rand(-1, 1);
                });
                ?>
                <section id="standings" class="card">
                    <h3>Classifica Parziale</h3>
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Giocatore</th>
                                <th>Punti</th>
                                <th>GWP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $index => $player):
                                $gwp = ($player['games_won'] + $player['games_lost'] > 0) ? $player['games_won'] / ($player['games_won'] + $player['games_lost']) : 0;
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="player-cell">
                                        <img src="<?php echo $avatar_map[$player['userId']] ?? '/data/avatars/default_avatar.png'; ?>?t=<?php echo time(); ?>"
                                            alt="Avatar" class="player-avatar">
                                        <a href="/views/view_profile.php?uid=<?php echo $player['userId']; ?>">
                                            <?php echo htmlspecialchars($user_map[$player['userId']] ?? 'Sconosciuto'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($player['score']); ?></td>
                                    <td><?php echo round($gwp * 100, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php } ?>

            <section id="participant-list" class="card">
                <h3>Partecipanti Iscritti (<?php echo count($tournament['participants']); ?>)</h3>
                <ul>
                    <?php foreach ($tournament['participants'] as $participant): ?>
                        <li class="player-list-item">
                            <img src="<?php echo $avatar_map[$participant['userId']] ?? '/data/avatars/default_avatar.png'; ?>?t=<?php echo time(); ?>"
                                alt="Avatar" class="player-avatar">
                            <span><?php echo htmlspecialchars($user_map[$participant['userId']] ?? 'Utente Sconosciuto'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>

</html>