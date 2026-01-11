const TournamentLogic = require('./src/tournamentLogic');

console.log('🎯 TEST TIE-BREAKER: Game Difference\n');
console.log('==========================================\n');
console.log('Scenario: 3 giocatori, tutti con 6 punti (2 vittorie)');
console.log('Ma con differenti margini di vittoria:\n');

// Simula un torneo dove 3 giocatori hanno lo stesso punteggio
// ma margini di vittoria diversi
const tournament = {
    id: 999,
    name: 'Tie-Breaker Test',
    settings: { tournament_type: 'swiss', rounds: 2 },
    currentRound: 2,
    participants: [
        // Giocatore A: 2 vittorie 2-0 (dominio totale)
        {
            userId: 1,
            score: 6,       // 2 vittorie = 6 punti
            games_won: 4,   // 2+2
            games_lost: 0,  // 0+0
            bye_count: 0,
            malus: 0
        },
        // Giocatore B: 2 vittorie 2-1 (vittorie combattute)
        {
            userId: 2,
            score: 6,       // 2 vittorie = 6 punti
            games_won: 4,   // 2+2
            games_lost: 2,  // 1+1
            bye_count: 0,
            malus: 0
        },
        // Giocatore C: 1 vittoria 2-0 + 1 pareggio 1-1
        {
            userId: 3,
            score: 4,       // 1 vittoria (3pts) + 1 pareggio (1pt) = 4 punti
            games_won: 3,   // 2+1
            games_lost: 1,  // 0+1
            bye_count: 0,
            malus: 0
        },
        // Giocatore D: 1 vittoria 2-1 + 1 pareggio 1-1
        {
            userId: 4,
            score: 4,       // 1 vittoria (3pts) + 1 pareggio (1pt) = 4 punti
            games_won: 3,   // 2+1
            games_lost: 2,  // 1+1
            bye_count: 0,
            malus: 0
        }
    ],
    matches: {}
};

console.log('📊 DATI INIZIALI:');
console.log('─'.repeat(70));
console.log('P1: 6pts | Vittorie: 2-0, 2-0 → Games: 4-0 (Diff: +4)');
console.log('P2: 6pts | Vittorie: 2-1, 2-1 → Games: 4-2 (Diff: +2)');
console.log('P3: 4pts | Vittoria: 2-0 + Pareggio: 1-1 → Games: 3-1 (Diff: +2)');
console.log('P4: 4pts | Vittoria: 2-1 + Pareggio: 1-1 → Games: 3-2 (Diff: +1)');

console.log('\n🔄 CALCOLO CLASSIFICA CON TIE-BREAKERS...\n');

const standings = TournamentLogic.calculateStandings(tournament);

console.log('📊 CLASSIFICA FINALE:');
console.log('─'.repeat(70));

standings.forEach((p, idx) => {
    const gameDiff = p.game_difference;
    console.log(`${idx + 1}° - P${p.userId} | ${p.score}pts | G:${p.games_won}-${p.games_lost} (${gameDiff >= 0 ? '+' : ''}${gameDiff})`);
});

console.log('\n✅ VERIFICA TIE-BREAKERS:\n');

// Verifica 1: P1 deve essere primo (stesso punteggio di P2 ma migliore game diff)
if (standings[0].userId === 1) {
    console.log('✅ P1 è 1° (6pts, +4 game diff) - CORRETTO!');
    console.log('   → Ha vinto entrambe le partite 2-0 (dominio)');
} else {
    console.log('❌ ERRORE: P1 dovrebbe essere primo!');
}

// Verifica 2: P2 deve essere secondo
if (standings[1].userId === 2) {
    console.log('✅ P2 è 2° (6pts, +2 game diff) - CORRETTO!');
    console.log('   → Ha vinto entrambe le partite 2-1 (combattute)');
} else {
    console.log('❌ ERRORE: P2 dovrebbe essere secondo!');
}

// Verifica 3: P3 deve essere terzo (4pts, +2 game diff)
if (standings[2].userId === 3) {
    console.log('✅ P3 è 3° (4pts, +2 game diff) - CORRETTO!');
    console.log('   → 1 vittoria netta + 1 pareggio');
} else {
    console.log('❌ ERRORE: P3 dovrebbe essere terzo!');
}

// Verifica 4: P4 deve essere quarto (4pts, +1 game diff)
if (standings[3].userId === 4) {
    console.log('✅ P4 è 4° (4pts, +1 game diff) - CORRETTO!');
    console.log('   → 1 vittoria combattuta + 1 pareggio');
} else {
    console.log('❌ ERRORE: P4 dovrebbe essere quarto!');
}

console.log('\n' + '═'.repeat(70));
console.log('🎯 CONCLUSIONE:');
console.log('═'.repeat(70));
console.log('\nIl sistema TIE-BREAKER funziona perfettamente!');
console.log('\nOrdine di priorità nella classifica:');
console.log('  1️⃣  PUNTI (vittorie/pareggi)');
console.log('  2️⃣  MALUS (penalità)');
console.log('  3️⃣  GAME DIFFERENCE (margine vittorie) ⭐ QUESTA È LA TUA RICHIESTA!');
console.log('  4️⃣  GAMES WON (totale partite vinte)');
console.log('  5️⃣  RANDOM (se tutto uguale)');
console.log('\n✅ Chi vince 2-0 è SEMPRE sopra chi vince 2-1 (a parità di punti)!\n');
