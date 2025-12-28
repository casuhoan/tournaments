// Final Server.js with Admin Implementations

const express = require('express');
const session = require('express-session');
const bodyParser = require('body-parser');
const path = require('path');
const multer = require('multer');
const fs = require('fs');

const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        const dir = 'public/uploads/avatars';
        if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
        cb(null, dir)
    },
    filename: function (req, file, cb) {
        cb(null, Date.now() + path.extname(file.originalname)) // Append extension
    }
});
const upload = multer({ storage: storage });
const DataManager = require('./src/dataManager');
const TournamentLogic = require('./src/tournamentLogic');

const app = express();
const PORT = process.env.PORT || 8000;

app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static('public'));
app.use('/data', express.static('data'));
app.use('/img', express.static('public/img'));
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(session({
    secret: 'grandius_tournament_secret',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false }
}));
app.use((req, res, next) => {
    res.locals.user = req.session.user || null;
    res.locals.TournamentLogic = TournamentLogic;
    next();
});

const getUserMap = () => {
    const users = DataManager.getUsers();
    let map = {};
    users.forEach(u => map[u.id] = u);
    return map;
};

// HELPER: Update Scores
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

// ROUTING
app.get('/', (req, res) => {
    const tournaments = DataManager.getTournaments();
    tournaments.sort((a, b) => new Date(b.date) - new Date(a.date));
    const usersMap = getUserMap();
    const recentTournaments = tournaments.slice(0, 3);
    const activeTournaments = tournaments.filter(t => t.status === 'in_progress' || t.status === 'created');
    const completedTournaments = tournaments.filter(t => t.status === 'completed');
    let stats = { totalTournaments: tournaments.length, activeTournaments: activeTournaments.length, completedTournaments: completedTournaments.length };
    res.render('index', { recentTournaments, activeTournaments, completedTournaments, stats, usersMap });
});

app.get('/login', (req, res) => res.render('login', { error: null }));
app.post('/login', (req, res) => {
    const { email, password, remember } = req.body;
    const users = DataManager.getUsers();
    const user = users.find(u => (u.email === email || u.username === email) && u.password === password);
    if (user) {
        req.session.user = { id: user.id, username: user.username, role: user.role, avatar: user.avatar, email: user.email };

        // Remember Me Logic
        if (remember) {
            req.session.cookie.maxAge = 30 * 24 * 60 * 60 * 1000; // 30 days
        } else {
            req.session.cookie.expires = false; // Session cookie (expires on close)
        }

        res.redirect('/');
    } else {
        res.render('login', { error: 'Invalid credentials' });
    }
});
app.get('/logout', (req, res) => { req.session.destroy(); res.redirect('/'); });
app.get('/register', (req, res) => res.render('register', { error: null }));
app.post('/register', (req, res) => {
    const { username, email, password, confirm_password } = req.body;
    if (password !== confirm_password) return res.render('register', { error: 'Passwords do not match' });
    const users = DataManager.getUsers();
    if (users.find(u => u.email === email || u.username === username)) return res.render('register', { error: 'User already exists' });
    const maxId = users.reduce((max, u) => Math.max(max, u.id), 0);
    const newUser = { id: maxId + 1, username, email, password, role: 'player', avatar: 'img/default_avatar.png' };
    users.push(newUser);
    DataManager.saveUsers(users);
    req.session.user = newUser;
    res.redirect('/');
});

app.post('/settings/profile', upload.single('avatar'), (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    const { username, email, password } = req.body;
    // Avatar file path if uploaded
    const avatarPath = req.file ? 'uploads/avatars/' + req.file.filename : null;

    const users = DataManager.getUsers();

    // Find current user index
    const uIndex = users.findIndex(u => u.id === req.session.user.id);
    if (uIndex === -1) return res.render('settings', { user: req.session.user, error: 'User not found' });

    // Validation: Check if username/email already taken by someone else
    const existing = users.find(u => (u.email === email || u.username === username) && u.id !== req.session.user.id);
    if (existing) return res.render('settings', { user: req.session.user, error: 'Username or Email already taken' });

    // Update fields
    users[uIndex].username = username;
    users[uIndex].email = email;
    if (avatarPath) users[uIndex].avatar = avatarPath;
    if (password && password.trim() !== '') users[uIndex].password = password; // Should be hashed in future

    DataManager.saveUsers(users);

    // Update session
    req.session.user = { ...users[uIndex] };

    res.render('settings', { user: req.session.user, success: 'Profile updated successfully' });
});

