<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Test Upload Avatar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        button {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <h1>🧪 Test Upload Avatar</h1>

    <?php
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<div class="alert alert-success">';
        echo '<h3>✅ Form Ricevuto!</h3>';
        echo '<p><strong>POST data:</strong></p><pre>';
        print_r($_POST);
        echo '</pre>';
        echo '<p><strong>FILES data:</strong></p><pre>';
        print_r($_FILES);
        echo '</pre>';
        echo '</div>';

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            echo '<div class="alert alert-success">';
            echo '<p>✅ File ricevuto correttamente!</p>';
            echo '<p>Nome: ' . htmlspecialchars($_FILES['avatar']['name']) . '</p>';
            echo '<p>Tipo: ' . htmlspecialchars($_FILES['avatar']['type']) . '</p>';
            echo '<p>Dimensione: ' . number_format($_FILES['avatar']['size'] / 1024, 2) . ' KB</p>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger">';
            echo '<p>❌ Nessun file ricevuto o errore upload</p>';
            if (isset($_FILES['avatar'])) {
                echo '<p>Error code: ' . $_FILES['avatar']['error'] . '</p>';
            }
            echo '</div>';
        }
    }
    ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username (test):</label>
            <input type="text" id="username" name="username" value="test_user" required>
        </div>

        <div class="form-group">
            <label for="avatar">Carica Avatar:</label>
            <input type="file" id="avatar" name="avatar" accept="image/*" required>
        </div>

        <button type="submit">🚀 Invia Test</button>
    </form>

    <hr style="margin: 30px 0;">
    <p><a href="/forms/settings.php?page=profile">← Torna alle Impostazioni</a></p>
</body>

</html>