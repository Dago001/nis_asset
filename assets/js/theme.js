/**
 * Theme (light/dark) handling.
 *
 * Precedence: a per-browser override in localStorage wins if the user has
 * ever toggled it; otherwise the system default (from the "Customization →
 * Default Theme" setting, server-rendered into <html data-theme-default>)
 * applies. The very first block below runs synchronously, before the page
 * paints, so there is no flash of the wrong theme.
 */
(function () {
    var root = document.documentElement;
    var stored = null;
    try { stored = localStorage.getItem('nis_ams_theme'); } catch (e) { /* storage blocked */ }

    if (stored === 'light' || stored === 'dark') {
        root.setAttribute('data-theme', stored);
    }
    // else: keep whatever the server already rendered on <html data-theme="...">
})();

document.addEventListener('DOMContentLoaded', function () {
    var root = document.documentElement;
    var toggle = document.getElementById('themeToggle');
    if (!toggle) return;

    function isDark() {
        return root.getAttribute('data-theme') === 'dark';
    }
    function reflect() {
        toggle.classList.toggle('active', isDark());
        toggle.setAttribute('aria-checked', isDark() ? 'true' : 'false');
    }
    reflect();

    toggle.addEventListener('click', function () {
        var next = isDark() ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('nis_ams_theme', next); } catch (e) { /* storage blocked */ }
        reflect();
    });
});
