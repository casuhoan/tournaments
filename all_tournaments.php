<?php
session_start();
require_once 'helpers.php';

$all_tournaments = read_json('data/tournaments.json');
$users = read_json('data/users.json');
$user_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = [
        'username' => $user['username'],
        'avatar' => !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'img/default_avatar.png'
    ];
}

// Filtri
$search_name = $_GET['name'] ?? '';
$filter_type = $_GET['filter'] ?? 'all'; // all, in_progress_participating, completed_participating, in_progress_all
$current_user_id = $_SESSION['user_id'] ?? null;

$filtered_tournaments = array_filter($all_tournaments, function($t) use ($search_name, $filter_type, $current_user_id) {
    $name_match = empty($search_name) || stripos($t['name'], $search_name) !== false;
    if (!$name_match) return false;

    $is_participating = false;
    if ($current_user_id) {
        foreach ($t['participants'] as $p) {
            if ($p['userId'] == $current_user_id) {
                $is_participating = true;
                break;
            }
        }
    }

    switch ($filter_type) {
        case 'in_progress_participating':
            return $t['status'] === 'in_progress' && $is_participating;
        case 'completed_participating':
            return $t['status'] === 'completed' && $is_participating;
        case 'in_progress_all':
            return $t['status'] === 'in_progress';
        case 'all':
        default:
            return true;
    }
});

// Paginazione
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$total_count = count($filtered_tournaments);
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;
$paginated_tournaments = array_slice($filtered_tournaments, $offset, $per_page);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti i Tornei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
    <?php load_theme(); ?>
</head>
<body <?php body_class(); ?>>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="home.php">Gestione Tornei</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="all_tournaments.php">Vedi tutti i tornei</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $current_user = get_current_user();
                    $avatar_path = $current_user['avatar'];
                    ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $avatar_path; ?>" alt="User Avatar" class="user-avatar me-2">
                            <span class="username"><?php echo htmlspecialchars($current_user['username']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end text-small shadow">
                            <li><a class="dropdown-item" href="view_profile.php?uid=<?php echo $_SESSION['user_id']; ?>">Profilo</a></li>
                            <li><a class="dropdown-item" href="settings.php">Impostazioni</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin_panel.php">Pannello Admin</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="home.php?action=logout">Logout</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="modern-main">
        <section id="filters" class="card mb-4">
            <form action="all_tournaments.php" method="GET" class="row g-3 align-items-center modern-form">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="Filtra per nome..." value="<?php echo htmlspecialchars($search_name); ?>">
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-select">
                        <option value="all" <?php if($filter_type === 'all') echo 'selected'; ?>>Tutti i tornei</option>
                        <option value="in_progress_all" <?php if($filter_type === 'in_progress_all') echo 'selected'; ?>>Tornei in corso (tutti)</option>
                        <option value="in_progress_participating" <?php if($filter_type === 'in_progress_participating') echo 'selected'; ?>>Tornei in corso (a cui partecipo)</option>
                        <option value="completed_participating" <?php if($filter_type === 'completed_participating') echo 'selected'; ?>>Tornei completati (a cui ho partecipato)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-modern">Filtra</button>
                </div>
            </form>
        </section>

        <section id="tournament-list" class="card">
            <?php foreach ($paginated_tournaments as $tournament): ?>
                <div class="tournament-card">
                    <h3>
                        <a href="tournament.php?link=<?php echo $tournament['link']; ?>"><?php echo htmlspecialchars($tournament['name']); ?></a>
                        <?php if ($tournament['status'] === 'in_progress'): ?>
                            <span class="badge bg-warning">In Corso</span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted"><?php echo htmlspecialchars($tournament['date']); ?></p>
                    
                    <?php
                    $participants = $tournament['participants'];
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
                    $top_players = array_slice($participants, 0, 3);
                    ?>
                    <ol>
                        <?php foreach ($top_players as $index => $player): ?>
                            <li>
                                <img src="<?php echo $user_map[$player['userId']]['avatar']; ?>" alt="User Avatar" class="user-avatar-small me-2">
                                <a href="view_profile.php?uid=<?php echo $player['userId']; ?>"><?php echo htmlspecialchars($user_map[$player['userId']]['username'] ?? 'Sconosciuto'); ?></a>
                                <?php if (!empty($player['decklist_name'])): ?>
                                    - <a href="view_decklist.php?tid=<?php echo $tournament['id']; ?>&uid=<?php echo $player['userId']; ?>"><?php echo htmlspecialchars($player['decklist_name']); ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endforeach; ?>
        </section>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&name=<?php echo urlencode($search_name); ?>&filter=<?php echo urlencode($filter_type); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
