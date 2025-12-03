/**
 * Theme Toggle System
 * Handles dark/light mode switching with localStorage persistence
 */

(function() {
    'use strict';
    
    const THEME_KEY = 'tournament-theme';
    const THEME_DARK = 'dark';
    const THEME_LIGHT = 'light';
    
    /**
     * Get the current theme from localStorage or system preference
     */
    function getCurrentTheme() {
        const storedTheme = localStorage.getItem(THEME_KEY);
        
        if (storedTheme) {
            return storedTheme;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return THEME_DARK;
        }
        
        return THEME_LIGHT;
    }
    
    /**
     * Apply theme to document
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        
        // Update toggle button if it exists
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', 
                theme === THEME_DARK ? 'Attiva modalità chiara' : 'Attiva modalità scura'
            );
            
            // Update icon if using icon
            const icon = toggleBtn.querySelector('.theme-icon');
            if (icon) {
                icon.textContent = theme === THEME_DARK ? '☀️' : '🌙';
            }
        }
        
        // Update checkbox if it exists (for settings page)
        const themeCheckbox = document.getElementById('dark-mode-toggle');
        if (themeCheckbox) {
            themeCheckbox.checked = theme === THEME_DARK;
        }
    }
    
    /**
     * Toggle between dark and light themes
     */
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
        applyTheme(newTheme);
        
        // Dispatch custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('themechange', { 
            detail: { theme: newTheme } 
        }));
    }
    
    /**
     * Initialize theme on page load
     */
    function initTheme() {
        const theme = getCurrentTheme();
        applyTheme(theme);
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Toggle button in header
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
        
        // Checkbox in settings page
        const themeCheckbox = document.getElementById('dark-mode-toggle');
        if (themeCheckbox) {
            themeCheckbox.addEventListener('change', function() {
                const newTheme = this.checked ? THEME_DARK : THEME_LIGHT;
                applyTheme(newTheme);
            });
        }
        
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                }
            });
        }
    }
    
    // Initialize immediately to prevent flash
    initTheme();
    
    // Setup listeners when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupEventListeners);
    } else {
        setupEventListeners();
    }
    
    // Expose API for programmatic access
    window.ThemeManager = {
        getCurrentTheme,
        applyTheme,
        toggleTheme,
        THEME_DARK,
        THEME_LIGHT
    };
})();
