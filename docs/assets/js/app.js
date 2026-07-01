(function () {
    'use strict';

    // Theme Toggle Logic
    var themeToggleBtn = document.getElementById('theme-toggle');
    if (themeToggleBtn) {
        // Set initial icon
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        var icon = themeToggleBtn.querySelector('.theme-icon');
        if (icon) {
            icon.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
        }

        themeToggleBtn.addEventListener('click', function () {
            var theme = document.documentElement.getAttribute('data-theme');
            var newTheme = theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (icon) {
                icon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            }
        });
    }

    document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
        var targetId = btn.getAttribute('aria-controls');
        var target = targetId ? document.getElementById(targetId) : null;
        if (!target) {
            return;
        }

        btn.addEventListener('click', function () {
            var open = target.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.app-nav a, #site-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            ['app-nav', 'site-nav'].forEach(function (id) {
                var nav = document.getElementById(id);
                if (nav) {
                    nav.classList.remove('is-open');
                }
            });
            var toggle = document.querySelector('[data-nav-toggle]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });
})();
