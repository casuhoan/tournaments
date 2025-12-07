<?php
// Questo file viene incluso in admin_panel.php, quindi la sessione è già avviata
// e i permessi sono già stati controllati.

$tournaments = read_json('data/tournaments.json');

// Ordina i tornei per data, dal più recente al più vecchio
usort($tournaments, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<h2>Amministrazione Tornei</h2>

<?php
if (isset($_SESSION['feedback'])): ?>
    <p class="success-message"><?php echo $_SESSION['feedback']; unset($_SESSION['feedback']); ?></p>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome Torneo</th>
            <th>Data</th>
            <th>Stato</th>
            <th>Partecipanti</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tournaments)): ?>
            <tr>
                <td colspan="6">Nessun torneo trovato.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($tournaments as $tournament): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tournament['id']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['name']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['date']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['status'] ?? 'N/A'); ?></td>
                    <td><?php echo count($tournament['participants']); ?></td>
                    <td class="actions">
                        <a href="/forms/edit_tournament.php?id=<?php echo $tournament['id']; ?>" class="action-edit">Modifica</a>
                        <a href="api/admin_actions.php?action=delete_tournament&id=<?php echo $tournament['id']; ?>" 
                           class="action-delete" 
                           onclick="return confirm('Sei sicuro di voler eliminare questo torneo? L\'azione è irreversibile.');">
                           Elimina
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
