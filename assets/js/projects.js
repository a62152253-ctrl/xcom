// Client-side project search
function filterProjects(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.project-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
}

// Dropdown menus
function toggleProjectMenu(id) {
    const menu = document.getElementById('project-menu-' + parseInt(id));
    const isOpen = menu.classList.contains('active');
    document.querySelectorAll('.project-dropdown').forEach(m => m.classList.remove('active'));
    if (!isOpen) menu.classList.add('active');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.project-card-menu-wrap')) {
        document.querySelectorAll('.project-dropdown').forEach(m => m.classList.remove('active'));
    }
});

// Create Project
function openCreateProjectModal() { document.getElementById('create-project-modal').classList.add('active'); }
function closeCreateProjectModal() { document.getElementById('create-project-modal').classList.remove('active'); }

async function submitCreateProject() {
    const btn = document.getElementById('create-proj-btn');
    const name = document.getElementById('project-name').value.trim();
    if (!name) { Toast.error('Podaj nazwę projektu.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Tworzę...';

    const json = await apiPost('/api/projects.php?action=create', {
        name,
        description: document.getElementById('project-desc').value,
        color: document.getElementById('project-color').value,
        deadline: document.getElementById('project-deadline').value || null
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-plus"></i> Stwórz projekt';

    if (json.success) {
        Toast.success('Projekt "' + name + '" został stworzony!');
        setTimeout(() => location.reload(), 1000);
    } else {
        Toast.error(json.error || 'Błąd tworzenia projektu');
    }
}

// Add Member
function openAddMemberModal(id) {
    document.getElementById('member-project-id').value = parseInt(id);
    document.getElementById('add-member-modal').classList.add('active');
}
function closeAddMemberModal() { document.getElementById('add-member-modal').classList.remove('active'); }

async function submitAddMember() {
    const project_id = parseInt(document.getElementById('member-project-id').value);
    const email = document.getElementById('member-email').value.trim();
    const role = document.getElementById('member-role').value;
    if (!email) { Toast.error('Podaj adres e-mail.'); return; }

    const json = await apiPost('/api/projects.php?action=add_member', { project_id, email, role });
    if (json.success) {
        Toast.success('Członek dodany do projektu!');
        closeAddMemberModal();
    } else {
        Toast.error(json.error || 'Błąd dodawania członka');
    }
}

// Archive
function archiveProject(id) {
    confirmDialog('Zarchiwizować projekt?', async () => {
        const json = await apiPost('/api/projects.php?action=archive', { id: parseInt(id) });
        if (json.success) { Toast.success('Projekt zarchiwizowany.'); setTimeout(() => location.reload(), 800); }
        else Toast.error(json.error || 'Błąd archiwizacji');
    });
}