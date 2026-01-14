const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '../data');
const USERS_FILE = path.join(DATA_DIR, 'users.json');
const TOURNAMENTS_FILE = path.join(DATA_DIR, 'tournaments.json');

// Helper to read JSON (Disk I/O)
const readJson = (file) => {
    try {
        if (!fs.existsSync(file)) {
            return [];
        }
        const data = fs.readFileSync(file, 'utf8');
        return JSON.parse(data);
    } catch (err) {
        console.error(`Error reading ${file}:`, err);
        return [];
    }
};

// Helper to write JSON (Disk I/O)
const writeJson = (file, data) => {
    try {
        fs.writeFileSync(file, JSON.stringify(data, null, 4), 'utf8');
        return true;
    } catch (err) {
        console.error(`Error writing ${file}:`, err);
        return false;
    }
};

// IN-MEMORY CACHE
// Load data once at startup (or first access)
let cachedUsers = readJson(USERS_FILE);
let cachedTournaments = readJson(TOURNAMENTS_FILE);

console.log('[DataManager] Data loaded into memory cache.');

const DataManager = {
    // READ: Return from memory (Instant)
    getUsers: () => cachedUsers,

    // WRITE: Update memory AND save to disk (Sync for safety)
    saveUsers: (users) => {
        cachedUsers = users; // Update cache
        return writeJson(USERS_FILE, users); // Persist
    },

    // READ: Return from memory (Instant)
    getTournaments: () => cachedTournaments,

    // WRITE: Update memory AND save to disk
    saveTournaments: (tournaments) => {
        cachedTournaments = tournaments; // Update cache
        return writeJson(TOURNAMENTS_FILE, tournaments); // Persist
    },

    // Specific Helpers (Now using cached data via getters)
    getUserById: (id) => {
        return cachedUsers.find(u => u.id === id);
    },

    getUserByEmail: (email) => {
        return cachedUsers.find(u => u.email === email);
    },

    getTournamentById: (id) => {
        return cachedTournaments.find(t => t.id == id);
    }
};

module.exports = DataManager;
