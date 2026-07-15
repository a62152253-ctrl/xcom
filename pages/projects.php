<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
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

$sort_sql = match($sort) {
    'deadline' => 'p.deadline ASC NULLS LAST',
    'created'  => 'p.created_at DESC',
    default    => 'p.name ASC'
};

$stmt = $db->prepare("
    SELECT DISTINCT p.*, u.full_name as creator_name,
        pm_me.role as user_project_role,
        (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done_tasks
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN project_members pm_me ON p.id = pm_me.project_id AND pm_me.user_id = ?
    LEFT JOIN users u ON p.created_by = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    ORDER BY $sort_sql
");
$stmt->execute([$user_id, $user_id, $user_id]);
$all_projects = $stmt->fetchAll();

// Filter
$projects = match($filter) {
    'mine'   => array_filter($all_projects, fn($p) => (int)$p['created_by'] === $user_id),
    'shared' => array_filter($all_projects, fn($p) => (int)$p['created_by'] !== $user_id),
    default  => $all_projects
};
$projects = array_values($projects);
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
    <div class="empty-state" style="grid-column:1/-1">
        <i class="fa-solid fa-folder-open"></i>
        <h3>Brak projektów</h3>
        <p>Zacznij od stworzenia swojego pierwszego projektu.</p>
        <button class="btn btn-primary" onclick="openCreateProjectModal()" style="width:auto;margin-top:1rem">Stwórz projekt</button>
    </div>
    <?php else: ?>
    <?php foreach ($projects as $p):
        $pct = $p['total_tasks'] > 0 ? round((int)$p['done_tasks'] / (int)$p['total_tasks'] * 100) : 0;
        $is_owner = (int)$p['created_by'] === $user_id;
    ?>
    <div class="project-card" data-search="<?= htmlspecialchars(strtolower(htmlspecialchars($p['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' ' . htmlspecialchars($p['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?>">
        <!-- Color stripe -->
        <div class="project-card-stripe" style="background:<?= htmlspecialchars($p['color'], ENT_QUOTES, 'UTF-8') ?>"></div>

        <div class="project-card-body">
            <div class="project-card-header">
                <h3 class="project-card-title" onclick="window.location.href='/pages/tasks.php?project_id=<?= (int)$p['id'] ?>'">
                    <?= htmlspecialchars($p['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </h3>
                <div class="project-card-menu-wrap">
                    <button class="btn-icon btn-ghost" onclick="toggleProjectMenu(<?= (int)$p['id'] ?>)" title="Opcje">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div id="project-menu-<?= (int)$p['id'] ?>" class="project-dropdown">
                        <a class="project-dropdown-item" href="/pages/tasks.php?project_id=<?= (int)$p['id'] ?>">
                            <i class="fa-solid fa-list-check"></i> Zadania
                        </a>
                        <div class="project-dropdown-item" onclick="openAddMemberModal(<?= (int)$p['id'] ?>)">
                            <i class="fa-solid fa-user-plus"></i> Członkowie
                        </div>
                        <?php if ($is_owner): ?>
                        <div class="project-dropdown-item project-dropdown-item--danger" onclick="archiveProject(<?= (int)$p['id'] ?>)">
                            <i class="fa-solid fa-box-archive"></i> Archiwizuj
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <p class="project-card-desc"><?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 90), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><?= mb_strlen($p['description'] ?? '') > 90 ? '…' : '' ?></p>

            <!-- Progress -->
            <div class="project-card-progress">
                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);margin-bottom:.3rem">
                    <span>Postęp zadań</span>
                    <span><?= (int)$p['done_tasks'] ?>/<?= (int)$p['total_tasks'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($p['color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                </div>
            </div>
        </div>

        <div class="project-card-footer">
            <div style="display:flex;align-items:center;gap:.35rem;font-size:.78rem;color:var(--text-muted)">
                <i class="fa-solid fa-users"></i> <?= (int)$p['member_count'] ?> członków
            </div>
            <div style="font-size:.78rem;color:<?= $p['deadline'] && strtotime($p['deadline']) < time() ? 'var(--danger)' : 'var(--text-muted)' ?>">
                <?php if ($p['deadline']): ?>
                <i class="fa-regular fa-clock"></i> <?= date('d.m.Y', strtotime($p['deadline'])) ?>
                <?php else: ?>
                <i class="fa-regular fa-calendar"></i> Bez terminu
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Create Project -->
<div class="modal-overlay" id="create-project-modal">
    <div class="modal-window">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-folder-plus" style="color:var(--primary)"></i> Nowy projekt</h2>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nazwa projektu *</label>
                <input class="form-control" type="text" id="project-name" placeholder="np. Redesign strony www" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Opis projektu</label>
                <textarea class="form-control" id="project-desc" rows="3" placeholder="Krótki opis celów projektu..." maxlength="1000"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kolor identyfikacyjny</label>
                    <input class="form-control" type="color" id="project-color" value="#3b82f6" style="height:44px;padding:.15rem;cursor:pointer">
                </div>
                <div class="form-group">
                    <label class="form-label">Termin zakończenia</label>
                    <input class="form-control" type="date" id="project-deadline">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateProjectModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="submitCreateProject()" style="width:auto" id="create-proj-btn">
                <i class="fa-solid fa-plus"></i> Stwórz projekt
            </button>
        </div>
    </div>
</div>

<!-- Modal: Add Member -->
<div class="modal-overlay" id="add-member-modal">
    <div class="modal-window" style="max-width:480px">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Dodaj członka</h2>
            <button class="modal-close" onclick="closeAddMemberModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="member-project-id">
            <div class="form-group">
                <label class="form-label">Adres e-mail użytkownika</label>
                <input class="form-control" type="email" id="member-email" placeholder="np. kolega@firma.pl" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Rola w projekcie</label>
                <select class="form-control" id="member-role">
                    <option value="Member">Member</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddMemberModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="submitAddMember()" style="width:auto">Dodaj</button>
        </div>
    </div>
</div>

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