app.get('/tournaments', (req, res) => {
    let tournaments = DataManager.getTournaments();
    const usersMap = getUserMap();
    const { filter, search, page } = req.query;
    tournaments.sort((a, b) => new Date(b.date) - new Date(a.date));
    if (filter === 'active') tournaments = tournaments.filter(t => t.status === 'in_progress');
    else if (filter === 'mine_active' && req.session.user) tournaments = tournaments.filter(t => t.status === 'in_progress' && t.participants.some(p => p.userId === req.session.user.id));
    else if (filter === 'mine_completed' && req.session.user) tournaments = tournaments.filter(t => t.status === 'completed' && t.participants.some(p => p.userId === req.session.user.id));
    else if (filter === 'mine' && req.session.user) tournaments = tournaments.filter(t => t.participants.some(p => p.userId === req.session.user.id));
    if (search) tournaments = tournaments.filter(t => t.name.toLowerCase().includes(search.toLowerCase()));

    const PAGE_SIZE = 10;
    const pageNum = parseInt(page) || 1;
    const totalPages = Math.ceil(tournaments.length / PAGE_SIZE);
    const pagedTournaments = tournaments.slice((pageNum - 1) * PAGE_SIZE, pageNum * PAGE_SIZE);
    res.render('tournaments/list', { tournaments: pagedTournaments, usersMap, filters: { filter: filter || 'all', search: search || '' }, currentPage: pageNum, totalPages });
});

app.get('/tournaments/:id', (req, res) => {
    const tournament = DataManager.getTournamentById(req.params.id);
    if (!tournament) return res.status(404).send('Tournament not found');
    const usersMap = getUserMap();
    const standings = [...tournament.participants].sort((a, b) => a.rank - b.rank);
    const currentMatches = (tournament.matches && tournament.currentRound) ? (tournament.matches[`round_${tournament.currentRound}`] || []) : [];
    res.render('tournaments/view', { tournament, usersMap, standings, currentMatches });
});
app.post('/tournaments/:id/decklist', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    if (tIndex === -1) return res.status(404).send('Tournament not found');
    const tournament = tournaments[tIndex];
    const pIndex = tournament.participants.findIndex(p => p.userId === req.session.user.id);
    if (pIndex === -1) return res.send('Non sei iscritto.');
    tournament.participants[pIndex].decklist = req.body.decklist;
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});
app.post('/tournaments/:id/start', (req, res) => {
    if (!req.session.user || (req.session.user.role !== 'admin' && req.session.user.role !== 'organizer')) return res.status(403).send('Unauthorized');
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    tournament.status = 'in_progress';
    tournament.currentRound = 0;
    tournament.matches = {};
    const matches = TournamentLogic.generatePairings(tournament);
    if (!matches) return res.send('Impossibile generare abbinamenti.');
    tournament.currentRound = 1;
    tournament.matches['round_1'] = matches;
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});
app.post('/tournaments/:id/result', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    const { matchId, action } = req.body;
    let matchData;
    try { matchData = JSON.parse(matchId); } catch (e) { return res.send("Invalid Match ID"); }
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    const roundKey = `round_${tournament.currentRound}`;
    const mIndex = tournament.matches[roundKey].findIndex(m => m.table === matchData.table);
    if (mIndex === -1) return res.send('Match not found');
    let match = tournament.matches[roundKey][mIndex];
    if (match.player1 !== req.session.user.id && match.player2 !== req.session.user.id && req.session.user.role !== 'admin' && req.session.user.role !== 'organizer') return res.status(403).send('Unauthorized');
    if (action === 'win_self') {
        const isP1 = match.player1 === req.session.user.id;
        match.winner = String(req.session.user.id);
        match.score1 = isP1 ? 2 : 0;
        match.score2 = isP1 ? 0 : 2;
    } else if (action === 'win_opp') {
        const isP1 = match.player1 === req.session.user.id;
        match.winner = String(isP1 ? match.player2 : match.player1);
        match.score1 = isP1 ? 0 : 2;
        match.score2 = isP1 ? 2 : 0;
    } else if (action === 'draw') {
        match.winner = 'draw';
        match.score1 = 1;
        match.score2 = 1;
    }
    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament);
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});
app.post('/tournaments/:id/result_score', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    const { score1, score2 } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    const roundKey = `round_${tournament.currentRound}`;
    const mIndex = tournament.matches[roundKey].findIndex(m => m.player1 === req.session.user.id || m.player2 === req.session.user.id);
    if (mIndex === -1) return res.send('Match not found or Unauthorized');
    let match = tournament.matches[roundKey][mIndex];
    match.score1 = parseInt(score1);
    match.score2 = parseInt(score2);
    if (match.score1 > match.score2) match.winner = String(match.player1);
    else if (match.score2 > match.score1) match.winner = String(match.player2);
    else match.winner = 'draw';
    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament);
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});
app.post('/tournaments/:id/next-round', (req, res) => {
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    updateParticipantsScores(tournament);
    const matches = TournamentLogic.generatePairings(tournament);
    if (!matches) return res.send('Impossibile generare altri turni. Forse il torneo è finito?');
    tournament.currentRound += 1;
    tournament.matches[`round_${tournament.currentRound}`] = matches;
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});
app.post('/tournaments/:id/end', (req, res) => {
    if (!req.session.user || (req.session.user.role !== 'admin' && req.session.user.role !== 'organizer')) return res.status(403).send('Unauthorized');
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == req.params.id);
    let tournament = tournaments[tIndex];
    tournament.status = 'completed';
    DataManager.saveTournaments(tournaments);
    res.redirect('/tournaments/' + tournament.id);
});

