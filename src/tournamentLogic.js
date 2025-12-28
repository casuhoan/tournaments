const DataManager = require('./dataManager');

const TournamentLogic = {
    // Constants
    POINTS: {
        WIN: 3,
        DRAW: 1,
        LOSS: 0
    },

    // Calculate standings for a tournament
    calculateStandings: (tournament) => {
        let participants = tournament.participants.map(p => {
            return {
                ...p,
                score: p.score || 0,
                games_won: p.games_won || 0,
                games_lost: p.games_lost || 0,
                malus: p.malus || 0
            };
        });

        participants.sort((a, b) => {
            if (b.score !== a.score) return b.score - a.score;
            if (a.malus !== b.malus) return a.malus - b.malus;
            if (b.games_won !== a.games_won) return b.games_won - a.games_won;
            if (a.games_lost !== b.games_lost) return a.games_lost - b.games_lost;
            return b.userId - a.userId;
        });

        participants.forEach((p, index) => {
            p.rank = index + 1;
        });

        return participants;
    },

    // Convert numeric rank to English string
    getRankString: (rank) => {
        const j = rank % 10, k = rank % 100;
        if (j == 1 && k != 11) return rank + "st";
        if (j == 2 && k != 12) return rank + "nd";
        if (j == 3 && k != 13) return rank + "rd";
        return rank + "th";
    },

    // Generate Pairings for next round
    generatePairings: (tournament) => {
        let participants = [...tournament.participants];
        let matches = tournament.matches || {};
        let nextRound = (tournament.currentRound || 0) + 1;

        // Elimination Logic
        if (tournament.settings && tournament.settings.tournament_type === 'elimination') {
            let losers = new Set();
            Object.values(matches).forEach(roundMatches => {
                roundMatches.forEach(m => {
                    if (m.winner && m.winner !== 'draw') {
                        const loserId = (m.winner == m.player1) ? m.player2 : m.player1;
                        if (loserId) losers.add(loserId);
                    }
                });
            });
            participants = participants.filter(p => !losers.has(p.userId));
        }

        // Get pairing history to avoid repeats
        let history = {};
        participants.forEach(p => history[p.userId] = new Set(p.played_opponents || []));

        // Also add matches from current match history in DB to history set if not already there
        Object.values(matches).forEach(roundMatches => {
            roundMatches.forEach(m => {
                if (m.player1 && m.player2) {
                    if (history[m.player1]) history[m.player1].add(m.player2);
                    if (history[m.player2]) history[m.player2].add(m.player1);
                }
            });
        });

        if (nextRound === 1) {
            participants.sort(() => Math.random() - 0.5);
        } else {
            participants = TournamentLogic.calculateStandings({ ...tournament, participants });
        }

        let newPairings = [];
        let byePlayer = null;

        if (participants.length % 2 !== 0) {
            byePlayer = participants.pop();
        }

        const solve = (pool) => {
            if (pool.length === 0) return [];
            let p1 = pool[0];
            for (let i = 1; i < pool.length; i++) {
                let p2 = pool[i];
                if (!history[p1.userId].has(p2.userId)) {
                    let remaining = [...pool];
                    remaining.splice(i, 1);
                    remaining.shift();
                    let result = solve(remaining);
                    if (result !== null) {
                        return [{ player1: p1, player2: p2, table: 0 }, ...result];
                    }
                }
            }
            return null;
        };

        let pairings = solve(participants);

        if (pairings === null) {
            return null;
        }

        let roundMatches = pairings.map((pair, idx) => ({
            round: nextRound,
            player1: pair.player1.userId,
            player2: pair.player2.userId,
            score1: null,
            score2: null,
            winner: null,
            table: idx + 1
        }));

        if (byePlayer) {
            roundMatches.push({
                round: nextRound,
                player1: byePlayer.userId,
                player2: null,
                score1: 1,
                score2: 0,
                winner: String(byePlayer.userId),
                table: roundMatches.length + 1,
                bye: true
            });
        }

        return roundMatches;
    }
};

module.exports = TournamentLogic;
