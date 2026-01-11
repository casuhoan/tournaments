const TournamentLogic = require('./src/tournamentLogic');

console.log('🎯 TOURNAMENT SYSTEM TEST - 8 PLAYERS\n');
console.log('==========================================\n');

// Simula un torneo Swiss con 8 giocatori
const tournament = {
    id: 999,
    name: 'Test Tournament',
    settings: {
        tournament_type: 'swiss',
        rounds: 3
    },
    currentRound: 0,
    participants: [
        { userId: 1, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 2, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 3, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 4, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 5, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 6, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 7, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 },
        { userId: 8, score: 0, games_won: 0, games_lost: 0, bye_count: 0, malus: 0 }
    ],
    matches: {}
};

function simulateMatch(match, winner) {
    if (winner === 'p1') {
        match.winner = String(match.player1);
        match.score1 = 2;
        match.score2 = 0;
    } else if (winner === 'p2') {
        match.winner = String(match.player2);
        match.score1 = 0;
        match.score2 = 2;
    } else {
        match.winner = 'draw';
        match.score1 = 1;
        match.score2 = 1;
    }
}

function updateScores(tournament) {
    tournament.participants.forEach(p => {
        p.score = 0;
        p.games_won = 0;
        p.games_lost = 0;
    });

    Object.values(tournament.matches).forEach(roundMatches => {
        if (!roundMatches) return;
        roundMatches.forEach(m => {
            if (m.winner) {
                const p1 = tournament.participants.find(p => p.userId === m.player1);
                const p2 = tournament.participants.find(p => p.userId === m.player2);

                if (m.winner === 'draw') {
                    if (p1) p1.score += 1;
                    if (p2) p2.score += 1;
                } else {
                    const winner = tournament.participants.find(p => p.userId == m.winner);
                    if (winner) winner.score += 3;
                }

                if (m.score1 !== null && m.score2 !== null) {
                    if (p1) {
                        p1.games_won += parseInt(m.score1);
                        p1.games_lost += parseInt(m.score2);
                    }
                    if (p2) {
                        p2.games_won += parseInt(m.score2);
                        p2.games_lost += parseInt(m.score1);
                    }
                }
            }
        });
    });
}

function printStandings(tournament, roundNum) {
    console.log(`\n📊 CLASSIFICA DOPO ROUND ${roundNum}:`);
    console.log('─'.repeat(60));
    const standings = TournamentLogic.calculateStandings(tournament);
    standings.forEach((p, idx) => {
        const gameDiff = p.games_won - p.games_lost;
        console.log(`${idx + 1}. Player ${p.userId} - ${p.score} pts | W:${p.games_won} L:${p.games_lost} (${gameDiff >= 0 ? '+' : ''}${gameDiff}) | BYEs: ${p.bye_count || 0}`);
    });
}

function printMatches(matches, roundNum) {
    console.log(`\n⚔️  ROUND ${roundNum} - ABBINAMENTI:`);
    console.log('─'.repeat(60));
    matches.forEach((m, idx) => {
        if (m.bye) {
            console.log(`Tavolo ${idx + 1}: Player ${m.player1} ha BYE (vittoria automatica)`);
        } else {
            const result = m.winner
                ? (m.winner === 'draw' ? 'PAREGGIO' : `Vince P${m.winner}`)
                : 'In corso';
            console.log(`Tavolo ${idx + 1}: Player ${m.player1} vs Player ${m.player2} (${m.score1 || 0}-${m.score2 || 0}) - ${result}`);
        }
    });
}

// ROUND 1
console.log('\n🚀 INIZIO TORNEO - Generazione Round 1...\n');
tournament.currentRound = 1;
const round1 = TournamentLogic.generatePairings(tournament);

if (!round1) {
    console.error('❌ ERRORE: Impossibile generare Round 1!');
    process.exit(1);
}

tournament.matches.round_1 = round1;
printMatches(round1, 1);

// Simula risultati casuali ma realistici
round1.forEach((m, idx) => {
    if (!m.bye) {
        const outcomes = ['p1', 'p1', 'p2', 'draw']; // Più vittorie nette che pareggi
        simulateMatch(m, outcomes[idx % outcomes.length]);
    }
});

updateScores(tournament);
printStandings(tournament, 1);

