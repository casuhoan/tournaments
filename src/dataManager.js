const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '../data');
const USERS_FILE = path.join(DATA_DIR, 'users.json');
const TOURNAMENTS_FILE = path.join(DATA_DIR, 'tournaments.json');

// Helper to read JSON
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

// Helper to write JSON
const writeJson = (file, data) => {
    try {
        fs.writeFileSync(file, JSON.stringify(data, null, 4), 'utf8');
        return true;
    } catch (err) {
        console.error(`Error writing ${file}:`, err);
        return false;
    }
};

const DataManager = {
    getUsers: () => readJson(USERS_FILE),
    saveUsers: (users) => writeJson(USERS_FILE, users),

    getTournaments: () => readJson(TOURNAMENTS_FILE),
    saveTournaments: (tournaments) => writeJson(TOURNAMENTS_FILE, tournaments),

    // Specific Helpers
    getUserById: (id) => {
        const users = readJson(USERS_FILE);
        return users.find(u => u.id === id);
    },

    getUserByEmail: (email) => {
        const users = readJson(USERS_FILE);
        return users.find(u => u.email === email);
    },

    getTournamentById: (id) => {
        const tournaments = readJson(TOURNAMENTS_FILE);
        return tournaments.find(t => t.id === Number(id)); // ID in JSON might be number
    }
};

module.exports = DataManager;
