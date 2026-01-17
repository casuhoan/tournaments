const express = require('express');
const router = express.Router();
const DataManager = require('../dataManager');
const TournamentLogic = require('../tournamentLogic');
const { isAdmin } = require('../middleware/authMiddleware');

const getUserMap = () => {
    const users = DataManager.getUsers();
    let map = {};
    users.forEach(u => map[u.id] = u);
    return map;
};



// Routes
router.use(isAdmin); // Apply isAdmin to all routes in this file

router.get('/', (req, res) => {
    res.render('admin/dashboard');
});

router.get('/tournaments', (req, res) => {
    const tournaments = DataManager.getTournaments();
    res.render('admin/tournaments', { tournaments });
});

router.post('/tournaments/:id/delete', (req, res) => {
    let tournaments = DataManager.getTournaments();
    tournaments = tournaments.filter(t => t.id != req.params.id);
    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/tournaments');
});

// CREATE TOURNAMENT UI
router.get('/tournaments/new', (req, res) => {
    res.render('admin/tournament_create');
});

// PROCESS CREATION
router.post('/tournaments/create', (req, res) => {
    const { name, date, format, type, rounds, decklist_mandatory, decklist_public } = req.body;
    let tournaments = DataManager.getTournaments();

    // Generate ID
    const maxId = tournaments.reduce((max, t) => Math.max(max, parseInt(t.id)), 100);
    const newId = maxId + 1;

    const newTournament = {
        id: newId, // Save as Number to match existing data
        name: name,
        date: date,
        status: 'created', // created, in_progress, completed
        organizerId: req.session.user.id,
        currentRound: 0,
        participants: [],
        matches: {},
        settings: {
            format: format, // bo1, bo3, bo3_draw
            tournament_type: type, // swiss, elimination
            rounds: parseInt(rounds) || 3,
            decklist_mandatory: decklist_mandatory === 'true',
            decklist_public: decklist_public === 'true'
        }
    };

    tournaments.push(newTournament);
    DataManager.saveTournaments(tournaments);

    // Redirect to the new tournament page using the direct link logic
    res.render('admin/tournament_created_success', { tournament: newTournament, host: req.get('host') });
});

// EDIT TOURNAMENT UI
router.get('/tournaments/:id', (req, res) => {
    const tournament = DataManager.getTournamentById(req.params.id);
    if (!tournament) return res.status(404).render('error', { message: 'Tournament not found' });
    res.render('admin/tournament_detail', { tournament });
});

// UPDATE TOURNAMENT
router.post('/tournaments/:id/update', (req, res) => {
    const { name, date, format, type, rounds, decklist_mandatory, decklist_public } = req.body;
    let tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);

    if (tIndex === -1) return res.status(404).send('Tournament not found');

    tournaments[tIndex].name = name;
    tournaments[tIndex].date = date;
    tournaments[tIndex].settings = {
        ...tournaments[tIndex].settings,
        format,
        tournament_type: type,
        rounds: parseInt(rounds),
        decklist_mandatory: decklist_mandatory === 'true',
        decklist_public: decklist_public === 'true'
    };

    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/tournaments/' + req.params.id);
});

router.get('/tournaments/:id/matches', (req, res) => {
    const tournament = DataManager.getTournamentById(req.params.id);
    if (!tournament) return res.status(404).send('Tournament not found');
    const usersMap = getUserMap();
    res.render('admin/tournament_matches', { tournament, usersMap });
});

router.post('/matches/update', (req, res) => {
    const { tournamentId, roundKey, matchIndex, score1, score2 } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == tournamentId);
    if (tIndex === -1) return res.status(404).send('Tournament not found');

    let tournament = tournaments[tIndex];
    if (!tournament.matches[roundKey] || !tournament.matches[roundKey][matchIndex]) return res.status(404).send('Match not found');

    let match = tournament.matches[roundKey][matchIndex];
    match.score1 = parseInt(score1);
    match.score2 = parseInt(score2);

    if (match.score1 > match.score2) match.winner = String(match.player1);
    else if (match.score2 > match.score1) match.winner = String(match.player2);
    else match.winner = 'draw';

    TournamentLogic.updateScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament);

    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/tournaments/' + tournament.id + '/matches');
});

router.get('/users', (req, res) => {
    const users = DataManager.getUsers();
    res.render('admin/users', { users });
});

router.post('/users/:id/delete', (req, res) => {
    let users = DataManager.getUsers();
    users = users.filter(u => u.id != req.params.id);
    DataManager.saveUsers(users);
    res.redirect('/admin/users');
});

