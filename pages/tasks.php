<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Open task_id from URL
$open_task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
$filter_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Get user's projects for filter
$stmt_projs = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    ORDER BY p.name ASC
");
$stmt_projs->execute([$user_id, $user_id]);
$user_projects = $stmt_projs->fetchAll();

// Get all accessible users (for assign dropdown)
$stmt_users = $db->query("SELECT id, full_name, email FROM users WHERE status='Active' ORDER BY full_name ASC");
$all_users = $stmt_users->fetchAll();

// Get tasks (kanban)
$proj_where = $filter_project ? "AND t.project_id = $filter_project" : '';
$stmt_tasks = $db->prepare("
    SELECT t.*, p.name as project_name, p.color as project_color,
           u.full_name as assigned_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0 $proj_where
    ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC
");
$stmt_tasks->execute([$user_id, $user_id]);
$all_tasks = $stmt_tasks->fetchAll();

// If specific task opened via URL, load it
$open_task = null;
if ($open_task_id) {
    $stmt_ot = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
    $stmt_ot->execute([$open_task_id]);
    $open_task = $stmt_ot->fetch();
}

// Group by status
$columns = ['To Do' => [], 'In Progress' => [], 'Review' => [], 'Done' => []];
foreach ($all_tasks as $t) {
    if (isset($columns[$t['status']])) $columns[$t['status']][] = $t;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-list-check"></i> Zadania</h1>
        <p class="page-subtitle">Kanban board — przeciągaj karty, aby zmienić status.</p>
    </div>
    <button class="btn btn-primary" onclick="openAddTaskModal()" style="width:auto">
        <i class="fa-solid fa-plus"></i> Nowe zadanie
    </button>
</div>

<!-- Toolbar: search + filter -->
<div class="tasks-toolbar">
    <div class="tasks-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="task-search" class="form-control" placeholder="Szukaj zadań..." oninput="filterTasks(this.value)">
    </div>

    <div class="filter-tabs tasks-filters">
        <button class="filter-tab active" data-filter="all" onclick="setPriorityFilter('all',this)">Wszystkie</button>
        <button class="filter-tab" data-filter="Critical" onclick="setPriorityFilter('Critical',this)" style="color:var(--danger)"><i class="fa-solid fa-fire"></i> Krytyczne</button>
        <button class="filter-tab" data-filter="High" onclick="setPriorityFilter('High',this)" style="color:var(--warning)"><i class="fa-solid fa-arrow-up"></i> Wysokie</button>
        <button class="filter-tab" data-filter="Medium" onclick="setPriorityFilter('Medium',this)">Średnie</button>
        <button class="filter-tab" data-filter="Low" onclick="setPriorityFilter('Low',this)" style="color:var(--success)">Niskie</button>
    </div>

    <?php if ($filter_project): ?>
    <a href="/pages/tasks.php" class="filter-tab active" style="border-color:var(--primary)">
        <i class="fa-solid fa-times"></i> Wyczyść filtr projektu
    </a>
    <?php endif; ?>

    <?php if (!empty($user_projects)): ?>
    <select class="form-control" style="width:auto;padding:.4rem .875rem;font-size:.825rem" onchange="if(this.value) window.location.href='/pages/tasks.php?project_id='+this.value; else window.location.href='/pages/tasks.php'">
        <option value="">Wszystkie projekty</option>
        <?php foreach ($user_projects as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $filter_project == $p['id'] ? 'selected' : '' ?>>
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
         ondrop="drop(event,'<?= $status ?>')" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
        <div class="kanban-column-header">
            <span class="kanban-column-title" style="color:<?= $col_colors[$status] ?>">
                <i class="fa-solid <?= $col_icons[$status] ?>" style="margin-right:.4rem;font-size:.8rem"></i>
                <?= $status ?>
            </span>
            <span class="kanban-count" id="count-<?= str_replace(' ', '-', strtolower($status)) ?>"><?= count($cards) ?></span>
        </div>

        <?php foreach ($cards as $c): ?>
        <div class="kanban-card" draggable="true" id="task-<?= $c['id'] ?>"
             data-id="<?= $c['id'] ?>" data-status="<?= $c['status'] ?>"
             data-priority="<?= $c['priority'] ?>" data-name="<?= strtolower(sanitize($c['name'])) ?>"
             ondragstart="dragStart(event)" onclick="openTaskDetail(<?= $c['id'] ?>)">

            <span class="kanban-card-tag tag-<?= strtolower($c['priority']) ?>"><?= $c['priority'] ?></span>
            <?php if (!empty($c['project_color']) && !$filter_project): ?>
            <span style="float:right;width:10px;height:10px;border-radius:50%;background:<?= $c['project_color'] ?>;margin-top:2px" title="<?= sanitize($c['project_name']) ?>"></span>
            <?php endif; ?>

            <div class="kanban-card-title"><?= sanitize($c['name']) ?></div>

            <?php if ($c['description']): ?>
            <div class="kanban-card-desc"><?= sanitize(mb_substr(strip_tags($c['description']), 0, 80)) ?></div>
            <?php endif; ?>

            <div class="kanban-card-footer">
                <span><?= $c['assigned_name'] ? '<i class="fa-solid fa-user"></i> '.sanitize($c['assigned_name']) : '<i class="fa-regular fa-user"></i> Nieprzypisany' ?></span>
                <span style="color:<?= $c['deadline'] && strtotime($c['deadline']) < time() && $c['status'] !== 'Done' ? 'var(--danger)' : 'var(--text-muted)' ?>">
                    <?= $c['deadline'] ? '<i class="fa-regular fa-clock"></i> '.date('d.m', strtotime($c['deadline'])) : '' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add task to column shortcut -->
        <button class="kanban-add-btn" onclick="openAddTaskModal('<?= $status ?>')">
            <i class="fa-solid fa-plus"></i> Dodaj
        </button>
    </div>
    <?php endforeach; ?>
</div>

<!-- Task Detail / Add Task Modal -->
<div class="modal-overlay" id="task-modal">
    <div class="modal-window" style="max-width:640px">
        <div class="modal-header">
            <h2 class="modal-title" id="task-modal-title">Nowe zadanie</h2>
            <button class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="task-id">
            <div class="form-group">
                <label class="form-label">Tytuł zadania *</label>
                <input class="form-control" type="text" id="task-name" placeholder="Co trzeba zrobić?">
            </div>
            <div class="form-group">
                <label class="form-label">Opis</label>
                <textarea class="form-control" id="task-desc" rows="3" placeholder="Szczegóły, wymagania..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Projekt *</label>
                    <select class="form-control" id="task-project">
                        <option value="">-- Wybierz projekt --</option>
                        <?php foreach ($user_projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_project == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Przypisz do</label>
                    <select class="form-control" id="task-assign">
                        <option value="">-- Nieprzypisany --</option>
                        <?php foreach ($all_users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= sanitize($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Priorytet</label>
                    <select class="form-control" id="task-priority">
                        <option value="Low">🟢 Niski</option>
                        <option value="Medium" selected>🔵 Średni</option>
                        <option value="High">🟡 Wysoki</option>
                        <option value="Critical">🔴 Krytyczny</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="task-status">
                        <option value="To Do">To Do</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Review">Review</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Termin (deadline)</label>
                <input class="form-control" type="date" id="task-deadline">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="task-delete-btn" onclick="deleteCurrentTask()" style="width:auto;display:none"><i class="fa-solid fa-trash"></i> Usuń</button>
            <button class="btn btn-secondary" onclick="closeTaskModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="saveTask()" style="width:auto" id="task-save-btn">
                <i class="fa-solid fa-floppy-disk"></i> Zapisz
            </button>
        </div>
    </div>
</div>

<script>
// ── Drag & Drop ──────────────────────────────────────────
let draggedId = null;

function dragStart(e) {
    draggedId = e.currentTarget.dataset.id;
    e.currentTarget.style.opacity = '.5';
}
function dragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}
function dragLeave(e) { e.currentTarget.classList.remove('drag-over'); }

async function drop(e, newStatus) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    if (!draggedId) return;

    const card = document.getElementById('task-' + draggedId);
    const oldStatus = card?.dataset.status;
    if (oldStatus === newStatus) { if (card) card.style.opacity = '1'; return; }

    // Optimistic UI: move card
    const col = document.getElementById('col-' + newStatus.replace(/ /g, '-').toLowerCase());
    const addBtn = col?.querySelector('.kanban-add-btn');
    if (card && col) { col.insertBefore(card, addBtn); card.dataset.status = newStatus; card.style.opacity = '1'; }
    updateColumnCounts();

    const json = await apiPost('/api/tasks.php?action=update_status', { id: draggedId, status: newStatus });
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

// ── Search & Priority Filter ──────────────────────────────
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
    document.querySelectorAll('.tasks-filters .filter-tab').forEach(b => b.classList.remove('active'));
    btn?.classList.add('active');
    filterTasks(document.getElementById('task-search')?.value || '');
}

// ── Task Modal ────────────────────────────────────────────
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
    const json = await apiGet('/api/tasks.php?action=get&id=' + id);
    if (!json?.task) { Toast.error('Nie udało się załadować zadania.'); return; }
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
    if (!name) { Toast.error('Podaj tytuł zadania.'); return; }
    if (!project_id) { Toast.error('Wybierz projekt.'); return; }

    const btn = document.getElementById('task-save-btn');
    btn.disabled = true;

    const payload = {
        id: editingTaskId,
        name,
        description: document.getElementById('task-desc').value,
        project_id,
        assigned_to: document.getElementById('task-assign').value || null,
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
        const json = await apiPost('/api/tasks.php?action=delete', { id: editingTaskId });
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

// Auto-open task from URL
<?php if ($open_task_id): ?>
document.addEventListener('DOMContentLoaded', () => openTaskDetail(<?= $open_task_id ?>));
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
