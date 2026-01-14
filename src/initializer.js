const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '../data');
const TEMP_DATA_DIR = '/tempdata';
const INIT_FLAG = path.join(DATA_DIR, '.initialized');

function initializeData(app) {
    if (!fs.existsSync(DATA_DIR)) {
        fs.mkdirSync(DATA_DIR, { recursive: true });
    }

    // Check if initialization has already happened
    if (fs.existsSync(INIT_FLAG)) {
        console.log('Data directory already initialized.');
        return;
    }

    console.log('New container/volume detected. Checking for migration data...');

    if (fs.existsSync(TEMP_DATA_DIR)) {
        console.log(`Migrating data from ${TEMP_DATA_DIR} to ${DATA_DIR}...`);

        try {
            const files = fs.readdirSync(TEMP_DATA_DIR);
            files.forEach(file => {
                const srcPath = path.join(TEMP_DATA_DIR, file);
                const destPath = path.join(DATA_DIR, file);

                // Only copy if destination doesn't exist to be safe, 
                // though usually volume is empty if .initialized is missing
                if (!fs.existsSync(destPath)) {
                    fs.copyFileSync(srcPath, destPath);
                    console.log(`Copied ${file}`);
                }
            });

            // Set global flag for UI notification
            if (app) {
                app.locals.migrationMessage = "Database inizializzato con successo. I dati sono stati migrati.";
            }

        } catch (err) {
            console.error('Error during data migration:', err);
        }
    } else {
        console.log('No /tempdata found. Skipping migration.');
    }

    // Check for leagues.json, create empty if missing
    const LEAGUES_FILE = path.join(DATA_DIR, 'leagues.json');
    if (!fs.existsSync(LEAGUES_FILE)) {
        try {
            // Check if temp data has it, otherwise default empty
            const tempLeagues = path.join(TEMP_DATA_DIR, 'leagues.json');
            if (fs.existsSync(tempLeagues)) {
                fs.copyFileSync(tempLeagues, LEAGUES_FILE);
                console.log('Migrated leagues.json from temp data.');
            } else {
                fs.writeFileSync(LEAGUES_FILE, '[]');
                console.log('Created empty leagues.json');
            }
        } catch (err) {
            console.error('Error initializing leagues.json:', err);
        }
    }

    // Create flag file
    try {
        fs.writeFileSync(INIT_FLAG, new Date().toISOString());
        console.log('Initialization complete.');
    } catch (err) {
        console.error('Could not write initialization flag:', err);
    }
}

module.exports = initializeData;
