<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Solo gli admin possono accedere
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

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

$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    die('ID torneo mancante.');
}

$tournaments = read_json(__DIR__ . '/../data/tournaments.json');
$tournament_data = null;

// Trova il torneo specifico
foreach ($tournaments as $t) {
    if ($t['id'] == $tournament_id) {
        $tournament_data = $t;
        break;
    }
}

if ($tournament_data === null) {
    die('Dati del torneo non trovati.');
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Torneo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <a href="/home.php" class="site-brand">Gestione Tornei</a>
            <nav class="main-nav">
                <a href="/home.php">Home</a>
                <a href="/views/all_tournaments.php">Vedi tutti i tornei</a>
            </nav>
            <div class="user-menu">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>?t=<?php echo time(); ?>" alt="User Avatar" class="user-avatar me-2">
                        <span class="username me-2"><?php echo htmlspecialchars($logged_in_username); ?></span>
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
            </div>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Modifica Dati Torneo</h2>
            <form action="/api/admin_actions.php" method="POST" class="modern-form">
                <input type="hidden" name="action" value="update_tournament">
                <input type="hidden" name="tournament_id" value="<?php echo htmlspecialchars($tournament_id); ?>">

                <div class="form-group mb-3">
                    <label for="name" class="form-label">Nome Torneo:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($tournament_data['name']); ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="date" class="form-label">Data:</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($tournament_data['date']); ?>" required>
                </div>
                
                <p class="text-muted">Nota: al momento è possibile modificare solo il nome e la data del torneo.</p>

                <button type="submit" class="btn-modern">Salva Modifiche</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
