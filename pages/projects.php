<?php
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../services/ProjectService.php';

$projectService = new ProjectService();
$user_id = $_SESSION['user_id'];

// Whitelist validation for sort and filter
$sort = $_GET['sort'] ?? 'name';
if (!in_array($sort, ['name', 'deadline', 'created'])) {
    $sort = 'name';
}

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'mine', 'shared'])) {
    $filter = 'all';
}

$projects = $projectService->getProjectsWithStats($user_id, $sort, $filter);
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-folder-open"></i> Projekty</h1>
        <p class="page-subtitle">Twórz i zarządzaj projektami zespołowymi.</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateProjectModal()" style="width:auto">
        <i class="fa-solid fa-plus"></i> Nowy projekt
    </button>
</div>

<!-- Search + Filters -->
<div class="projects-toolbar">
    <div class="projects-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="project-search" class="form-control" placeholder="Szukaj projektu..." oninput="filterProjects(this.value)">
    </div>
    <div class="filter-tabs">
        <a href="?filter=all&sort=<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>"    class="filter-tab <?= $filter === 'all'    ? 'active' : '' ?>">Wszystkie (<?= count($all_projects) ?>)</a>
        <a href="?filter=mine&sort=<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>"   class="filter-tab <?= $filter === 'mine'   ? 'active' : '' ?>">Moje</a>
        <a href="?filter=shared&sort=<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>" class="filter-tab <?= $filter === 'shared' ? 'active' : '' ?>">Udostępnione</a>
    </div>
    <div class="filter-tabs">
        <span style="font-size:.75rem;color:var(--text-muted);align-self:center">Sortuj:</span>
        <a href="?filter=<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>&sort=name"     class="filter-tab <?= $sort === 'name'     ? 'active' : '' ?>"><i class="fa-solid fa-arrow-down-a-z"></i> Nazwa</a>
        <a href="?filter=<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>&sort=deadline" class="filter-tab <?= $sort === 'deadline' ? 'active' : '' ?>"><i class="fa-regular fa-calendar"></i> Termin</a>
        <a href="?filter=<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>&sort=created"  class="filter-tab <?= $sort === 'created'  ? 'active' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Najnowsze</a>
    </div>
</div>

<!-- Projects Grid -->
<div class="projects-grid" id="projects-grid">
    <?php if (empty($projects)): ?>
    <div class="empty-state-premium" style="grid-column:1/-1">
        <div class="es-icon">📁</div>
        <div class="es-title">Brak projektów</div>
        <div class="es-sub">Zacznij od stworzenia swojego pierwszego projektu i zaproś do niego team.</div>
        <button class="es-btn" onclick="openCreateProjectModal()"><i class="fa-solid fa-plus"></i> Stwórz projekt</button>
    </div>
    <?php else: ?>
    <?php foreach ($projects as $p): ?>
        <?php
        $is_owner = ((int)$p['created_by'] === $user_id || ($p['user_project_role'] ?? '') === 'Owner');
        require __DIR__ . '/../includes/components/project_card.php';
        ?>

    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/modals/project_modals.php'; ?>

<script>
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