router.get('/users/:id/edit', (req, res) => {
    const users = DataManager.getUsers();
    const targetUser = users.find(u => u.id == req.params.id);
    if (!targetUser) return res.status(404).send('User not found');
    res.render('admin/user_edit', { targetUser, error: null });
});

router.post('/users/:id/edit', (req, res) => {
    const { username, email, role, password } = req.body;
    const users = DataManager.getUsers();
    const uIndex = users.findIndex(u => u.id == req.params.id);
    if (uIndex === -1) return res.status(404).send('User not found');

    const existing = users.find(u => (u.email === email || u.username === username) && u.id != req.params.id);
    if (existing) {
        const targetUser = users[uIndex];
        return res.render('admin/user_edit', { targetUser, error: "Username or Email already taken" });
    }

    users[uIndex].username = username;
    users[uIndex].email = email;
    users[uIndex].role = role;
    if (password && password.trim() !== '') users[uIndex].password = password;

    DataManager.saveUsers(users);
    res.redirect('/admin/users');
});

router.get('/decklists', (req, res) => {
    const tournaments = DataManager.getTournaments();
    const usersMap = getUserMap();
    res.render('admin/decklists', { tournaments, usersMap });
});

router.post('/decklists/categorize', (req, res) => {
    const { tournamentId, userId, deckName, format } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == tournamentId);
    if (tIndex === -1) return res.status(404).send('Not found');
    const pIndex = tournaments[tIndex].participants.findIndex(p => p.userId == userId);
    if (pIndex === -1) return res.status(404).send('Not found');
    tournaments[tIndex].participants[pIndex].decklist_name = deckName;
    tournaments[tIndex].participants[pIndex].decklist_format = format;
    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/decklists');
});

// Update player penalty (malus)
router.post('/tournaments/:id/penalty', (req, res) => {
    const { userId, malus } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);

    if (tIndex === -1) return res.status(404).send('Tournament not found');

    const participant = tournaments[tIndex].participants.find(p => p.userId == userId);
    if (!participant) return res.status(404).send('Participant not found');

    participant.malus = parseInt(malus) || 0;

    // Recalculate standings after penalty change
    tournaments[tIndex].participants = TournamentLogic.calculateStandings(tournaments[tIndex]);

    DataManager.saveTournaments(tournaments);
    res.redirect(`/admin/tournaments/${req.params.id}/matches`);
});



// LEAGUES MANAGEMENT

// List Leagues & Create Form
router.get('/leagues', isAdmin, (req, res) => {
    const leagues = DataManager.getLeagues() || [];
    res.render('admin/leagues', { leagues });
});

// Create League
router.post('/leagues', isAdmin, (req, res) => {
    const { name, max_stops } = req.body;
    let leagues = DataManager.getLeagues() || [];

    const newLeague = {
        id: Date.now().toString(),
        name,
        max_stops: parseInt(max_stops) || 5,
        status: 'active', // active, completed
        stops: [], // Array of tournament IDs
        created_at: new Date().toISOString()
    };

    leagues.push(newLeague);
    DataManager.saveLeagues(leagues);
    res.redirect('/admin/leagues');
});

// League Detail & Manage Stops
router.get('/leagues/:id', isAdmin, (req, res) => {
    const leagues = DataManager.getLeagues();
    const league = leagues.find(l => l.id == req.params.id);
    if (!league) return res.redirect('/admin/leagues');

    // Get all tournaments that are NOT already in this league (or any league? User didn't specify, but usually unique)
    // For now, allow any "created" tournament to be added as a stop.
    // User requirement: "selezionando uno dei tornei tra quelli non ancora iniziati"
    const allTournaments = DataManager.getTournaments();
    const availableTournaments = allTournaments.filter(t =>
        t.status === 'created' &&
        !league.stops.includes(t.id)
        // Optional: Check if t is already in another league? Not strictly required but good practice.
    );

    // Get details of stops
    const stopDetails = league.stops.map(stopId => {
        return allTournaments.find(t => t.id == stopId) || { id: stopId, name: 'Unknown/Deleted', status: 'unknown' };
    });

    res.render('admin/league_detail', { league, availableTournaments, stopDetails });
});

// Add Stop to League
router.post('/leagues/:id/stops', isAdmin, (req, res) => {
    const { tournament_id } = req.body;
    let leagues = DataManager.getLeagues();
    let league = leagues.find(l => l.id == req.params.id);

    if (league && tournament_id) {
        if (!league.stops.includes(tournament_id)) {
            league.stops.push(tournament_id);
            // Optionally, we could tag the tournament object with leagueId if needed for easier lookup
            DataManager.saveLeagues(leagues);
        }
    }
    res.redirect('/admin/leagues/' + req.params.id);
});

module.exports = router;
