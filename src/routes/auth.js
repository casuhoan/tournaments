const express = require('express');
const router = express.Router();
const DataManager = require('../dataManager');

router.get('/login', (req, res) => res.render('login', { error: null }));

router.post('/login', (req, res) => {
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

router.get('/logout', (req, res) => { req.session.destroy(); res.redirect('/'); });

router.get('/register', (req, res) => res.render('register', { error: null }));

router.post('/register', (req, res) => {
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

module.exports = router;
