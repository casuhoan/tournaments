// Final Server.js with Refactored Architecture
const express = require('express');
const session = require('express-session');
const bodyParser = require('body-parser');
const path = require('path');
const DataManager = require('./src/dataManager');
const TournamentLogic = require('./src/tournamentLogic');
const initializeData = require('./src/initializer');

// Routes
const authRoutes = require('./src/routes/auth');
const profileRoutes = require('./src/routes/profile');
const tournamentRoutes = require('./src/routes/tournament');
const adminRoutes = require('./src/routes/admin');
const leagueRoutes = require('./src/routes/leagues');

const app = express();
const PORT = process.env.PORT || 8000;

// Auto-Migration / Initialization
initializeData(app);

app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static('public'));
app.use('/data', express.static('data'));
app.use('/img', express.static('public/img'));
app.use('/uploads', express.static('public/uploads')); // Serve uploaded avatars

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(session({
    secret: 'grandius_tournament_secret',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false }
}));

// Global Middleware
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

// Routing
app.get('/', (req, res) => {
    const tournaments = DataManager.getTournaments();
    tournaments.sort((a, b) => new Date(b.date) - new Date(a.date));
    const usersMap = getUserMap();
    const recentTournaments = tournaments.slice(0, 3);
    const activeTournaments = tournaments.filter(t => t.status === 'in_progress' || t.status === 'created');
    const completedTournaments = tournaments.filter(t => t.status === 'completed');
    let stats = { totalTournaments: tournaments.length, activeTournaments: activeTournaments.length, completedTournaments: completedTournaments.length };

    // Check for migration message (auto-clears after view)
    const migrationMsg = app.locals.migrationMessage;
    if (migrationMsg) delete app.locals.migrationMessage;

    res.render('index', { recentTournaments, activeTournaments, completedTournaments, stats, usersMap, migrationMessage: migrationMsg });
});

// Mount Routes
app.use('/', authRoutes);
app.use('/admin', adminRoutes);
app.use('/tournaments', tournamentRoutes);
app.use('/', profileRoutes);
app.use('/leagues', leagueRoutes);

app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
