<?php
session_start();
require_once 'helpers.php'; // Include il file delle funzioni helper

// Se l'utente non è loggato, reindirizza alla pagina di login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user = get_current_user();
if (!is_array($current_user)) {
    // Potrebbe succedere se l'utente viene eliminato ma la sessione è ancora attiva
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$logged_in_username = $current_user['username'];
$avatar_path = $current_user['avatar'];

// Gestione del logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset(); // Rimuove tutte le variabili di sessione
    session_destroy(); // Distrugge la sessione
    header('Location: index.php'); // Reindirizza alla pagina principale
    exit();
}

// Leggi i dati dei tornei per la dashboard
$tournaments = read_json('data/tournaments.json');

// Filtra i tornei a cui l'utente loggato ha partecipato (per ora, solo un esempio)
$user_tournaments = array_filter($tournaments, function($tournament) use ($logged_in_username) {
    foreach ($tournament['participants'] as $participant) {
        // Per questo esempio, assumiamo che il nome utente sia direttamente nel partecipante
        // In futuro, useremo l'ID utente e la user_map
        $users = read_json('data/users.json');
        $user_map = [];
        foreach ($users as $user) {
            $user_map[$user['id']] = $user['username'];
        }
        if (isset($user_map[$participant['userId']]) && $user_map[$participant['userId']] === $logged_in_username) {
            return true;
        }
    }
    return false;
});

$total_tournaments = count($tournaments);
$completed_tournaments = count($tournaments); // Per ora, tutti i tornei sono considerati completati
$user_total_tournaments = count($user_tournaments);
$user_completed_tournaments = count($user_tournaments); // Per ora, tutti i tornei dell'utente sono considerati completati
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Gestione Tornei</title>
    <!-- Bootstrap CSS -->
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
                        <a class="nav-link active" aria-current="page" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="all_tournaments.php">Vedi tutti i tornei</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $avatar_path; ?>" alt="User Avatar" class="user-avatar me-2">
                        <span class="username"><?php echo htmlspecialchars($logged_in_username); ?></span>
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
            </div>
        </div>
    </nav>

    <main class="modern-main">
        <section id="dashboard-actions" class="card">
            <h2>Azioni Rapide</h2>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator'): ?>
                <a href="create_tournament.php" class="btn-modern me-2">Crea Nuovo Torneo</a>
            <?php endif; ?>
            <a href="all_tournaments.php" class="btn-modern btn-modern-secondary">Vedi Tutti i Tornei</a>
        </section>

        <section id="summary" class="card">
            <h2>Riepilogo Generale</h2>
            <div class="summary-boxes">
                <div class="summary-box">
                    <h3><a href="all_tournaments.php?filter=in_progress_participating">Tornei in corso (a cui partecipo)</a></h3>
                    <p><?php echo count(array_filter($tournaments, fn($t) => $t['status'] === 'in_progress' && in_array($_SESSION['user_id'], array_column($t['participants'], 'userId')))); ?></p>
                </div>
                <div class="summary-box">
                    <h3><a href="all_tournaments.php?filter=completed_participating">Tornei completati (che hai fatto)</a></h3>
                    <p><?php echo count(array_filter($tournaments, fn($t) => $t['status'] === 'completed' && in_array($_SESSION['user_id'], array_column($t['participants'], 'userId')))); ?></p>
                </div>
            </div>
        </section>

        <section id="user-tournaments" class="card">
            <h2>I tuoi tornei</h2>
            <div class="tournament-list">
                <?php
                $user_tournaments = array_filter($tournaments, function($t) {
                    return in_array($_SESSION['user_id'], array_column($t['participants'], 'userId'));
                });
                // Ordina per data più recente
                usort($user_tournaments, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                $latest_tournaments = array_slice($user_tournaments, 0, 10);
                ?>
                <?php if (empty($latest_tournaments)): ?>
                    <p>Non sei iscritto a nessun torneo.</p>
                <?php else: ?>
                    <?php foreach ($latest_tournaments as $tournament): ?>
                        <div class="tournament-card">
                            <h3>
                                <a href="tournament.php?link=<?php echo $tournament['link']; ?>"><?php echo htmlspecialchars($tournament['name']); ?></a>
                                <?php if ($tournament['status'] === 'in_progress'): ?>
                                    <span class="badge bg-warning">In Corso</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-muted"><?php echo htmlspecialchars($tournament['date']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (count($user_tournaments) > 10): ?>
            <div class="text-center mt-3">
                <a href="all_tournaments.php?filter=completed_participating" class="btn btn-secondary">Visualizza altri</a>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
