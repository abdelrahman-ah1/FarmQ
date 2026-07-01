(function () {
    'use strict';

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
