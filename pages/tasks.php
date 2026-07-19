<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt_projs = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    ORDER BY p.name ASC
");
$stmt_projs->execute([$user_id, $user_id]);
$user_projects = $stmt_projs->fetchAll();

$stmt_users = $db->query("SELECT id, full_name, email FROM users WHERE status='Active' ORDER BY full_name ASC");
$all_users = $stmt_users->fetchAll();

$open_task_id = (int)($_GET['task_id'] ?? 0);
$filter_project = (int)($_GET['project_id'] ?? 0);

$base_query = "
    SELECT t.*, p.name as project_name, p.color as project_color,
           u.full_name as assigned_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
";

$params = [$user_id, $user_id];

if ($filter_project) {
    $base_query .= " AND t.project_id = ?";
    $params[] = $filter_project;
}

$base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

$stmt_tasks = $db->prepare($base_query);
$stmt_tasks->execute($params);
$all_tasks = $stmt_tasks->fetchAll();

$open_task = null;
if ($open_task_id) {
    $stmt_ot = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
    $stmt_ot->execute([$open_task_id]);
    $open_task = $stmt_ot->fetch();
}

$columns = ['To Do' => [], 'In Progress' => [], 'Review' => [], 'Done' => []];
foreach ($all_tasks as $t) {
    if (isset($columns[$t['status']])) $columns[$t['status']][] = $t;
}
?>

<style>
.tasks-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.tasks-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.tasks-toolbar {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    align-items: center;
}

.tasks-search-wrap {
    flex: 1;
    min-width: 250px;
    position: relative;
    display: flex;
    align-items: center;
}

.tasks-search-wrap i {
    position: absolute;
    left: 1rem;
    color: var(--text-muted);
}

.tasks-search-wrap input {
    padding-left: 2.75rem !important;
    border-radius: 10px;
}

.priority-filters {
    display: flex;
    gap: 0.5rem;
}

.priority-filters .filter-tab {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.priority-filters .filter-tab:hover {
    border-color: var(--primary);
    background: var(--bg-primary);
}

.priority-filters .filter-tab.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.kanban-board {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 1.5rem;
    min-height: 600px;
}

.kanban-column {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 500px;
    max-height: 800px;
    overflow-y: auto;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.kanban-column:hover {
    background: var(--bg-primary);
    border-color: var(--border-color);
}

.kanban-column.drag-over {
    border-color: var(--primary);
    background: rgba(59, 130, 246, 0.05);
}

.kanban-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
    position: sticky;
    top: 0;
    background: var(--bg-secondary);
    z-index: 10;
}

.kanban-column-title {
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.kanban-count {
    background: var(--primary);
    color: white;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    min-width: 28px;
    text-align: center;
}

.kanban-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 1rem;
    cursor: grab;
    transition: all 0.2s ease;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid transparent;
}

.kanban-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-left-color: var(--primary);
}

.kanban-card:active {
    cursor: grabbing;
}

