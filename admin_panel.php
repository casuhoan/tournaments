<?php
session_start();
require_once 'helpers.php'; // Include il file delle funzioni helper

// Solo gli admin possono accedere a questa pagina
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

// Determina quale pagina caricare
$page = $_GET['page'] ?? 'tournaments'; // Pagina di default
$page_title = 'Amministrazione Tornei';
if ($page === 'users') {
    $page_title = 'Gestione Utenti';
} elseif ($page === 'decklists') {
    $page_title = 'Gestione Liste';
}

$users = read_json('data/users.json');
$current_user = find_user_by_id($users, $_SESSION['user_id']);
$avatar_path = !empty($current_user['avatar']) && file_exists($current_user['avatar']) 
    ? $current_user['avatar'] 
    : 'img/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - <?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
    <link rel="stylesheet" href="css/modern_admin_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1>Pannello di Amministrazione</h1>
            <div class="d-flex align-items-center">
                <a href="home.php" class="btn btn-light me-3">Torna alla Home</a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>" alt="User Avatar" class="user-avatar me-2">
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="admin_panel.php?page=tournaments" class="<?php echo $page === 'tournaments' ? 'active' : ''; ?>">Gestione Tornei</a></li>
                    <li><a href="admin_panel.php?page=users" class="<?php echo $page === 'users' ? 'active' : ''; ?>">Gestione Utenti</a></li>
                    <li><a href="admin_panel.php?page=decklists" class="<?php echo $page === 'decklists' ? 'active' : ''; ?>">Gestione Liste</a></li>
                </ul>
            </nav>
        </aside>
        <main class="admin-content">
            <?php
            // Carica la pagina richiesta
            if ($page === 'tournaments') {
                include 'admin_tournaments.php';
            } elseif ($page === 'users') {
                include 'admin_users.php';
            } elseif ($page === 'decklists') {
                include 'admin_decklists.php';
            } else {
                echo '<p>Pagina non trovata.</p>';
            }
            ?>
        </main>
    </div>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
