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