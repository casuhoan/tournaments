<?php
session_start();
// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validazione di base
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error_message = 'Tutti i campi sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Formato email non valido.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Le password non corrispondono.';
    } else {
        $users_file = 'data/users.json';
        $users = [];
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true);
        }

        // Controlla se l'email o l'username esistono già
        $email_exists = false;
        $username_exists = false;
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $email_exists = true;
                break;
            }
            if ($user['username'] === $username) {
                $username_exists = true;
                break;
            }
        }

        if ($email_exists) {
            $error_message = 'Questa email è già registrata.';
        } elseif ($username_exists) {
            $error_message = 'Questo username è già in uso.';
        } else {
            // Genera un nuovo ID utente
            $new_user_id = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;

            $new_user = [
                'id' => $new_user_id,
                'username' => $username,
                'email' => $email,
                'password' => $password, // In un'applicazione reale, hashare la password!
                'role' => 'player', // Assegna il ruolo di default
                'avatar' => 'img/default_avatar.png', // Campo avatar di default (piccolo)
                'avatar_large' => 'img/default_avatar_large.png' // Campo avatar di default (grande)
            ];

            $users[] = $new_user;
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

            $success_message = 'Registrazione avvenuta con successo! Ora puoi accedere.';
            // Potresti reindirizzare l'utente alla pagina di login
            // header('Location: login.php');
            // exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Gestione Tornei</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modern_style.css">
</head>
<body>
    <header class="modern-header">
        <h1>Registrazione</h1>
    </header>

    <main class="modern-main">
        <section id="register-form" class="card">
            <h2>Crea un nuovo account</h2>
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <form action="register.php" method="POST" class="modern-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Conferma Password:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit" class="btn-modern">Registrati</button>
            </form>
            <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>
</body>
</html>