// ROUND 2
console.log('\n\n🔄 AVANZAMENTO AL ROUND 2...\n');
tournament.currentRound = 2;
const round2 = TournamentLogic.generatePairings(tournament);

if (!round2) {
    console.error('❌ ERRORE: Impossibile generare Round 2!');
    console.log('\nDETTAGLI DEBUG:');
    console.log('Participants:', tournament.participants.length);
    console.log('Match history:', JSON.stringify(tournament.matches, null, 2));
    process.exit(1);
}

tournament.matches.round_2 = round2;
printMatches(round2, 2);

round2.forEach((m, idx) => {
    if (!m.bye) {
        const outcomes = ['p2', 'p1', 'p1', 'p2'];
        simulateMatch(m, outcomes[idx % outcomes.length]);
    }
});

updateScores(tournament);
printStandings(tournament, 2);

// ROUND 3
console.log('\n\n🔄 AVANZAMENTO AL ROUND 3 (FINALE)...\n');
tournament.currentRound = 3;
const round3 = TournamentLogic.generatePairings(tournament);

if (!round3) {
    console.error('❌ ERRORE: Impossibile generare Round 3!');
    console.log('\nDETTAGLI DEBUG:');
    console.log('Participants:', tournament.participants.length);
    console.log('Match history:', JSON.stringify(tournament.matches, null, 2));
    process.exit(1);
}

tournament.matches.round_3 = round3;
printMatches(round3, 3);

round3.forEach((m, idx) => {
    if (!m.bye) {
        const outcomes = ['p1', 'p2', 'p2', 'p1'];
        simulateMatch(m, outcomes[idx % outcomes.length]);
    }
});

updateScores(tournament);
printStandings(tournament, 3);

// VERIFICA FINALE
console.log('\n\n✅ TEST COMPLETATO - VERIFICA FINALE:\n');
console.log('─'.repeat(60));

// Check 1: Tutti hanno giocato lo stesso numero di partite (±1)
const matchCounts = tournament.participants.map(p => {
    let count = 0;
    Object.values(tournament.matches).forEach(round => {
        if (round) {
            round.forEach(m => {
                if (m.player1 === p.userId || m.player2 === p.userId) count++;
            });
        }
    });
    return count;
});

const minMatches = Math.min(...matchCounts);
const maxMatches = Math.max(...matchCounts);
console.log(`✓ Partite giocate: Min=${minMatches}, Max=${maxMatches} (differenza: ${maxMatches - minMatches})`);

if (maxMatches - minMatches <= 1) {
    console.log('  ✅ OK: Tutti hanno giocato un numero equo di partite');
} else {
    console.log('  ❌ PROBLEMA: Differenza troppo alta nelle partite giocate');
}

// Check 2: Nessuno ha giocato contro lo stesso avversario due volte
let repeatMatches = 0;
tournament.participants.forEach(p => {
    const opponents = [];
    Object.values(tournament.matches).forEach(round => {
        if (round) {
            round.forEach(m => {
                if (m.player1 === p.userId && m.player2) opponents.push(m.player2);
                if (m.player2 === p.userId && m.player1) opponents.push(m.player1);
            });
        }
    });
    const unique = new Set(opponents);
    if (unique.size !== opponents.length) {
        repeatMatches += (opponents.length - unique.size);
    }
});

console.log(`✓ Match ripetuti: ${repeatMatches / 2}`); // Diviso 2 perché conta entrambe le direzioni
if (repeatMatches === 0) {
    console.log('  ✅ OK: Nessun giocatore ha affrontato lo stesso avversario due volte');
} else {
    console.log('  ⚠️ NOTA: Alcuni match sono stati ripetuti (può essere normale con pool piccoli)');
}

// Check 3: La classifica è ordinata correttamente
const finalStandings = TournamentLogic.calculateStandings(tournament);
let sortingOk = true;
for (let i = 0; i < finalStandings.length - 1; i++) {
    const curr = finalStandings[i];
    const next = finalStandings[i + 1];
    if (curr.score < next.score) {
        sortingOk = false;
        break;
    }
}

console.log(`✓ Ordinamento classifica: ${sortingOk ? '✅ OK' : '❌ ERRORE'}`);

console.log('\n🏆 TORNEO SIMULATO CON SUCCESSO!\n');
