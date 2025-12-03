<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Richiesta non valida. Riprova.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Don't sanitize password

        // Rate limiting check
        if (check_rate_limit($email)) {
            $error_message = 'Troppi tentativi di accesso. Riprova tra 15 minuti.';
        } else {
            // Leggi gli utenti dal file JSON
            $users_file = __DIR__ . '/../data/users.json';
            $users = [];
            if (file_exists($users_file)) {
                $users = json_decode(file_get_contents($users_file), true);
            }

            $authenticated = false;
            foreach ($users as $user) {
                // Permetti il login sia con email che con username
                if ($user['email'] === $email || $user['username'] === $email) {
                    // Check if password is hashed or plaintext (for migration)
                    if (password_get_info($user['password'])['algo'] !== null) {
                        // Password is hashed, use password_verify
                        if (verify_password($password, $user['password'])) {
                            $authenticated = true;
                        }
                    } else {
                        // Legacy plaintext password (for migration)
                        if ($user['password'] === $password) {
                            $authenticated = true;
                        }
                    }

                    if ($authenticated) {
                        // Regenerate session ID for security
                        regenerate_session();

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];

                        // Reset rate limit on successful login
                        reset_rate_limit($email);

                        header('Location: home.php');
                        exit();
                    }
                    break;
                }
            }

            if (!$authenticated) {
                $error_message = 'Email o password non validi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestione Tornei</title>
    <link rel="stylesheet" href="../assets/css/premium_design.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern_style.css">
</head>

<body>
    <header class="modern-header">
        <h1>Login</h1>
    </header>

    <main class="modern-main">
        <section id="login-form" class="card">
            <h2>Accedi al tuo account</h2>
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST" class="modern-form">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email" class="form-label">Email o Username:</label>
                    <input type="text" id="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Accedi</button>
            </form>
            <p class="mt-3">Non hai un account? <a href="register.php">Registrati qui</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <script src="../assets/js/theme-toggle.js"></script>
</body>

</html>