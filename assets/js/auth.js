document.addEventListener('DOMContentLoaded', () => {
    // Password Visibility Toggle
    const toggleBtns = document.querySelectorAll('.pwd-toggle-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const input = e.currentTarget.parentElement.querySelector('input');
            const icon = e.currentTarget.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password Strength Meter
    const pwdInput = document.getElementById('password');
    const strengthContainer = document.getElementById('pwd-strength-container');
    const strengthText = document.getElementById('pwd-strength-text');

    if (pwdInput && strengthContainer) {
        pwdInput.addEventListener('input', (e) => {
            const val = e.target.value;
            let score = 0;

            if (val.length > 0) {
                if (val.length >= 6) score += 1;
                if (val.length >= 10) score += 1;
                if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 1;
                if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score += 1;
            }

            strengthContainer.className = 'pwd-strength-meter';
            if (score > 0) {
                strengthContainer.classList.add('strength-' + score);
            }

            if (strengthText) {
                if (score === 0) strengthText.textContent = '';
                else if (score === 1) strengthText.textContent = 'Słabe';
                else if (score === 2) strengthText.textContent = 'Średnie';
                else if (score === 3) strengthText.textContent = 'Dobre';
                else if (score === 4) strengthText.textContent = 'Silne';
            }
        });
    }

    // Password Confirmation Match
    const confirmPwdInput = document.getElementById('confirm_password');
    if (pwdInput && confirmPwdInput) {
        const validateMatch = () => {
            if (confirmPwdInput.value === '') {
                confirmPwdInput.style.borderColor = '';
            } else if (pwdInput.value === confirmPwdInput.value) {
                confirmPwdInput.style.borderColor = 'var(--success)';
            } else {
                confirmPwdInput.style.borderColor = 'var(--danger)';
            }
        };
        pwdInput.addEventListener('input', validateMatch);
        confirmPwdInput.addEventListener('input', validateMatch);
    }

    // Form Submit Loading State
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !form.dataset.loading) {
                form.dataset.loading = "true";
                submitBtn.classList.add('loading');
                // We do NOT disable the button or prevent default immediately,
                // because disabling the submit button might stop the form from submitting in some browsers.
                // Instead, we just add the visual loading state. The browser will handle the native submit.
            }
        });
    });
});