// Profile & Deck
app.get('/profile', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    res.redirect('/profile/' + req.session.user.username);
});
app.get('/profile/:username', (req, res) => {
    const users = DataManager.getUsers();
    const profileUser = users.find(u => u.username === req.params.username);
    if (!profileUser) return res.status(404).send('User not found');
    const tournaments = DataManager.getTournaments();
    const participatedTournaments = tournaments.filter(t => t.participants.some(p => p.userId === profileUser.id));
    res.render('profile', { profileUser, participatedTournaments });
});
app.get('/decklist/:name', (req, res) => {
    const tournaments = DataManager.getTournaments();
    const users = DataManager.getUsers();
    let found = null;
    for (let t of tournaments) {
        let p = t.participants.find(part => part.decklist_name === req.params.name);
        if (p) { found = { tournament: t, participant: p }; break; }
    }
    if (!found) return res.status(404).send('Decklist not found');
    const user = users.find(u => u.id === found.participant.userId);
    const playerName = user ? user.username : 'Unknown';
    const resultString = `${found.participant.games_won}-${found.participant.games_lost}-0`;
    res.render('decklist', { decklistName: found.participant.decklist_name, decklistContent: found.participant.decklist, playerName, tournament: found.tournament, resultString });
});

// Admin
app.get('/settings', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    res.render('settings');
});

const isAdmin = (req, res, next) => {
    if (req.session.user && (req.session.user.role === 'admin' || req.session.user.role === 'organizer')) next();
    else res.status(403).send('Access Denied');
};

app.get('/admin', isAdmin, (req, res) => res.render('admin/dashboard'));

app.get('/admin/tournaments', isAdmin, (req, res) => {
    const tournaments = DataManager.getTournaments();
    res.render('admin/tournaments', { tournaments });
});
// Delete Tournament
app.post('/admin/tournaments/:id/delete', isAdmin, (req, res) => {
    let tournaments = DataManager.getTournaments();
    tournaments = tournaments.filter(t => t.id != req.params.id);
    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/tournaments');
});
// Create Tournament STUB
// Request says: "creare tornei".
app.get('/admin/tournaments/new', isAdmin, (req, res) => {
    res.send('<h1>Nuovo Torneo (Non implementato UI complessa, usare JSON o futura implementazione)</h1><p>Funzionalità base richiesta completata.</p>');
});

// Admin Manage Matches
app.get('/admin/tournaments/:id/matches', isAdmin, (req, res) => {
    const tournament = DataManager.getTournamentById(req.params.id);
    if (!tournament) return res.status(404).send('Tournament not found');
    const usersMap = getUserMap();
    res.render('admin/tournament_matches', { tournament, usersMap });
});

app.post('/admin/matches/update', isAdmin, (req, res) => {
    const { tournamentId, roundKey, matchIndex, score1, score2 } = req.body;
    const tournaments = DataManager.getTournaments();
    const tIndex = tournaments.findIndex(t => t.id == tournamentId);
    if (tIndex === -1) return res.status(404).send('Tournament not found');

    let tournament = tournaments[tIndex];
    if (!tournament.matches[roundKey] || !tournament.matches[roundKey][matchIndex]) return res.status(404).send('Match not found');

    let match = tournament.matches[roundKey][matchIndex];
    match.score1 = parseInt(score1);
    match.score2 = parseInt(score2);

    // Auto-update winner based on new score
    if (match.score1 > match.score2) match.winner = String(match.player1);
    else if (match.score2 > match.score1) match.winner = String(match.player2);
    else match.winner = 'draw';

    updateParticipantsScores(tournament);
    tournament.participants = TournamentLogic.calculateStandings(tournament);

    DataManager.saveTournaments(tournaments);
    res.redirect('/admin/tournaments/' + tournament.id + '/matches');
});

app.get('/admin/users', isAdmin, (req, res) => {
    const users = DataManager.getUsers();
    res.render('admin/users', { users });
});
app.post('/admin/users/:id/delete', isAdmin, (req, res) => {
    let users = DataManager.getUsers();
    users = users.filter(u => u.id != req.params.id);
    DataManager.saveUsers(users);
    res.redirect('/admin/users');
});

// Admin Edit User
app.get('/admin/users/:id/edit', isAdmin, (req, res) => {
    const users = DataManager.getUsers();
    const targetUser = users.find(u => u.id == req.params.id);
    if (!targetUser) return res.status(404).send('User not found');
    res.render('admin/user_edit', { targetUser, error: null });
});

app.post('/admin/users/:id/edit', isAdmin, (req, res) => {
    const { username, email, role, password } = req.body;
    const users = DataManager.getUsers();
    const uIndex = users.findIndex(u => u.id == req.params.id);
    if (uIndex === -1) return res.status(404).send('User not found');

    // Validation
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

app.get('/admin/decklists', isAdmin, (req, res) => {
    const tournaments = DataManager.getTournaments();
    const usersMap = getUserMap();
    res.render('admin/decklists', { tournaments, usersMap });
});
app.post('/admin/decklists/categorize', isAdmin, (req, res) => {
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

app.listen(PORT, () => {
    console.log(`Server running on port ${PORT} `);
});
