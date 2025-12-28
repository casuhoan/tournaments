function isAdmin(req, res, next) {
    if (req.session.user && req.session.user.role === 'admin') {
        next();
    } else {
        res.status(403).send('Access Denied');
    }
}

function isLoggedIn(req, res, next) {
    if (req.session.user) {
        next();
    } else {
        res.redirect('/login');
    }
}

module.exports = { isAdmin, isLoggedIn };
