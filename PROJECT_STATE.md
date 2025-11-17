# Project State Summary (2025-11-16)

This file summarizes the state of the tournament management project to allow for easy context restoration in a future session.

## Final TODO List Status

- [x] Clean up the incorrect project structure (remove `backend` and `frontend` folders).
- [x] Set up the new project structure for a PHP application.
- [x] Implement the public main page (`index.php`).
- [x] Implement user registration and login system (PHP/JSON).
- [x] Implement the authenticated dashboard view.
- [x] Implement tournament creation logic.
- [x] Set up Docker for deployment.
- [x] Implement the tournament lobby, match pairings, and result submission.
- [x] Implement Swiss and Elimination tournament formats.
- [x] Implement viewing for partial and final rankings.
- [x] BUG FIX: Tournament page asks for login even when logged in.
- [x] BUG FIX: Score submission UI (+1 button) does not update display.
- [x] BUG FIX: "I tuoi tornei" link on dashboard leads to wrong page.
- [x] FEATURE: Allow login with username.
- [x] FEATURE: Require double password entry for registration.
- [x] FEATURE: Enhance dashboard navigation for enrolled tournaments.
- [x] FEATURE: Display direct tournament link on creation.
- [x] FEATURE: Implement no-rematch logic for pairings.
- [x] BUG FIX: Hide login prompt for logged-in users on index.php.
- [x] FEATURE: Create Admin Panel (Part 6).
- [x] FEATURE: Create User Settings Page (Part 7).
- [x] UI FIX: Replace custom dropdown with Bootstrap dropdown component.
- [x] BUG FIX: Add defensive checks for missing keys in admin pages.
-[x] DATA FIX: Add missing 'status' and 'role' keys to existing data.
- [x] REFACTOR: Centralize helper functions in helpers.php.
- [x] BUG FIX & FEATURE: Fix score submission UI and add decrement button.
- [x] BUG FIX: Fix undefined '$logged_in_username' in dashboard.php.
- [x] BUG FIX: Fix 'undefined rank' warning on index.php.
- [x] FEATURE: Implement early tournament end if no pairings possible (Part 8).
- [x] RENAME: Rename dashboard.php to home.php.
- [x] DATA: Refine tournament data structure for decklist names/formats.
- [x] FEATURE: Implement Admin Panel 'Gestione Liste' (Richiesta 11).
- [x] FEATURE: Implement Single Decklist Viewer (Richiesta 13).
- [x] FEATURE: Implement Single Tournament Viewer (Richiesta 12).
- [x] FEATURE: Enhance User Profile Page (Richiesta 10).
- [x] FEATURE: Implement All Tournaments Page with Pagination/Filtering (Nona richiesta).
- [x] FEATURE: Enhance Home Page (Nona richiesta).
- [x] BUG FIX: Re-add missing action buttons to home.php.
- [x] BUG FIX: Correctly end Elimination tournaments when one player remains.
- [x] BUG FIX: Correct profile link in header dropdowns.
- [x] BUG FIX: Correct profile link in header to point to public view.
- [x] FEATURE: Implement "Edit Tournament" functionality for admins.
- [x] FEATURE: Implement user avatar uploads.
- [x] BUG FIX: Investigate and fix "Edit Tournament" functionality.
- [x] FEATURE: Add default avatar for users without one.

## Project Summary

- **Tech Stack:** PHP, HTML, CSS, JavaScript. Data is stored in JSON files (`data/users.json`, `data/tournaments.json`). Bootstrap 5 is used for styling.
- **Core Features:**
    - **User System:** Registration, Login (username/email), Admin/Moderator/Player roles.
    - **Avatars:** Users can upload their own avatars, with a default avatar as a fallback.
    - **Tournament Management:**
        - Admins/Mods can create tournaments with custom settings (Swiss/Elimination, Bo1/Bo3, rounds, decklist requirements).
        - Organizers can start tournaments and advance rounds.
        - Players can join, leave, and submit decklists.
        - Players submit their own match results.
    - **Pairing Logic:**
        - **Swiss:** First round is random. Subsequent rounds are based on score. Tie-breakers are Score > Game Win Percentage (GWP) > Malus > Random. No-rematch logic is implemented.
        - **Elimination:** Winners advance.
        - **Bye Handling:** Implemented for odd numbers of players.
        - **Early End:** Tournaments end automatically if there are not enough players for a new round (both Swiss and Elimination).
    - **Admin Panel:**
        - User management (Create, Edit, Delete).
        - Tournament management (Edit Name/Date, Delete).
        - Decklist management (Categorize and name submitted decklists).
    - **Viewing & Navigation:**
        - **Home Page:** Summarizes user-specific tournaments and provides quick actions.
        - **All Tournaments Page:** A filterable, paginated list of all tournaments.
        - **Public Profile Page (`view_profile.php`):** Shows a user's tournament history, rank, and W-L-D record.
        - **Tournament Viewer (`view_tournament.php`):** Shows final standings and details for a specific tournament.
        - **Decklist Viewer (`view_decklist.php`):** Shows a specific decklist, who played it, in which tournament, and their result.
- **Deployment:** The project is set up with a `Dockerfile` and `docker-compose.yml` for containerized deployment.
- **Data Persistence:** The `data` and `uploads` directories should be treated as persistent volumes in a production environment.
