// Avatar preview
function previewAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const existing = document.querySelector('.settings-avatar');
        if (existing) existing.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// Live theme switcher
async function setTheme(theme) {
    if (!['light', 'dark'].includes(theme)) return;

    document.documentElement?.setAttribute('data-theme', theme);
    document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('theme-option--active'));
    document.getElementById('theme-' + theme)?.classList.add('theme-option--active');

    const icon = document.getElementById('theme-toggle-btn');
    if (icon) icon.innerHTML = `<i class="fa-solid ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;

    const json = await apiPost('/api/profile.php?action=theme', { theme });
    if (json?.success) Toast.success('Motyw ' + (theme === 'dark' ? 'ciemny' : 'jasny') + ' aktywowany!');
}

// Push notifications
function requestPushPermission() {
    if (!('Notification' in window)) { Toast.warning('Twoja przeglądarka nie wspiera powiadomień push.'); return; }
    Notification.requestPermission().then(p => {
        if (p === 'granted') {
            Toast.success('Powiadomienia push włączone!');
            document.getElementById('push-btn').innerHTML = '<i class="fa-solid fa-check"></i> Włączone';
        } else {
            Toast.error('Brak zgody na powiadomienia.');
        }
    });
}

// Check existing push permission
if (window.Notification?.permission === 'granted') {
    const btn = document.getElementById('push-btn');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Włączone';
}