.kanban-card-tag {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.kanban-card-tag.tag-critical {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.kanban-card-tag.tag-high {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.kanban-card-tag.tag-medium {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.kanban-card-tag.tag-low {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.kanban-card-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    line-height: 1.4;
}

.kanban-card-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.kanban-card-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: var(--text-muted);
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

.kanban-add-btn {
    background: transparent;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    color: var(--text-muted);
    transition: all 0.2s ease;
    font-weight: 600;
    margin-top: auto;
}

.kanban-add-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--bg-secondary);
}

@media (max-width: 1024px) {
    .kanban-board {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .kanban-board {
        grid-template-columns: 1fr;
    }

    .tasks-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .priority-filters {
        flex-wrap: wrap;
    }

    .tasks-search-wrap {
        min-width: 100%;
    }
}
</style>

<!-- Page Header -->
<div class="tasks-header animate-fade">
    <div>
        <h1><i class="fa-solid fa-list-check"></i> Zadania</h1>
        <p style="margin: 0.5rem 0 0 0; color: var(--text-muted);">Kanban board — przeciągaj karty, aby zmienić status.</p>
    </div>
    <button class="btn btn-primary" onclick="openAddTaskModal()">
        <i class="fa-solid fa-plus"></i> Nowe zadanie
    </button>
</div>

<!-- Toolbar -->
<div class="tasks-toolbar animate-slide-up">
    <div class="tasks-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="task-search" class="form-control" placeholder="Szukaj zadań..." oninput="filterTasks(this.value)">
    </div>

    <div class="priority-filters">
        <button class="filter-tab active" data-filter="all" onclick="setPriorityFilter('all', this)">
            <i class="fa-solid fa-list"></i> Wszystkie
        </button>
        <button class="filter-tab" data-filter="Critical" onclick="setPriorityFilter('Critical', this)">
            <i class="fa-solid fa-fire"></i> Krytyczne
        </button>
        <button class="filter-tab" data-filter="High" onclick="setPriorityFilter('High', this)">
            <i class="fa-solid fa-arrow-up"></i> Wysokie
        </button>
        <button class="filter-tab" data-filter="Medium" onclick="setPriorityFilter('Medium', this)">
            <i class="fa-solid fa-minus"></i> Średnie
        </button>
        <button class="filter-tab" data-filter="Low" onclick="setPriorityFilter('Low', this)">
            <i class="fa-solid fa-arrow-down"></i> Niskie
        </button>
    </div>

    <?php if (!empty($user_projects)): ?>
    <select class="form-control" style="width: auto;" onchange="if(this.value) window.location.href='/pages/tasks.php?project_id='+parseInt(this.value); else window.location.href='/pages/tasks.php'">
        <option value="">Wszystkie projekty</option>
        <?php foreach ($user_projects as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $filter_project == $p['id'] ? 'selected' : '' ?>>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= $p['color'] ?>;"></span>
            <?= sanitize($p['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
</div>

<!-- Kanban Board -->
<div class="kanban-board" id="kanban-board">
    <?php
    $col_colors = ['To Do' => '#64748b', 'In Progress' => '#3b82f6', 'Review' => '#8b5cf6', 'Done' => '#10b981'];
    $col_icons  = ['To Do' => 'fa-circle', 'In Progress' => 'fa-spinner', 'Review' => 'fa-eye', 'Done' => 'fa-circle-check'];
    foreach ($columns as $status => $cards):
    ?>
    <div class="kanban-column" id="col-<?= str_replace(' ', '-', strtolower($status)) ?>"
         ondrop="drop(event,'<?= sanitize($status) ?>')" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
        
        <div class="kanban-column-header">
            <span class="kanban-column-title" style="color: <?= $col_colors[$status] ?>;">
                <i class="fa-solid <?= $col_icons[$status] ?>" style="font-size: 0.85rem;"></i>
                <?= $status ?>
            </span>
            <span class="kanban-count" id="count-<?= str_replace(' ', '-', strtolower($status)) ?>"><?= count($cards) ?></span>
        </div>

        <?php foreach ($cards as $c): ?>
        <div class="kanban-card" draggable="true" id="task-<?= (int)$c['id'] ?>"
             data-id="<?= (int)$c['id'] ?>" data-status="<?= sanitize($c['status']) ?>"
             data-priority="<?= sanitize($c['priority']) ?>" data-name="<?= htmlspecialchars(strtolower($c['name']), ENT_QUOTES, 'UTF-8') ?>"
             ondragstart="dragStart(event)" onclick="openTaskDetail(<?= (int)$c['id'] ?>)">

            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                <span class="kanban-card-tag tag-<?= strtolower($c['priority']) ?>"><?= substr($c['priority'], 0, 1) ?></span>
                <?php if ($c['project_color'] && !$filter_project): ?>
                <span style="width: 10px; height: 10px; border-radius: 50%; background: <?= sanitize($c['project_color']) ?>; flex-shrink: 0;" title="<?= sanitize($c['project_name']) ?>"></span>
                <?php endif; ?>
            </div>

            <div class="kanban-card-title"><?= sanitize($c['name']) ?></div>

            <?php if ($c['description']): ?>
            <div class="kanban-card-desc"><?= sanitize(mb_substr(strip_tags($c['description']), 0, 80)) ?></div>
            <?php endif; ?>

            <div class="kanban-card-footer">
                <span>
                    <?php if ($c['assigned_name']): ?>
                    <i class="fa-solid fa-user" style="width: 12px; margin-right: 4px;"></i><?= sanitize(explode(' ', $c['assigned_name'])[0]) ?>
                    <?php else: ?>
                    <i class="fa-regular fa-user" style="opacity: 0.5;"></i>
                    <?php endif; ?>
                </span>
                <?php if ($c['deadline']): ?>
                <span style="color: <?= strtotime($c['deadline']) < time() && $c['status'] !== 'Done' ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                    <i class="fa-regular fa-calendar"></i> <?= date('d.m', strtotime($c['deadline'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button class="kanban-add-btn" onclick="openAddTaskModal('<?= sanitize($status) ?>')">
            <i class="fa-solid fa-plus"></i> Dodaj zadanie
        </button>
    </div>
    <?php endforeach; ?>
</div>

<!-- Create Project Modal -->
<?php require_once __DIR__ . '/../includes/modals/project_modal_tasks.php'; ?>

<!-- Task Modal -->
<?php require_once __DIR__ . '/../includes/modals/task_modal.php'; ?>

<script>
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
    document.getElementById('color-' + '<?php echo md5("#3b82f6"); ?>').style.borderColor = 'var(--primary)';
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

<?php if ($open_task_id): ?>
document.addEventListener('DOMContentLoaded', () => openTaskDetail(<?= $open_task_id ?>));
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
