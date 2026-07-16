let draggedId = null;

function dragStart(e) {
    draggedId = e.currentTarget.dataset.id;
    e.currentTarget.style.opacity = '0.5';
}

function dragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}

function dragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

async function drop(e, newStatus) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    if (!draggedId) return;

    const card = document.getElementById('task-' + draggedId);
    const oldStatus = card?.dataset.status;
    if (oldStatus === newStatus) {
        if (card) card.style.opacity = '1';
        return;
    }

    const col = document.getElementById('col-' + newStatus.replace(/ /g, '-').toLowerCase());
    const addBtn = col?.querySelector('.kanban-add-btn');
    if (card && col) {
        col.insertBefore(card, addBtn);
        card.dataset.status = newStatus;
        card.style.opacity = '1';
    }
    updateColumnCounts();

    const json = await apiPost('/api/tasks.php?action=update_status', { task_id: parseInt(draggedId), status: newStatus });
    if (!json.success) {
        Toast.error(json.error || 'Błąd zapisu statusu');
        location.reload();
    }
    draggedId = null;
}

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const id = col.id.replace('col-', '');
        const count = col.querySelectorAll('.kanban-card').length;
        const badge = document.getElementById('count-' + id);
        if (badge) badge.textContent = count;
    });
}

let currentPriorityFilter = 'all';

function filterTasks(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.kanban-card').forEach(card => {
        const nameMatch = card.dataset.name?.includes(q) ?? true;
        const prioMatch = currentPriorityFilter === 'all' || card.dataset.priority === currentPriorityFilter;
        card.style.display = (nameMatch && prioMatch) ? '' : 'none';
    });
}

function setPriorityFilter(priority, btn) {
    currentPriorityFilter = priority;
    document.querySelectorAll('.priority-filters .filter-tab').forEach(b => b.classList.remove('active'));
    btn?.classList.add('active');
    filterTasks(document.getElementById('task-search')?.value || '');
}

let editingTaskId = null;

function openAddTaskModal(status = 'To Do') {
    editingTaskId = null;
    document.getElementById('task-id').value = '';
    document.getElementById('task-name').value = '';
    document.getElementById('task-desc').value = '';
    document.getElementById('task-deadline').value = '';
    document.getElementById('task-priority').value = 'Medium';
    document.getElementById('task-status').value = status;
    document.getElementById('task-modal-title').textContent = 'Nowe zadanie';
    document.getElementById('task-delete-btn').style.display = 'none';
    document.getElementById('task-modal').classList.add('active');
    document.getElementById('task-name').focus();
}

async function openTaskDetail(id) {
    const json = await apiGet('/api/tasks.php?action=get&id=' + parseInt(id));
    if (!json?.task) {
        Toast.error('Nie udało się załadować zadania.');
        return;
    }
    const t = json.task;

    editingTaskId = id;
    document.getElementById('task-id').value = id;
    document.getElementById('task-name').value = t.name;
    document.getElementById('task-desc').value = t.description || '';
    document.getElementById('task-project').value = t.project_id;
    document.getElementById('task-assign').value = t.assigned_to || '';
    document.getElementById('task-priority').value = t.priority;
    document.getElementById('task-status').value = t.status;
    document.getElementById('task-deadline').value = t.deadline || '';
    document.getElementById('task-modal-title').textContent = 'Edytuj zadanie';
    document.getElementById('task-delete-btn').style.display = 'block';
    document.getElementById('task-modal').classList.add('active');
}

function closeTaskModal() {
    document.getElementById('task-modal').classList.remove('active');
    editingTaskId = null;
}

async function saveTask() {
    const name = document.getElementById('task-name').value.trim();
    const project_id = document.getElementById('task-project').value;
    if (!name) {
        Toast.error('Podaj tytuł zadania.');
        return;
    }
    if (!project_id) {
        Toast.error('Wybierz projekt.');
        return;
    }

    const btn = document.getElementById('task-save-btn');
    btn.disabled = true;

    const payload = {
        id: editingTaskId,
        name,
        description: document.getElementById('task-desc').value,
        project_id: parseInt(project_id),
        assigned_to: parseInt(document.getElementById('task-assign').value) || null,
        priority: document.getElementById('task-priority').value,
        status: document.getElementById('task-status').value,
        deadline: document.getElementById('task-deadline').value || null
    };

    const action = editingTaskId ? 'update' : 'create';
    const json = await apiPost('/api/tasks.php?action=' + action, payload);

    btn.disabled = false;
    if (json.success) {
        Toast.success(editingTaskId ? 'Zadanie zaktualizowane!' : 'Zadanie utworzone!');
        closeTaskModal();
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error(json.error || 'Błąd zapisu zadania');
    }
}

async function deleteCurrentTask() {
    if (!editingTaskId) return;
    confirmDialog('Trwale usunąć to zadanie?', async () => {
        const json = await apiPost('/api/tasks.php?action=delete', { id: parseInt(editingTaskId) });
        if (json.success) {
            Toast.success('Zadanie usunięte.');
            closeTaskModal();
            document.getElementById('task-' + editingTaskId)?.remove();
            updateColumnCounts();
        } else {
            Toast.error(json.error || 'Błąd usuwania');
        }
    }, true);
}

// Project creation functions
function openCreateProjectModal() {
    document.getElementById('project-name').value = '';
    document.getElementById('project-description').value = '';
    document.getElementById('project-color').value = '#3b82f6';
    document.getElementById('project-deadline').value = '';
    document.querySelectorAll('[id^="color-"]').forEach(c => c.style.borderColor = 'transparent');
    document.getElementById('color-' + '8993f41cb83ef28ce555b74c2d43d1a3').style.borderColor = 'var(--primary)';
    document.getElementById('project-modal').classList.add('active');
    document.getElementById('project-name').focus();
}

function closeCreateProjectModal() {
    document.getElementById('project-modal').classList.remove('active');
}

function selectProjectColor(color) {
    document.getElementById('project-color').value = color;
    document.querySelectorAll('[id^="color-"]').forEach(c => c.style.borderColor = 'transparent');
    document.getElementById('color-' + crypto.subtle ? btoa(color).replace(/[^a-z0-9]/gi,'').substr(0,10) : 'default').style.borderColor = 'var(--primary)';
    document.getElementById('color-' + md5(color)).style.borderColor = 'var(--primary)';
}

async function saveNewProject() {
    const name = document.getElementById('project-name').value.trim();
    if (!name) {
        Toast.error('Podaj nazwę projektu.');
        return;
    }

    const btn = document.querySelector('#project-modal .btn-primary');
    btn.disabled = true;

    const payload = {
        name,
        description: document.getElementById('project-description').value,
        color: document.getElementById('project-color').value,
        deadline: document.getElementById('project-deadline').value || null
    };

    const json = await apiPost('/api/projects.php?action=create', payload);
    btn.disabled = false;

    if (json.success) {
        Toast.success('Projekt utworzony!');
        closeCreateProjectModal();
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error(json.error || 'Błąd tworzenia projektu');
    }
}
