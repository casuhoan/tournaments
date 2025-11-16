<?php
session_start();
// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Leggi gli utenti dal file JSON
    $users_file = 'data/users.json';
    $users = [];
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true);
    }

    $authenticated = false;
    foreach ($users as $user) {
        // In un'applicazione reale, la password dovrebbe essere hashata e verificata con password_verify()
        // Permetti il login sia con email che con username
        if (($user['email'] === $email || $user['username'] === $email) && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Aggiungi il ruolo alla sessione
            $authenticated = true;
            header('Location: home.php');
            exit();
        }
    }

    if (!$authenticated) {
        $error_message = 'Email o password non validi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestione Tornei</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <h1>Login</h1>
    </header>

    <main class="modern-main">
        <section id="login-form" class="card">
            <h2>Accedi al tuo account</h2>
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form action="login.php" method="POST" class="modern-form">
                <div class="form-group">
                    <label for="email">Email o Username:</label>
                    <input type="text" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-modern">Accedi</button>
            </form>
            <p>Non hai un account? <a href="register.php">Registrati qui</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
