<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /home.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Richiesta non valida. Riprova.';
    } else {
        // Sanitize inputs
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Don't sanitize password
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validazione di base
        if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
            $error_message = 'Tutti i campi sono obbligatori.';
        } elseif (!validate_email($email)) {
            $error_message = 'Formato email non valido.';
        } elseif (!validate_username($username)) {
            $error_message = 'Username non valido. Usa solo lettere, numeri, trattini e underscore (3-20 caratteri).';
        } elseif ($password !== $password_confirm) {
            $error_message = 'Le password non corrispondono.';
        } elseif (!validate_password_strength($password)) {
            $error_message = 'La password deve contenere almeno 8 caratteri, una lettera e un numero.';
        } else {
            $users_file = __DIR__ . '/../data/users.json';
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
                    'password' => hash_password($password), // Hash password with bcrypt
                    'role' => 'player', // Assegna il ruolo di default
                    'avatar' => '/data/avatars/default_avatar.png', // Updated path
                    'theme' => 'light' // Default theme preference
                ];

                $users[] = $new_user;
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

                $success_message = 'Registrazione avvenuta con successo! Ora puoi accedere.';
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
    <title>Registrazione - Gestione Tornei</title>
    <link rel="stylesheet" href="/assets/css/premium_design.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/modern_style.css">
</head>

<body>
    <header class="modern-header">
        <h1>Registrazione</h1>
    </header>

    <main class="modern-main">
        <section id="register-form" class="card">
            <h2>Crea un nuovo account</h2>
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form action="/register.php" method="POST" class="modern-form">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <small class="text-muted">Minimo 8 caratteri, almeno una lettera e un numero</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Conferma Password:</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrati</button>
            </form>
            <p class="mt-3">Hai già un account? <a href="/login.php">Accedi qui</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Gestione Tornei</p>
    </footer>

    <script src="/assets/js/theme-toggle.js"></script>
</body>

</html>