<?php
// Questo file viene incluso in admin_panel.php, quindi la sessione è già avviata
// e i permessi sono già stati controllati.

$users = read_json('data/users.json');

?>

<h2>Gestione Utenti</h2>

<p>
    <a href="#" class="button">Crea Nuovo Utente</a>
</p>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Avatar</th>
            <th>Username</th>
            <th>Email</th>
            <th>Ruolo</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="6">Nessun utente trovato.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <?php
                    $avatar = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'img/default_avatar.png';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><img src="<?php echo $avatar; ?>?t=<?php echo time(); ?>" alt="Avatar" class="player-avatar"></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role'] ?? 'player'); ?></td>
                    <td class="actions">
                        <a href="#" class="action-edit">Modifica</a>
                        <a href="api/admin_actions.php?action=delete_user&id=<?php echo $user['id']; ?>" 
                           class="action-delete"
                           onclick="return confirm('Sei sicuro di voler eliminare questo utente?');">
                           Elimina
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
