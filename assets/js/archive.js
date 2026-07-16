async function restoreProject(id) {
    if (!confirm('Przywrócić projekt?')) return;
    const res = await fetch('/api/projects.php?action=restore', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd przywracania');
}

async function deleteProject(id) {
    if (!confirm('TRWALE usunąć ten projekt i wszystkie jego zadania? Tej operacji nie można cofnąć.')) return;
    const res = await fetch('/api/projects.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd usuwania');
}