let selectedColor = '#3b82f6';

function selectColor(c) {
    selectedColor = c;
    document.getElementById('note-color').value = c;
    document.querySelectorAll('.color-chip').forEach(el => {
        el.classList.toggle('color-chip--active', el.dataset.color === c);
    });
}

// Init first color
document.querySelector('.color-chip')?.classList.add('color-chip--active');

function openNoteModal() {
    document.getElementById('note-id').value = '';
    document.getElementById('note-title').value = '';
    document.getElementById('note-content').value = '';
    document.getElementById('note-tags').value = '';
    document.getElementById('note-pinned').checked = false;
    document.getElementById('note-modal-title').textContent = 'Nowa notatka';
    selectColor('#3b82f6');
    document.getElementById('note-modal').classList.add('active');
}

function closeNoteModal() {
    document.getElementById('note-modal').classList.remove('active');
}

function editNote(n) {
    document.getElementById('note-id').value = n.id;
    document.getElementById('note-title').value = n.title;
    document.getElementById('note-content').value = n.content || '';
    document.getElementById('note-tags').value = n.tags || '';
    document.getElementById('note-pinned').checked = n.is_pinned == 1;
    document.getElementById('note-modal-title').textContent = 'Edytuj notatkę';
    selectColor(n.color || '#3b82f6');
    document.getElementById('note-modal').classList.add('active');
}

async function saveNote() {
    const id = document.getElementById('note-id').value;
    const payload = {
        id: id || null,
        title: document.getElementById('note-title').value.trim() || 'Bez tytułu',
        content: document.getElementById('note-content').value,
        tags: document.getElementById('note-tags').value,
        color: document.getElementById('note-color').value,
        is_pinned: document.getElementById('note-pinned').checked ? 1 : 0
    };
    const action = id ? 'update' : 'create';
    const res = await fetch('/api/notes.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd zapisu');
}

async function deleteNote(id) {
    if (!confirm('Usunąć notatkę?')) return;
    const res = await fetch('/api/notes.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
}

function filterNotes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.note-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNoteModal(); });