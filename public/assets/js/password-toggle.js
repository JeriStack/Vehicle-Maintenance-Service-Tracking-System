// assets/js/password-toggle.js
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetSelector = button.getAttribute('data-target');
            var input = targetSelector ? document.querySelector(targetSelector) : null;
            if (!input) {
                return;
            }

            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            var icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }

            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            input.focus();
        });
    });
});
