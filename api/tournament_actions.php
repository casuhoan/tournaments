<?php
session_start();
require_once '../helpers.php'; // Include il file delle funzioni helper

function find_tournament_by_id(&$tournaments, $id) {
    foreach ($tournaments as $key => &$tournament) {
        if ($tournament['id'] == $id) {
            return $key;
        }
    }
    return null;
}

// --- Logica Principale ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Metodo non supportato.');
}

if (!isset($_SESSION['user_id'])) {
    die('Accesso negato. Devi essere loggato.');
}

$action = $_POST['action'] ?? '';
$tournament_id = $_POST['tournament_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($action) || empty($tournament_id)) {
    die('Azione o ID torneo non specificato.');
}

$tournaments_file = '../data/tournaments.json';
$tournaments = read_json($tournaments_file);

$tournament_key = find_tournament_by_id($tournaments, $tournament_id);

if ($tournament_key === null) {
    die('Torneo non trovato.');
}

$tournament = &$tournaments[$tournament_key];
$redirect_link = $tournament['link'];

switch ($action) {
    case 'join_tournament':
        $is_registered = false;
        foreach ($tournament['participants'] as $participant) {
            if ($participant['userId'] === $user_id) {
                $is_registered = true;
                break;
            }
        }
        if (!$is_registered) {
            $tournament['participants'][] = [
                'userId' => $user_id,
                'decklist' => '',
                'decklist_name' => '',
                'decklist_format' => '',
                'score' => 0,
                'malus' => 0,
                'games_won' => 0,
                'games_lost' => 0,
                'played_opponents' => [] // Inizializza l'array degli avversari già affrontati
            ];
        }
        break;

    case 'leave_tournament':
        $tournament['participants'] = array_filter($tournament['participants'], function($participant) use ($user_id) {
            return $participant['userId'] !== $user_id;
        });
        break;

    case 'submit_decklist':
        $decklist = $_POST['decklist'] ?? '';
        foreach ($tournament['participants'] as &$participant) {
            if ($participant['userId'] === $user_id) {
                $participant['decklist'] = $decklist;
                break;
            }
        }
        break;

    case 'start_tournament':
        // Solo l'organizzatore può avviare il torneo
        if ($tournament['organizerId'] === $user_id && $tournament['status'] === 'pending' && count($tournament['participants']) >= 2) {
            $tournament['status'] = 'in_progress';
            
            // Logica di abbinamento casuale per il primo turno
            $participants = $tournament['participants'];
            shuffle($participants);
            
            $round = 1;
            $matches = [];
            for ($i = 0; $i < count($participants); $i += 2) {
                if (isset($participants[$i + 1])) { // C'è un avversario
                    $matches[] = [
                        'round' => $round,
                        'player1' => $participants[$i]['userId'],
                        'player2' => $participants[$i + 1]['userId'],
                        'score1' => null,
                        'score2' => null,
                        'winner' => null,
                        'table' => ($i / 2) + 1
                    ];
                } else { // Giocatore dispari, riceve un BYE
                    // Aggiorna il punteggio e i game vinti del giocatore
                    foreach ($tournament['participants'] as &$p) {
                        if ($p['userId'] === $participants[$i]['userId']) {
                            $p['score'] += 3;
                            $p['games_won'] += 1;
                            break;
                        }
                    }
                    // Aggiunge un match fittizio per il BYE
                    $matches[] = [
                        'round' => $round,
                        'player1' => $participants[$i]['userId'],
                        'player2' => 'BYE',
                        'score1' => 1,
                        'score2' => 0,
                        'winner' => $participants[$i]['userId'],
                        'table' => 'BYE'
                    ];
                }
            }
            $tournament['matches']['round_1'] = $matches;
        }
        break;

    case 'start_next_round':
        // Verifica che sia l'organizzatore
        if ($tournament['organizerId'] !== $user_id || $tournament['status'] !== 'in_progress') {
            die('Azione non permessa.');
        }

        $current_round_key = !empty($tournament['matches']) ? array_key_last($tournament['matches']) : null;
        
        // Verifica che tutti i match del round corrente siano conclusi
        $all_matches_done = true;
        if ($current_round_key) {
            foreach($tournament['matches'][$current_round_key] as $match) {
                if ($match['winner'] === null) {
                    $all_matches_done = false;
                    break;
                }
            }
        } else {
            $all_matches_done = false; // Nessun round ancora giocato
        }

        if ($all_matches_done) {
            $next_round_num = (int)str_replace('round_', '', $current_round_key) + 1;
            $next_round_key = 'round_' . $next_round_num;
            $matches = [];

            // Logica per Torneo alla Svizzera
            if ($tournament['settings']['tournament_type'] === 'swiss') {
                if ($next_round_num > $tournament['settings']['rounds']) {
                    $tournament['status'] = 'completed'; // Superato il numero di round
                } else {
                    $participants = $tournament['participants'];
                    // Ordina i partecipanti secondo le nuove regole di spareggio
                    usort($available_participants, function($a, $b) {
                        // 1. Punteggio (decrescente)
                        if ($a['score'] !== $b['score']) {
                            return $b['score'] - $a['score'];
                        }
                        
                        // 2. Game Win Percentage (decrescente)
                        $gwp_a = ($a['games_won'] + $a['games_lost'] > 0) ? $a['games_won'] / ($a['games_won'] + $a['games_lost']) : 0;
                        $gwp_b = ($b['games_won'] + $b['games_lost'] > 0) ? $b['games_won'] / ($b['games_won'] + $b['games_lost']) : 0;
                        if ($gwp_a !== $gwp_b) {
                            return $gwp_b > $gwp_a ? 1 : -1;
                        }

                        // 3. Malus (crescente)
                        if ($a['malus'] !== $b['malus']) {
                            return $a['malus'] - $b['malus'];
                        }

                        // 4. Casuale
                        return rand(-1, 1);
                    });

                    $paired_user_ids = [];
                    $table_num = 1;

                    foreach ($available_participants as $p1_key => $p1) {
                        if (in_array($p1['userId'], $paired_user_ids)) continue;

                        $p2_found = false;
                        foreach ($available_participants as $p2_key => $p2) {
                            if (in_array($p2['userId'], $paired_user_ids)) continue;
                            if ($p1['userId'] === $p2['userId']) continue; // Non può giocare contro se stesso

                            // Verifica se hanno già giocato
                            if (!in_array($p2['userId'], $p1['played_opponents'])) {
                                // Trovato un avversario che non ha ancora giocato
                                $matches[] = ['round' => $next_round_num, 'player1' => $p1['userId'], 'player2' => $p2['userId'], 'score1' => null, 'score2' => null, 'winner' => null, 'table' => $table_num++];
                                $paired_user_ids[] = $p1['userId'];
                                $paired_user_ids[] = $p2['userId'];
                                $p2_found = true;
                                break;
                            }
                        }

                        // Se non è stato trovato un avversario che non ha giocato, accoppia con il primo disponibile
                        if (!$p2_found) {
                            foreach ($available_participants as $p2_key => $p2) {
                                if (in_array($p2['userId'], $paired_user_ids)) continue;
                                if ($p1['userId'] === $p2['userId']) continue;

                                $matches[] = ['round' => $next_round_num, 'player1' => $p1['userId'], 'player2' => $p2['userId'], 'score1' => null, 'score2' => null, 'winner' => null, 'table' => $table_num++];
                                $paired_user_ids[] = $p1['userId'];
                                $paired_user_ids[] = $p2['userId'];
                                $p2_found = true;
                                break;
                            }
                        }
                    }

                    // Gestione del BYE se c'è un numero dispari di partecipanti non accoppiati
                    $unpaired_participants = array_filter($available_participants, function($p) use ($paired_user_ids) {
                        return !in_array($p['userId'], $paired_user_ids);
                    });

                                        if (!empty($unpaired_participants)) {

                                            $bye_player = reset($unpaired_participants); // Prende il primo non accoppiato

                                            foreach ($tournament['participants'] as &$p) {

                                                if ($p['userId'] === $bye_player['userId']) {

                                                    $p['score'] += 3; // Punti per il BYE

                                                    $p['games_won'] += 1;

                                                    break;

                                                }

                                            }

                                            // Aggiunge un match fittizio per il BYE

                                            $matches[] = [

                                                'round' => $next_round_num,

                                                'player1' => $bye_player['userId'],

                                                'player2' => 'BYE',

                                                'score1' => 1,

                                                "score2" => 0,

                                                'winner' => $bye_player['userId'],

                                                'table' => 'BYE'

                                            ];

                                        }

                                        

                                        // Se non sono stati generati match ma ci sono ancora partecipanti, il torneo termina

                                        if (empty($matches) && count($available_participants) > 1) {

                                            $tournament['status'] = 'completed';

                                        } else {

                                            $tournament['matches'][$next_round_key] = $matches;

                                            if ($next_round_num == $tournament['settings']['rounds']) { $tournament['status'] = 'completed'; }

                                        }

                                    }

                                }
            // Logica per Torneo a Eliminazione Diretta
            elseif ($tournament['settings']['tournament_type'] === 'elimination') {
                $winners = [];
                foreach ($tournament['matches'][$current_round_key] as $match) {
                    if ($match['winner'] !== 'draw') {
                        $winners[] = $match['winner'];
                    }
                }
                
                if (count($winners) === 1) {
                    $tournament['status'] = 'completed'; // Abbiamo un vincitore finale
                } else {
                    shuffle($winners);
                    for ($i = 0; $i < count($winners); $i += 2) {
                        if (isset($winners[$i + 1])) {
                            $matches[] = ['round' => $next_round_num, 'player1' => $winners[$i], 'player2' => $winners[$i + 1], 'score1' => null, 'score2' => null, 'winner' => null, 'table' => ($i / 2) + 1];
                        } else {
                            // Il giocatore dispari passa il turno (bye), non serve fare nulla, verrà incluso nel prossimo pool di vincitori
                        }
                    }
                    $tournament['matches'][$next_round_key] = $matches;
                }
            }
        }
        break;

    case 'submit_result':
        $round_key = $_POST['round_key'] ?? null;
        $p1_id = $_POST['player1_id'] ?? null;
        $p2_id = $_POST['player2_id'] ?? null;
        $score1 = (int)($_POST['score1'] ?? 0);
        $score2 = (int)($_POST['score2'] ?? 0);
        $is_draw = isset($_POST['is_draw']);

        if (!$round_key || !$p1_id || !$p2_id) {
            die('Dati del match mancanti.');
        }

        // Trova il match specifico
        $match_key = null;
        foreach ($tournament['matches'][$round_key] as $key => $match) {
            if ($match['player1'] == $p1_id && $match['player2'] == $p2_id) {
                $match_key = $key;
                break;
            }
        }

        if ($match_key !== null && $tournament['matches'][$round_key][$match_key]['winner'] === null) {
            $match = &$tournament['matches'][$round_key][$match_key];
            $match['score1'] = $score1;
            $match['score2'] = $score2;

            $winner_id = null;
            if ($is_draw) {
                $match['winner'] = 'draw';
            } elseif ($score1 > $score2) {
                $winner_id = $p1_id;
                $match['winner'] = $p1_id;
            } else {
                $winner_id = $p2_id;
                $match['winner'] = $p2_id;
            }

            // Aggiorna i punteggi generali dei partecipanti e gli avversari giocati
            foreach ($tournament['participants'] as &$participant) {
                if ($participant['userId'] == $p1_id) {
                    if ($is_draw) {
                        $participant['score'] += 1;
                    } elseif ($winner_id == $p1_id) {
                        $participant['score'] += 3;
                    }
                    $participant['games_won'] += $score1;
                    $participant['games_lost'] += $score2;
                    $participant['played_opponents'][] = $p2_id;
                } elseif ($participant['userId'] == $p2_id) {
                    if ($is_draw) {
                        $participant['score'] += 1;
                    } elseif ($winner_id == $p2_id) {
                        $participant['score'] += 3;
                    }
                    $participant['games_won'] += $score2;
                    $participant['games_lost'] += $score1;
                    $participant['played_opponents'][] = $p1_id;
                }
            }

            // Controlla se il torneo a eliminazione è terminato
            if ($tournament['settings']['tournament_type'] === 'elimination') {
                $losses = [];
                foreach ($tournament['participants'] as $p) {
                    $losses[$p['userId']] = 0;
                }
                foreach ($tournament['matches'] as $round_matches) {
                    foreach ($round_matches as $m) {
                        if ($m['winner'] !== null && $m['winner'] !== 'draw') {
                            $loser = ($m['winner'] == $m['player1']) ? $m['player2'] : $m['player1'];
                            if (isset($losses[$loser])) {
                                $losses[$loser]++;
                            }
                        }
                    }
                }
                $active_players = 0;
                foreach ($losses as $loss_count) {
                    if ($loss_count === 0) {
                        $active_players++;
                    }
                }
                if ($active_players <= 1 && count($tournament['participants']) > 1) {
                    $tournament['status'] = 'completed';
                }
            }
        }
        break;

    default:
        die('Azione non valida.');
}

write_json($tournaments_file, $tournaments);

// Reindirizza l'utente alla pagina del torneo
header('Location: ../tournament.php?link=' . $redirect_link);
exit();
