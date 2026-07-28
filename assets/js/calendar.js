let editingEventId = null;

function goToMonth(m, y) {
    if (!m || !y || isNaN(m) || isNaN(y)) return;
    window.location.href = `/pages/calendar.php?month=${parseInt(m)}&year=${parseInt(y)}`;
}

function goToToday() {
    const today = new Date();
    window.location.href = `/pages/calendar.php?month=${today.getMonth() + 1}&year=${today.getFullYear()}`;
}

function openAddEventModal(date = null) {
    editingEventId = null;
    document.getElementById('event-id').value = '';
    document.getElementById('event-title').value = '';
    document.getElementById('event-date').value = date || new Date().toISOString().split('T')[0];
    document.getElementById('event-time').value = '';
    document.getElementById('event-description').value = '';
    document.getElementById('event-modal-title').textContent = 'Nowe wydarzenie';
    document.getElementById('event-delete-btn').style.display = 'none';
    document.getElementById('event-modal').classList.add('active');
}

function showDayEvents(date) {
    openAddEventModal(date);
}

async function editEvent(id) {
    const json = await apiGet(`/api/calendar_detail.php?id=${parseInt(id)}`);
    if (!json?.event) {
        Toast.error('Nie udało się załadować wydarzenia.');
        return;
    }
    const e = json.event;

    editingEventId = id;
    document.getElementById('event-id').value = id;
    document.getElementById('event-title').value = e.title;
    document.getElementById('event-date').value = e.event_date;
    document.getElementById('event-time').value = e.event_time || '';
    document.getElementById('event-description').value = e.description || '';
    document.getElementById('event-modal-title').textContent = 'Edytuj wydarzenie';
    document.getElementById('event-delete-btn').style.display = 'block';
    document.getElementById('event-modal').classList.add('active');
}

function closeEventModal() {
    document.getElementById('event-modal').classList.remove('active');
    editingEventId = null;
}

async function saveEvent() {
    const title = document.getElementById('event-title').value.trim();
    const date = document.getElementById('event-date').value;

    if (!title || !date) {
        Toast.error('Podaj tytuł i datę.');
        return;
    }

    const btn = document.querySelector('#event-modal .btn-primary');
    btn.disabled = true;

    const payload = {
        id: editingEventId,
        title,
        event_date: date,
        event_time: document.getElementById('event-time').value || null,
        description: document.getElementById('event-description').value
    };

    const action = editingEventId ? 'update' : 'create';
    const json = await apiPost(`/api/calendar.php?action=${action}`, payload);

    btn.disabled = false;
    if (json.success) {
        Toast.success(editingEventId ? 'Wydarzenie zaktualizowane!' : 'Wydarzenie utworzone!');
        closeEventModal();
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error(json.error || 'Błąd zapisu');
    }
}

async function deleteEvent() {
    if (!editingEventId) return;
    confirmDialog('Usunąć to wydarzenie?', async () => {
        const json = await apiPost('/api/calendar.php?action=delete', { id: parseInt(editingEventId) });
        if (json.success) {
            Toast.success('Wydarzenie usunięte.');
            closeEventModal();
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.error || 'Błąd usuwania');
        }
    }, true);
}