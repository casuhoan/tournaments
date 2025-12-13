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
        $avatar_path = !empty($current_user['avatar']) ? '/' . $current_user['avatar'] : '/data/avatars/default_avatar.png';
    }
    $logged_in_username = $_SESSION['username'];
}

$tournament_id = $_GET['tid'] ?? null;
$user_id = $_GET['uid'] ?? null;

if (!$tournament_id || !$user_id) {
    die('ID torneo o utente mancanti.');
}

$tournaments = read_json(__DIR__ . '/../data/tournaments.json');
$users = read_json(__DIR__ . '/../data/users.json');

$tournament_data = null;
$participant_data = null;

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

// Trova il nome e l'avatar del giocatore
$player = find_user_by_id($users, $user_id);
$player_name = 'Sconosciuto';
$player_avatar = 'data/avatars/default_avatar.png';
if ($player) {
    $player_name = $player['username'];
    $player_avatar = !empty($player['avatar']) ? '/' . $player['avatar'] : '/data/avatars/default_avatar.png';
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
    <link rel="stylesheet" href="/assets/css/premium_design.css">
    <link rel="stylesheet" href="/assets/css/components.css">
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
            <h2>Dettagli Lista</h2>
            <ul class="list-group">
                <li class="list-group-item player-cell">
                    <strong>Giocatore:</strong>
                    <img src="<?php echo $player_avatar; ?>?t=<?php echo time(); ?>" alt="Avatar"
                        class="player-avatar ms-2">
                    <a
                        href="/views/view_profile.php?uid=<?php echo $user_id; ?>"><?php echo htmlspecialchars($player_name); ?></a>
                </li>
                <li class="list-group-item">
                    <strong>Torneo:</strong>
                    <a
                        href="/views/view_tournament.php?tid=<?php echo $tournament_id; ?>"><?php echo htmlspecialchars($tournament_data['name']); ?></a>
                </li>
                <li class="list-group-item">
                    <strong>Formato:</strong>
                    <?php echo htmlspecialchars($participant_data['decklist_format'] ?: 'N/D'); ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>

</html>