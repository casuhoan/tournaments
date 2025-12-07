<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// User data retrieval for header
$header_avatar_path = '/data/avatars/default_avatar.png';
$logged_in_username = null;
if (isset($_SESSION['user_id'])) {
    $users_data_for_header = read_json(__DIR__ . '/../data/users.json');
    $current_user_for_header = find_user_by_id($users_data_for_header, $_SESSION['user_id']);
    if ($current_user_for_header) {
        $header_avatar_path = !empty($current_user_for_header['avatar']) && file_exists($current_user_for_header['avatar']) 
            ? $current_user_for_header['avatar'] 
            : '/data/avatars/default_avatar.png';
    }
    $logged_in_username = $_SESSION['username'];
}


$user_id_to_view = $_GET['uid'] ?? null;

if (!$user_id_to_view) {
    die('ID utente mancante.');
}

$users = read_json(__DIR__ . '/../data/users.json');
$tournaments = read_json(__DIR__ . '/../data/tournaments.json');

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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <a href="<?php echo isset($_SESSION['user_id']) ? '/home.php' : '/index.php'; ?>" class="site-brand">Gestione Tornei</a>
            <nav class="main-nav">
                <a href="<?php echo isset($_SESSION['user_id']) ? '/home.php' : '/index.php'; ?>">Home</a>
                <a href="/views/all_tournaments.php">Vedi tutti i tornei</a>
            </nav>
            <div class="user-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $header_avatar_path; ?>" alt="User Avatar" class="user-avatar me-2">
                            <span class="username"><?php echo htmlspecialchars($logged_in_username); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="/views/view_profile.php?uid=<?php echo $_SESSION['user_id']; ?>">Profilo</a></li>
                            <li><a class="dropdown-item" href="/forms/settings.php">Impostazioni</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/admin/index.php">Pannello Admin</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
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
        <section class="card text-center mb-4">
            <img src="<?php echo (!empty($user_data['avatar']) && file_exists($user_data['avatar'])) ? $user_data['avatar'] : '/data/avatars/default_avatar.png'; ?>?t=<?php echo time(); ?>" alt="User Avatar" class="user-avatar-large">
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
                                <td><a href="/views/view_tournament.php?tid=<?php echo $participation['tournament_id']; ?>"><?php echo htmlspecialchars($participation['tournament_name']); ?></a></td>
                                <td><?php echo $participation['rank']; ?></td>
                                <td><?php echo htmlspecialchars($participation['wld']); ?></td>
                                <td>
                                    <?php if ($participation['decklist_name'] !== 'N/D'): ?>
                                        <a href="/views/view_decklist.php?tid=<?php echo $participation['tournament_id']; ?>&uid=<?php echo $user_id_to_view; ?>">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
