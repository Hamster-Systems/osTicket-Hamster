(function () {
    'use strict';

    var storageKey = 'portal-theme';
    var root = document.documentElement;
    var saved;

    try {
        saved = window.localStorage.getItem(storageKey);
    } catch (error) {
        saved = null;
    }

    var prefersDark = window.matchMedia
        && window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('portal-theme-toggle');
        if (!button)
            return;

        function updateButton() {
            var dark = root.getAttribute('data-theme') === 'dark';
            var action = dark ? 'Switch to light mode' : 'Switch to dark mode';
            var label = button.querySelector('.theme-label');
            button.setAttribute('aria-label', action);
            button.setAttribute('title', action);
            if (label)
                label.textContent = dark ? 'Light mode' : 'Dark mode';
        }

        button.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark'
                ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try {
                window.localStorage.setItem(storageKey, next);
            } catch (error) {}
            updateButton();
        });

        updateButton();
    });
})();
