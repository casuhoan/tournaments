const express = require('express');
const router = express.Router();
const DataManager = require('../dataManager');
const TournamentLogic = require('../tournamentLogic');
const { isLoggedIn } = require('../middleware/authMiddleware');

const getUserMap = () => {
    const users = DataManager.getUsers();
    let map = {};
    users.forEach(u => map[u.id] = u);
    return map;
};

// HELPER: Update Scores (Duplicated, ideally shared)
function updateParticipantsScores(tournament) {
    if (!tournament.matches) return;
    tournament.participants.forEach(p => { p.score = 0; p.games_won = 0; p.games_lost = 0; });
    Object.values(tournament.matches).forEach(roundMatches => {
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
}

// Route for listing tournaments with pagination and filters
router.get('/', (req, res) => {
    let tournaments = DataManager.getTournaments();
    const usersMap = getUserMap();
    const { filter, search, page } = req.query;

    // Filter Logic
    if (filter === 'active') tournaments = tournaments.filter(t => t.status === 'in_progress');
    else if (filter === 'mine_active' && req.session.user) tournaments = tournaments.filter(t => t.status === 'in_progress' && t.participants.some(p => p.userId === req.session.user.id));
    else if (filter === 'mine_completed' && req.session.user) tournaments = tournaments.filter(t => t.status === 'completed' && t.participants.some(p => p.userId === req.session.user.id));
    else if (filter === 'mine' && req.session.user) tournaments = tournaments.filter(t => t.participants.some(p => p.userId === req.session.user.id));

    if (search) tournaments = tournaments.filter(t => t.name.toLowerCase().includes(search.toLowerCase()));

    // Sort by date descending
    tournaments.sort((a, b) => new Date(b.date) - new Date(a.date));

    // Pagination Logic
    const itemsPerPage = 10;
    const currentPage = parseInt(page) || 1;
    const totalPages = Math.ceil(tournaments.length / itemsPerPage);
    const paginatedTournaments = tournaments.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

    res.render('tournaments/list', {
        tournaments: paginatedTournaments,
        usersMap,
        filters: { filter: filter || 'all', search: search || '' },
        currentPage,
        totalPages
    });
});

router.get('/:id', (req, res) => {
    const tournament = DataManager.getTournamentById(req.params.id);
    if (!tournament) return res.status(404).send('Tournament not found');

    const usersMap = getUserMap();
    // Sort logic
    tournament.participants = TournamentLogic.calculateStandings(tournament);

    const currentMatches = tournament.matches ? (tournament.matches[tournament.currentRound ? 'round_' + tournament.currentRound : 'round_1'] || []) : [];
    res.render('tournaments/view', {
        tournament,
        usersMap,
        currentMatches,
        standings: tournament.participants
    });
});

router.post('/:id/join', isLoggedIn, (req, res) => {
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    if (tIndex === -1) return res.status(404).send('Tournament not found');

    let tournament = tournaments[tIndex];
    if (tournament.status !== 'created') return res.status(400).send('Tournament registration closed');

    // Check if already registered
    if (tournament.participants.some(p => p.userId === req.session.user.id)) {
        return res.redirect('/tournaments/' + tournament.id);
    }

    tournament.participants.push({
        userId: req.session.user.id,
        score: 0,
        games_won: 0,
        games_lost: 0,
        rank: 0,
        decklist: '',
        decklist_name: ''
    });

    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/start', isLoggedIn, (req, res) => {
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    if (req.session.user.role !== 'admin' && req.session.user.id !== tournament.organizerId) return res.status(403).send('Unauthorized');

    tournament.participants.forEach(p => { p.score = 0; p.games_won = 0; p.games_lost = 0; });
    tournament.currentRound = 1;
    tournament.status = 'in_progress';
    tournament.matches = {};
    const pairings = TournamentLogic.generatePairings(tournament);
    tournament.matches['round_1'] = pairings;

    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/result', isLoggedIn, (req, res) => {
    // Only updates winner/draw, scores left as null or 0 if not provided
    const { roundKey, matchIndex, result } = req.body; // result: 'p1', 'p2', 'draw'
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];

    if (!tournament.matches[roundKey]) return res.redirect('/tournaments/' + tournament.id);
    let match = tournament.matches[roundKey][matchIndex];

    // Security check: only players involved or admin/organizer
    const canEdit = req.session.user.role === 'admin' || req.session.user.id === tournament.organizerId ||
        req.session.user.id === match.player1 || req.session.user.id === match.player2;
    if (!canEdit) return res.status(403).send("Unauthorized");

    if (result === 'p1') match.winner = String(match.player1);
    else if (result === 'p2') match.winner = String(match.player2);
    else if (result === 'draw') match.winner = 'draw';

    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament); // FIX: Recalculate
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/result_score', isLoggedIn, (req, res) => {
    const { roundKey, matchIndex, score1, score2 } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];

    let match = tournament.matches[roundKey][matchIndex];
    const canEdit = req.session.user.role === 'admin' || req.session.user.id === tournament.organizerId ||
        req.session.user.id === match.player1 || req.session.user.id === match.player2;
    if (!canEdit) return res.status(403).send("Unauthorized");

    match.score1 = parseInt(score1);
    match.score2 = parseInt(score2);

    if (match.score1 > match.score2) match.winner = String(match.player1);
    else if (match.score2 > match.score1) match.winner = String(match.player2);
    else if (match.score1 === match.score2) match.winner = 'draw';

    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament); // FIX: Recalculate
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/next-round', isLoggedIn, (req, res) => {
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    if (req.session.user.role !== 'admin' && req.session.user.id !== tournament.organizerId) return res.status(403).send('Unauthorized');

    // Check if current round complete
    const currentMatches = tournament.matches['round_' + tournament.currentRound];
    if (currentMatches.some(m => !m.winner)) return res.send('Completa prima tutti i match del turno corrente.');

    updateParticipantsScores(tournament);

    tournament.currentRound += 1;
    const newPairings = TournamentLogic.generatePairings(tournament);
    tournament.matches['round_' + tournament.currentRound] = newPairings;

    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/end', isLoggedIn, (req, res) => {
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    if (req.session.user.role !== 'admin' && req.session.user.id !== tournament.organizerId) return res.status(403).send('Unauthorized');

    tournament.status = 'completed';
    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament);
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

router.post('/:id/decklist', isLoggedIn, (req, res) => {
    const { decklist_url } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];

    const pIndex = tournament.participants.findIndex(p => p.userId === req.session.user.id);
    if (pIndex !== -1) {
        tournament.participants[pIndex].decklist = decklist_url;
        DataManager.saveTournaments(tournaments);
    }
    res.redirect('/tournaments/' + tournament.id);
});

module.exports = router;
