<?php
session_start();
require_once 'helpers.php';

// Solo gli admin possono accedere
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

// User data retrieval for header
$avatar_path = 'img/default_avatar.png';
$logged_in_username = null;
if (isset($_SESSION['user_id'])) {
    $users_data = read_json('data/users.json');
    $current_user = find_user_by_id($users_data, $_SESSION['user_id']);
    if ($current_user) {
        $avatar_path = !empty($current_user['avatar']) && file_exists($current_user['avatar']) 
            ? $current_user['avatar'] 
            : 'img/default_avatar.png';
    }
    $logged_in_username = $_SESSION['username'];
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Utente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <a href="home.php" class="site-brand">Gestione Tornei</a>
            <nav class="main-nav">
                <a href="home.php">Home</a>
                <a href="all_tournaments.php">Vedi tutti i tornei</a>
            </nav>
            <div class="user-menu">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>?t=<?php echo time(); ?>" alt="User Avatar" class="user-avatar me-2">
                        <span class="username"><?php echo htmlspecialchars($logged_in_username); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
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
            </div>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Crea Nuovo Utente</h2>
            <form action="api/admin_actions.php" method="POST" class="modern-form">
                <input type="hidden" name="action" value="create_user">

                <div class="form-group mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-group mb-3">
                    <label for="role" class="form-label">Ruolo:</label>
                    <select class="form-control" id="role" name="role">
                        <option value="player" selected>Player</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn-modern">Crea Utente</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
