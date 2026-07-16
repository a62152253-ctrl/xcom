async function markAllRead() {
    const res = await fetch('/api/notifications.php?action=mark_all_read', { method: 'POST' });
    const json = await res.json();
    if (json.success) location.reload();
}

async function deleteNotif(id, btn) {
    const res = await fetch('/api/notifications.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) btn.closest('.notif-full-item').style.animation = 'fadeOutRight .3s forwards';
    setTimeout(() => btn.closest('.notif-full-item')?.remove(), 300);
}