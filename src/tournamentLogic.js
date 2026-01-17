const DataManager = require('./dataManager');

const TournamentLogic = {
    // Constants
    POINTS: {
        WIN: 3,
        DRAW: 1,
        LOSS: 0
    },

    // Update scores based on match results
    updateScores: (tournament) => {
        if (!tournament.matches) return;

        // Reset scores first
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
                        if (p1) { p1.games_won += parseInt(m.score1); p1.games_lost += parseInt(m.score2); }
                        if (p2) { p2.games_won += parseInt(m.score2); p2.games_lost += parseInt(m.score1); }
                    }
                }
            });
        });
    },

    // Calculate standings for a tournament
    calculateStandings: (tournament) => {
        let participants = tournament.participants.map(p => {
            const gameDiff = (p.games_won || 0) - (p.games_lost || 0);
            return {
                ...p,
                score: p.score || 0,
                games_won: p.games_won || 0,
                games_lost: p.games_lost || 0,
                game_difference: gameDiff,
                malus: p.malus || 0,
                bye_count: p.bye_count || 0
            };
        });

        participants.sort((a, b) => {
            // 1. Points
            if (b.score !== a.score) return b.score - a.score;
            // 2. Malus (lower is better)
            if (a.malus !== b.malus) return a.malus - b.malus;
            // 3. Game Difference
            if (b.game_difference !== a.game_difference) return b.game_difference - a.game_difference;
            // 4. Games Won
            if (b.games_won !== a.games_won) return b.games_won - a.games_won;
            // 5. Random (using userId as seed for consistency)
            return Math.sign(Math.sin(a.userId) - Math.sin(b.userId));
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

        // Calculate standings FIRST (before BYE selection)
        if (nextRound === 1) {
            participants.sort(() => Math.random() - 0.5);
        } else {
            participants = TournamentLogic.calculateStandings({ ...tournament, participants });
        }

        let newPairings = [];
        let byePlayer = null;

        if (participants.length % 2 !== 0) {
            // STRICT RULE: BYE can only be given ONCE per player in the entire tournament
            const eligibleForBye = participants.filter(p => (p.bye_count || 0) === 0);

            if (eligibleForBye.length === 0) {
                // Edge case: All players already have a BYE
                console.error('WARNING: All players have already received a BYE. Cannot continue with odd number.');
                return null;
            }

            // Among eligible players, select the LAST one (lowest in standings)
            // eligibleForBye is already sorted by calculateStandings above
            // Find the lowest-ranked among eligible
            let lowestRank = -1;
            let lowestPlayer = null;
            eligibleForBye.forEach(p => {
                if (p.rank > lowestRank) {
                    lowestRank = p.rank;
                    lowestPlayer = p;
                }
            });
            byePlayer = lowestPlayer;

            // Remove BYE player from participants pool for pairing
            // IMPORTANT: Keep the rest in their current sorted order!
            participants = participants.filter(p => p.userId !== byePlayer.userId);
        }

        const solve = (pool, allowRepeats = false) => {
            if (pool.length === 0) return [];
            if (pool.length === 1) return null; // Can't pair a single player

            let p1 = pool[0];
            for (let i = 1; i < pool.length; i++) {
                let p2 = pool[i];
                // Check repeats only if strict
                if (allowRepeats || !history[p1.userId].has(p2.userId)) {
                    let remaining = [...pool];
                    remaining.splice(i, 1);
                    remaining.shift();
                    let result = solve(remaining, allowRepeats);
                    if (result !== null) {
                        return [{ player1: p1, player2: p2, table: 0 }, ...result];
                    }
                }
            }
            return null;
        };

        let pairings = solve(participants, false);

        // FALLBACK: If strict pairing fails with small pool (e.g., 2 players who already played),
        // allow one repeat match to continue tournament
        if (pairings === null && participants.length === 2) {
            console.log("Small pool - allowing repeat match to continue tournament");
            pairings = solve(participants, true);
        }

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
            // Increment BYE count for tracking
            const byeParticipant = tournament.participants.find(p => p.userId === byePlayer.userId);
            if (byeParticipant) {
                byeParticipant.bye_count = (byeParticipant.bye_count || 0) + 1;
            }

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
