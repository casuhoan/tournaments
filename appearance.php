<?php
// Questo file viene incluso in settings.php, quindi la sessione è già avviata
// e l'utente è loggato. Le funzioni helper sono già incluse.

$current_user = get_current_user();

?>

<h2>Impostazioni Aspetto</h2>

<?php if (isset($_SESSION['feedback'])): ?>
    <p class="success-message"><?php echo $_SESSION['feedback']; unset($_SESSION['feedback']); ?></p>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <p class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>

<form action="api/user_actions.php" method="post" class="modern-form">
    <input type="hidden" name="action" value="update_appearance">
    <div class="form-group">
        <label for="theme">Tema del Sito:</label>
        <select name="theme" id="theme">
            <option value="light" <?php echo ($current_user['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Chiaro</option>
            <option value="dark" <?php echo ($current_user['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Scuro</option>
        </select>
    </div>
    <button type="submit" class="btn-modern">Salva Modifiche</button>
</form>