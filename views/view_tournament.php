<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// User data retrieval for header
$avatar_path = 'data/avatars/default_avatar.png';
$logged_in_username = null;
if (isset($_SESSION['user_id'])) {
    $users_data = read_json(__DIR__ . '/../data/users.json');
    $current_user = find_user_by_id($users_data, $_SESSION['user_id']);
    if ($current_user) {
        $avatar_path = !empty($current_user['avatar']) && file_exists($current_user['avatar'])
            ? $current_user['avatar']
            : '/data/avatars/default_avatar.png';
    }
    $logged_in_username = $_SESSION['username'];
}

$tournament_id = $_GET['tid'] ?? null;

if (!$tournament_id) {
    die('ID torneo mancante.');
}

$tournaments = read_json(__DIR__ . '/../data/tournaments.json');
$users = read_json(__DIR__ . '/../data/users.json');
$user_map = [];
$avatar_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = $user['username'];
    $avatar_map[$user['id']] = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : '/data/avatars/default_avatar.png';
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
usort($participants, function ($a, $b) {
    $a_score = $a['score'] ?? 0;
    $b_score = $b['score'] ?? 0;
    if ($a_score !== $b_score)
        return $b_score - $a_score;

    $a_gwp = ($a['games_won'] + $a['games_lost'] > 0) ? $a['games_won'] / ($a['games_won'] + $a['games_lost']) : 0;
    $b_gwp = ($b['games_won'] + $b['games_lost'] > 0) ? $b['games_won'] / ($b['games_won'] + $b['games_lost']) : 0;
    if (abs($a_gwp - $b_gwp) > 0.0001)
        return $b_gwp > $a_gwp ? 1 : -1;

    $a_malus = $a['malus'] ?? 0;
    $b_malus = $b['malus'] ?? 0;
    if ($a_malus !== $b_malus)
        return $a_malus - $b_malus;

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
        <section class="card">
            <h2>Dettagli Torneo</h2>
            <ul class="list-group mb-4">
                <li class="list-group-item"><strong>Data:</strong>
                    <?php echo htmlspecialchars($tournament_data['date']); ?></li>
                <li class="list-group-item"><strong>Stato:</strong> <span
                        class="badge bg-<?php echo $tournament_data['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars(ucfirst($tournament_data['status'])); ?></span>
                </li>
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
                        $wins = 0;
                        $losses = 0;
                        $draws = 0;
                        if (isset($tournament_data['matches']) && is_array($tournament_data['matches'])) {
                            foreach ($tournament_data['matches'] as $round) {
                                foreach ($round as $match) {
                                    if ($match['player1'] == $p['userId'] || $match['player2'] == $p['userId']) {
                                        if ($match['winner'] === 'draw')
                                            $draws++;
                                        elseif ($match['winner'] == $p['userId'])
                                            $wins++;
                                        elseif ($match['winner'] !== null)
                                            $losses++;
                                    }
                                }
                            }
                        }
                        $wld_score = "$wins-$losses-$draws";
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="player-cell">
                                <img src="<?php echo $avatar_map[$p['userId']] ?? 'data/avatars/default_avatar.png'; ?>?t=<?php echo time(); ?>"
                                    alt="Avatar" class="player-avatar">
                                <a href="/views/view_profile.php?uid=<?php echo $p['userId']; ?>">
                                    <?php echo htmlspecialchars($user_map[$p['userId']] ?? 'Sconosciuto'); ?>
                                </a>
                            </td>
                            <td><?php echo $wld_score; ?></td>
                            <td>
                                <?php if (!empty($p['decklist_name'])): ?>
                                    <a
                                        href="/views/view_decklist.php?tid=<?php echo $tournament_id; ?>&uid=<?php echo $p['userId']; ?>">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>

</html>