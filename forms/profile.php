<?php
// Questo file viene incluso in settings.php
$users = read_json(__DIR__ . '/../data/users.json');
$current_user = find_user_by_id($users, $_SESSION['user_id']);
$tournaments = read_json(__DIR__ . '/../data/tournaments.json');
?>

<h2>Modifica Profilo</h2>

<?php if (isset($_SESSION['feedback'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['feedback']);
        unset($_SESSION['feedback']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['error']);
        unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<form action="/api/user_actions.php" method="POST" enctype="multipart/form-data" class="modern-form">
    <input type="hidden" name="action" value="update_profile">

    <fieldset>
        <legend>Immagine del Profilo</legend>
        <div class="form-group">
            <label for="avatar">Carica un nuovo avatar:</label>
            <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*">
            <small class="form-text text-muted">Formati supportati: JPG, PNG, GIF, WebP. Massimo 5MB.</small>
        </div>
    </fieldset>

    <fieldset>
        <legend>Informazioni Personali</legend>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username"
                value="<?php echo htmlspecialchars($current_user['username']); ?>" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>"
                class="form-control" required>
        </div>
    </fieldset>

    <fieldset>
        <legend>Cambia Password (opzionale)</legend>
        <div class="form-group">
            <label for="current_password">Password Attuale:</label>
            <input type="password" id="current_password" name="current_password" class="form-control">
        </div>
        <div class="form-group">
            <label for="new_password">Nuova Password:</label>
            <input type="password" id="new_password" name="new_password" class="form-control">
        </div>
        <div class="form-group">
            <label for="confirm_new_password">Conferma Nuova Password:</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control">
        </div>
    </fieldset>

    <button type="submit" class="btn-modern">Salva Modifiche</button>
</form>

<hr class="my-5">

<h3>Storico Tornei</h3>
<table class="standings-table">
    <thead>
        <tr>
            <th>Torneo</th>
            <th>Classifica</th>
            <th>Risultato (V-S-P)</th>
            <th>Mazzo</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Trova i tornei a cui l'utente ha partecipato
        $participated_tournaments = [];
        foreach ($tournaments as $tournament) {
            $found_participant = null;
            foreach ($tournament['participants'] as $participant) {
                if ($participant['userId'] == $_SESSION['user_id']) {
                    $found_participant = $participant;
                    break;
                }
            }

            if ($found_participant) {
                // Calcola W-L-D
                $wins = 0;
                $losses = 0;
                $draws = 0;
                if (isset($tournament['matches']) && is_array($tournament['matches'])) {
                    foreach ($tournament['matches'] as $round) {
                        foreach ($round as $match) {
                            if ($match['player1'] == $_SESSION['user_id'] || $match['player2'] == $_SESSION['user_id']) {
                                if ($match['winner'] === 'draw')
                                    $draws++;
                                elseif ($match['winner'] == $_SESSION['user_id'])
                                    $wins++;
                                elseif ($match['winner'] !== null)
                                    $losses++;
                            }
                        }
                    }
                }

                // Calcola la classifica
                $sorted_participants = $tournament['participants'];
                usort($sorted_participants, function ($a, $b) {
                    $a_score = $a['score'] ?? 0;
                    $b_score = $b['score'] ?? 0;
                    if ($a_score !== $b_score)
                        return $b_score - $a_score;
                    $a_gwp = ($a['games_won'] + $a['games_lost'] > 0) ? $a['games_won'] / ($a['games_won'] + $a['games_lost']) : 0;
                    $b_gwp = ($b['games_won'] + $b['games_lost'] > 0) ? $b['games_won'] / ($b['games_won'] + $b['games_lost']) : 0;
                    if (abs($a_gwp - $b_gwp) > 0.0001)
                        return $b_gwp > $a_gwp ? 1 : -1;
                    $a_malus = $a['malus'] ?? 0;
                    $b_malus = $b['malus'] ?? 0;
                    if ($a_malus !== $b_malus)
                        return $a_malus - $b_malus;
                    return rand(-1, 1);
                });
                $rank = array_search($_SESSION['user_id'], array_column($sorted_participants, 'userId')) + 1;

                $participated_tournaments[] = [
                    'tournament_id' => $tournament['id'],
                    'tournament_name' => $tournament['name'],
                    'rank' => $rank,
                    'wld' => "$wins-$losses-$draws",
                    'decklist_name' => $found_participant['decklist_name'] ?? 'N/D',
                    'decklist_format' => $found_participant['decklist_format'] ?? 'N/D',
                ];
            }
        }
        ?>
        <?php if (empty($participated_tournaments)): ?>
            <tr>
                <td colspan="4">Non hai partecipato a nessun torneo.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($participated_tournaments as $participation): ?>
                <tr>
                    <td><a
                            href="/views/view_tournament.php?tid=<?php echo $participation['tournament_id']; ?>"><?php echo htmlspecialchars($participation['tournament_name']); ?></a>
                    </td>
                    <td><?php echo $participation['rank']; ?></td>
                    <td><?php echo htmlspecialchars($participation['wld']); ?></td>
                    <td>
                        <?php if ($participation['decklist_name'] !== 'N/D'): ?>
                            <a
                                href="/views/view_decklist.php?tid=<?php echo $participation['tournament_id']; ?>&uid=<?php echo $_SESSION['user_id']; ?>">
                                <?php echo htmlspecialchars($participation['decklist_name']); ?>
                            </a>
                            <small class="text-muted">(<?php echo htmlspecialchars($participation['decklist_format']); ?>)</small>
                        <?php else: ?>
                            N/D
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>