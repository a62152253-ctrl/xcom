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

<link rel="stylesheet" href="/assets/css/tasks.css">

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

<?php require_once __DIR__ . '/../includes/components/tasks_kanban.php'; ?>

<?php require_once __DIR__ . '/../includes/modals/task_project_modal.php'; ?>

<?php require_once __DIR__ . '/../includes/modals/task_modal.php'; ?>


<script>
<?php if ($open_task_id): ?>
document.addEventListener('DOMContentLoaded', () => openTaskDetail(<?= $open_task_id ?>));
<?php endif; ?>
</script>
<script src="/assets/js/tasks.js"></script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
