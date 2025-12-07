<?php
// Questo file viene incluso in admin_panel.php

$tournaments = read_json('data/tournaments.json');
$users = read_json('data/users.json');
$user_map = [];
$avatar_map = [];
foreach ($users as $user) {
    $user_map[$user['id']] = $user['username'];
    $avatar_map[$user['id']] = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'img/default_avatar.png';
}

$unnamed_decklists = [];
foreach ($tournaments as $tournament) {
    foreach ($tournament['participants'] as $participant) {
        // Aggiungi alla lista se la decklist è presente ma non ha un nome
        if (!empty($participant['decklist']) && empty($participant['decklist_name'])) {
            $unnamed_decklists[] = [
                'tournament_id' => $tournament['id'],
                'tournament_name' => $tournament['name'],
                'user_id' => $participant['userId'],
                'player_name' => $user_map[$participant['userId']] ?? 'Sconosciuto',
                'decklist' => $participant['decklist']
            ];
        }
    }
}

?>

<h2>Gestione Liste da Categorizzare</h2>

<table class="admin-table">
    <thead>
        <tr>
            <th>Torneo</th>
            <th>Giocatore</th>
            <th>Lista</th>
            <th>Azione</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($unnamed_decklists)): ?>
            <tr>
                <td colspan="4">Nessuna nuova lista da categorizzare.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($unnamed_decklists as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['tournament_name']); ?></td>
                    <td class="player-cell">
                        <img src="<?php echo $avatar_map[$item['user_id']] ?? 'img/default_avatar.png'; ?>?t=<?php echo time(); ?>" alt="Avatar" class="player-avatar">
                        <span><?php echo htmlspecialchars($item['player_name']); ?></span>
                    </td>
                    <td><pre><?php echo htmlspecialchars(substr($item['decklist'], 0, 100)); ?>...</pre></td>
                    <td class="actions">
                        <a href="../forms/edit_decklist.php?tid=<?php echo $item['tournament_id']; ?>&uid=<?php echo $item['user_id']; ?>" class="action-edit">
                           Categorizza
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
