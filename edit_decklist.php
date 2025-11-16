<?php
session_start();
require_once 'helpers.php';

// Solo gli admin possono accedere
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accesso negato.');
}

$tournament_id = $_GET['tid'] ?? null;
$user_id = $_GET['uid'] ?? null;

if (!$tournament_id || !$user_id) {
    die('ID torneo o utente mancanti.');
}

$tournaments = read_json('data/tournaments.json');
$decklist_data = null;

// Trova la decklist specifica
foreach ($tournaments as $tournament) {
    if ($tournament['id'] == $tournament_id) {
        foreach ($tournament['participants'] as $participant) {
            if ($participant['userId'] == $user_id) {
                $decklist_data = $participant;
                break 2;
            }
        }
    }
}

if ($decklist_data === null) {
    die('Dati della lista non trovati.');
}

$formats = ['Pauper', 'Pioneer', 'Standard', 'Modern', 'Commander'];

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorizza Lista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <div class="header-content">
            <h1>Categorizza Lista</h1>
            <a href="admin_panel.php?page=decklists" class="btn-modern">Torna a Gestione Liste</a>
        </div>
    </header>

    <main class="modern-main">
        <section class="card">
            <h2>Modifica Dettagli Lista</h2>
            <form action="api/admin_actions.php" method="POST" class="modern-form">
                <input type="hidden" name="action" value="update_decklist">
                <input type="hidden" name="tournament_id" value="<?php echo htmlspecialchars($tournament_id); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                <div class="form-group mb-3">
                    <label for="decklist_name" class="form-label">Nome Mazzo:</label>
                    <input type="text" class="form-control" id="decklist_name" name="decklist_name" required>
                </div>

                <div class="form-group mb-3">
                    <label for="decklist_format" class="form-label">Formato:</label>
                    <select class="form-select" id="decklist_format" name="decklist_format">
                        <?php foreach ($formats as $format): ?>
                            <option value="<?php echo $format; ?>"><?php echo $format; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Lista:</label>
                    <textarea class="form-control" rows="15" readonly><?php echo htmlspecialchars($decklist_data['decklist']); ?></textarea>
                </div>

                <button type="submit" class="btn-modern">Salva Categoria</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
