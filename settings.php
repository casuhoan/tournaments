<?php
session_start();
require_once 'helpers.php'; // Include il file delle funzioni helper

// L'utente deve essere loggato per accedere a questa pagina
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Determina quale pagina caricare
$page = $_GET['page'] ?? 'profile'; // Pagina di default
$page_title = 'Modifica Profilo';

$current_user = get_current_user();
if (!is_array($current_user)) {
    // Potrebbe succedere se l'utente viene eliminato ma la sessione è ancora attiva
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
$avatar_path = $current_user['avatar'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - <?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
    <link rel="stylesheet" href="css/modern_admin_style.css">
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
                        <a class="nav-link" href="all_tournaments.php">Vedi tutti i tornei</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>" alt="User Avatar" class="user-avatar me-2">
                        <span class="username"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end text-small shadow">
                        <li><a class="dropdown-item" href="view_profile.php?uid=<?php echo $_SESSION['user_id']; ?>">Profilo</a></li>
                        <li><a class="dropdown-item active" aria-current="page" href="settings.php">Impostazioni</a></li>
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
    </nav>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="settings.php?page=profile" class="<?php echo $page === 'profile' ? 'active' : ''; ?>">Profilo</a></li>
                    <li><a href="settings.php?page=appearance" class="<?php echo $page === 'appearance' ? 'active' : ''; ?>">Aspetto</a></li>
                </ul>
            </nav>
        </aside>
        <main class="admin-content">
            <?php
            // Carica la pagina richiesta
            if ($page === 'profile') {
                // Il file profile.php non esiste ancora, lo creiamo subito dopo
                if (file_exists('profile.php')) {
                    include 'profile.php';
                } else {
                    echo '<h2>Pagina Profilo in costruzione</h2>';
                }
            } elseif ($page === 'appearance') {
                if (file_exists('appearance.php')) {
                    include 'appearance.php';
                } else {
                    echo '<h2>Pagina Aspetto in costruzione</h2>';
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
</body>
</html>
