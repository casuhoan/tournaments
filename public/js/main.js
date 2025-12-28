// Theme Toggle
// In layout.ejs we read from localStorage. Here we can add a listener if we had a button.
// Let's add the button logic if present (e.g. in Settings)

document.addEventListener('DOMContentLoaded', () => {
    // General tooltip or other init logic

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    if (alerts) {
        setTimeout(() => {
            alerts.forEach(a => a.style.display = 'none');
        }, 5000);
    }
});

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}
