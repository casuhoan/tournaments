<?php
session_start();
require_once 'helpers.php';

// Solo gli admin possono accedere
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    die('ID torneo mancante.');
}

$tournaments = read_json('data/tournaments.json');
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1>Modifica Torneo: <?php echo htmlspecialchars($tournament_data['name']); ?></h1>
            <a href="admin_panel.php?page=tournaments" class="btn-modern">Torna a Gestione Tornei</a>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Modifica Dati Torneo</h2>
            <form action="api/admin_actions.php" method="POST" class="modern-form">
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
</body>
</html>
