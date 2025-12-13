<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php'; // Include il file delle funzioni helper

// L'utente deve essere loggato per accedere a questa pagina
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Determina quale pagina caricare
$page = $_GET['page'] ?? 'profile'; // Pagina di default
$page_title = 'Modifica Profilo';

$users = read_json(__DIR__ . '/../data/users.json');
$tournaments = read_json(__DIR__ . '/../data/tournaments.json'); // Carica i dati dei tornei
$current_user = find_user_by_id($users, $_SESSION['user_id']);
$avatar_path = !empty($current_user['avatar'])
    ? '/' . $current_user['avatar']
    : '/data/avatars/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - <?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/modern_style.css">
    <link rel="stylesheet" href="/assets/css/modern_admin_style.css">
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
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>?t=<?php echo time(); ?>" alt="User Avatar"
                            class="user-avatar me-2">
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
            </div>
        </div>
    </header>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="/forms/settings.php?page=profile"
                            class="<?php echo $page === 'profile' ? 'active' : ''; ?>">Profilo</a></li>
                    <!-- Aggiungere qui altri link per le impostazioni future -->
                </ul>
            </nav>
        </aside>
        <main class="admin-content">
            <?php
            // Carica la pagina richiesta
            if ($page === 'profile') {
                if (file_exists(__DIR__ . '/profile.php')) {
                    include __DIR__ . '/profile.php';
                } else {
                    echo '<h2>Pagina Profilo in costruzione</h2>';
                }
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
    <script src="/assets/js/main.js"></script>
</body>

</html>