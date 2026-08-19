<?php
// includes/components/project_card.php
// Przekazane zmienne z pętli nadrzędnej:
// $p - tablica asocjacyjna projektu
// $is_owner - flaga (bool) czy użytkownik jest właścicielem
// $pct - procent ukończenia projektu

$pct = $p['total_tasks'] > 0 ? round(($p['done_tasks'] / $p['total_tasks']) * 100) : 0;
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
