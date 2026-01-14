const express = require('express');
const router = express.Router();
const DataManager = require('../dataManager');
const TournamentLogic = require('../tournamentLogic');

// Helper to determine if a user participated in a league
// A user participates if they participated in AT LEAST ONE of the tournaments in the league's stops
const isParticipant = (league, userId) => {
    if (!league.stops || league.stops.length === 0) return false;
    const allTournaments = DataManager.getTournaments();
    return league.stops.some(stopId => {
        const t = allTournaments.find(tour => tour.id === stopId);
        return t && t.participants && t.participants.some(p => p.userId === userId);
    });
};

/* 
    Calculate League Standings
    Rules: 3 pts for Win (Tournament Score > ? No, "3 pt per vittoria e 1 pt per pareggio" usually refers to matches within tournament.
    BUT user request says: "Al termina del torneo, verrà registrato il punteggio alla lega... assegnà 3 punti per ogni vittoria e 1 punto per ogni pareggio."
    So League Score = Sum of (Matches Won * 3 + Matches Drawn * 1) across all stops.
    It doesn't seem to depend on Tournament Rank (like 1st place gets 10pts). It depends on match results.
*/
const calculateLeagueStandings = (league) => {
    const standings = {}; // userId -> { score, games_won, games_lost, participations }
    const allTournaments = DataManager.getTournaments();
    const stops = league.stops.map(id => allTournaments.find(t => t.id === id)).filter(Boolean);

    stops.forEach(t => {
        if (!t.participants) return;
        t.participants.forEach(p => {
            if (!standings[p.userId]) {
                standings[p.userId] = { userId: p.userId, score: 0, games_won: 0, games_lost: 0, participations: 0 };
            }
            // Add Tournament Score directly? 
            // Tournament Score is usually calculated as 3pts per win, 1pt draw.
            // So we can just sum the tournament score of the player.
            // Is it that simple? "somma di tutti i punteggi ottenuti da un giocatore nelle varie leghe" -> "varie tappe".
            // Yes, user said: "La classifica... assegnà 3 punti per ogni vittoria e 1 punto per ogni pareggio"
            // This is exactly how 'p.score' is calculated in the tournament itself.
            standings[p.userId].score += (p.score || 0);
            standings[p.userId].games_won += (p.games_won || 0);
            standings[p.userId].games_lost += (p.games_lost || 0);
            standings[p.userId].participations += 1; // Track how many stops they played
        });
    });

    return Object.values(standings).sort((a, b) => {
        if (b.score !== a.score) return b.score - a.score;
        // Tie-breakers? Games won?
        return b.games_won - a.games_won;
    });
};

// List Public Leagues
router.get('/', (req, res) => {
    const leagues = DataManager.getLeagues() || [];
    const userId = req.session.user ? req.session.user.id : null;

    // Process leagues to add metadata (am I participating?)
    const processedLeagues = leagues.map(l => {
        return {
            ...l,
            isMyLeague: userId ? isParticipant(l, userId) : false,
            stopsCount: l.stops.length
        };
    });

    res.render('leagues/list', { leagues: processedLeagues, user: req.session.user });
});

// View Public League Detail
router.get('/:id', (req, res) => {
    const leagues = DataManager.getLeagues() || [];
    const league = leagues.find(l => l.id == req.params.id);
    if (!league) return res.status(404).send('Lega non trovata');

    const allTournaments = DataManager.getTournaments();
    const stopsEvents = league.stops.map(id => {
        const t = allTournaments.find(tour => tour.id === id);
        if (!t) return null;

        // Calculate Top 3 for this stop
        const sorted = [...(t.participants || [])].sort((a, b) => a.rank - b.rank); // Assuming rank is already calc
        const top3 = sorted.slice(0, 3);

        return { ...t, top3 };
    }).filter(Boolean);

    // Filter next stops (not completed)
    // Actually user says: "visualizzare la pagina dei tornei delle tappe successive"
    // We can show upcoming tournaments
    const nextStops = stopsEvents.filter(t => t.status !== 'completed'); // created or in_progress?
    // Or maybe tournaments linked but not started?

    // User: "Prossima tappa non ancora disponibile" if empty.

    const standings = calculateLeagueStandings(league);

    const usersMap = {};
    const users = DataManager.getUsers();
    users.forEach(u => usersMap[u.id] = u);

    res.render('leagues/view', {
        league,
        standings,
        stops: stopsEvents,
        nextStops,
        usersMap,
        TournamentLogic // For rank string helper if needed
    });
});

module.exports = router;
