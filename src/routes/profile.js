const express = require('express');
const router = express.Router();
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const DataManager = require('../dataManager');

// Multer Config
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        const dir = 'public/uploads/avatars';
        if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
        cb(null, dir)
    },
    filename: function (req, file, cb) {
        cb(null, Date.now() + path.extname(file.originalname))
    }
});
const upload = multer({ storage: storage });

router.get('/settings', (req, res) => {
    if (!req.session.user) return res.redirect('/login');
    res.render('settings', { user: req.session.user, error: null, success: null });
});

router.post('/settings/profile', upload.single('avatar'), (req, res) => {
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
    if (password && password.trim() !== '') users[uIndex].password = password;

    DataManager.saveUsers(users);

    // Update session
    req.session.user = { ...users[uIndex] };

    res.render('settings', { user: req.session.user, success: 'Profile updated successfully' });
});

router.get('/profile/:id', (req, res) => {
    const users = DataManager.getUsers();
    const user = users.find(u => u.id == req.params.id);
    if (!user) return res.status(404).send('User not found');
    res.render('profile', { user });
});

module.exports = router;
