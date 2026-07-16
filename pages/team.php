<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get all team members with advanced stats
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM projects p WHERE p.created_by = u.id) as projects_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.created_by = u.id) as tasks_created,
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'Done') as tasks_active,
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status = 'Done') as tasks_done,
           (SELECT COUNT(*) FROM activity_logs l WHERE l.user_id = u.id AND l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as activity_week,
           u.last_login,
           CASE 
               WHEN u.last_login IS NULL THEN 'Nigdy'
               WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Aktywny'
               WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Niedawno'
               ELSE 'Nieaktywny'
           END as status_label
    FROM users u
    WHERE u.status = 'Active'
    ORDER BY u.last_login DESC
");
$stmt->execute();
$team_members = $stmt->fetchAll();

// Get pending invitations
$stmt_invites = $db->prepare("
    SELECT *, CASE 
        WHEN expires_at < NOW() THEN 'Wygasło'
        WHEN expires_at < DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 'Za 1 dzień'
        ELSE 'Oczekuje'
    END as status_label FROM workspace_invites 
    WHERE status = 'pending' 
    ORDER BY created_at DESC
");
$stmt_invites->execute();
$pending_invites = $stmt_invites->fetchAll();

// Get activity logs for team
$stmt_logs = $db->prepare("
    SELECT l.*, u.full_name, u.avatar
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 30
");
$stmt_logs->execute();
$activity_logs = $stmt_logs->fetchAll();

// Aggregated stats
$total_members = count($team_members);
$active_members = count(array_filter($team_members, fn($m) => $m['status_label'] === 'Aktywny'));
$total_projects = array_sum(array_column($team_members, 'projects_count'));
$total_tasks_done = array_sum(array_column($team_members, 'tasks_done'));

// Role colors
$role_colors = [
    'Owner' => ['bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#ef4444', 'icon' => 'fa-crown', 'badge' => 'rgba(239, 68, 68, 0.25)'],
    'Administrator' => ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#f59e0b', 'icon' => 'fa-shield', 'badge' => 'rgba(245, 158, 11, 0.25)'],
    'Member' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'color' => '#3b82f6', 'icon' => 'fa-user', 'badge' => 'rgba(59, 130, 246, 0.25)']
];

// Status colors
$status_colors = [
    'Aktywny' => '#10b981',
    'Niedawno' => '#f59e0b',
    'Nieaktywny' => '#9ca3af',
    'Nigdy' => '#9ca3af'
];
?>

<style>
.team-hero {
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    margin-bottom: 2.5rem;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 2rem;
    align-items: center;
}

.team-hero h1 {
    margin: 0;
    font-size: 2.25rem;
    font-weight: 700;
}

.team-hero-actions {
    display: flex;
    gap: 1rem;
}

.team-hero-actions .btn {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
    border: 1px solid rgba(255,255,255,0.3);
}

.team-hero-actions .btn:hover {
    background: rgba(255,255,255,0.3) !important;
}

.team-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), #06b6d4);
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 600;
}

.members-container {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
}

.members-main {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.members-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

.members-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
}

.filter-controls {
    display: flex;
    gap: 0.75rem;
}

.filter-btn {
    padding: 0.6rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    font-weight: 600;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.member-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
}

.member-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.member-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.member-card:hover {
    transform: translateY(-4px);
    background: var(--bg-primary);
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
}

.member-card:hover::before {
    transform: scaleX(1);
}

.member-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.member-avatar-lg {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.member-card-title {
    flex: 1;
}

.member-name {
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.member-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.member-card-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
}

.card-stat {
    text-align: center;
}

.card-stat-number {
    font-weight: 700;
    color: var(--primary);
    font-size: 1.1rem;
}

.card-stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.member-card-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.card-action-btn {
    flex: 1;
    padding: 0.6rem;
    border: 1px solid var(--border-color);
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.card-action-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.sidebar-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.sidebar-card h3 {
    margin: 0 0 1rem 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.invite-item-card {
    padding: 1rem;
    background: var(--bg-secondary);
    border-left: 4px solid var(--warning);
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.invite-email-card {
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-all;
}

.invite-meta-card {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.35rem;
}

.activity-feed {
    max-height: 600px;
    overflow-y: auto;
}

.activity-item-card {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    gap: 0.75rem;
    font-size: 0.85rem;
}

.activity-item-card:last-child {
    border-bottom: none;
}

.activity-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.activity-content-card {
    flex: 1;
}

.activity-user-card {
    font-weight: 600;
    color: var(--text-primary);
}

.activity-time-card {
    color: var(--text-muted);
    font-size: 0.75rem;
}

@media (max-width: 1200px) {
    .members-container {
        grid-template-columns: 1fr;
    }

    .member-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .team-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .team-hero {
        grid-template-columns: 1fr;
    }

    .member-grid {
        grid-template-columns: 1fr;
    }

    .team-stats-grid {
        grid-template-columns: 1fr;
    }

    .members-main {
        padding: 1rem;
    }
}
</style>

<!-- Hero Section -->
<div class="team-hero animate-fade">
    <div>
        <h1><i class="fa-solid fa-users-gear"></i> Zarządzanie Zespołem</h1>
        <p style="margin: 0.5rem 0 0 0; opacity: 0.95;">Pełna kontrola nad członkami, rolami i uprawnieniami zespołu.</p>
    </div>
    <?php if ($user_role === 'Owner' || $user_role === 'Administrator'): ?>
    <div class="team-hero-actions">
        <button class="btn btn-primary" onclick="openInviteModal()" style="background: rgba(255,255,255,0.2) !important;">
            <i class="fa-solid fa-user-plus"></i> Zaproś
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div class="team-stats-grid animate-slide-up">
    <div class="stat-card">
        <div class="stat-number"><?= $total_members ?></div>
        <div class="stat-label">Członków Ogółem</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $active_members ?></div>
        <div class="stat-label">Aktywnych</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $total_projects ?></div>
        <div class="stat-label">Projektów</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $total_tasks_done ?></div>
        <div class="stat-label">Zadań Ukończonych</div>
    </div>
</div>

<!-- Main Content -->
<div class="members-container">
    <!-- Members Grid -->
    <div class="members-main">
        <div class="members-header">
            <h2><i class="fa-solid fa-people-group"></i> Członkowie zespołu</h2>
            <div class="filter-controls">
                <button class="filter-btn active" onclick="filterByStatus('all', this)">Wszyscy</button>
                <button class="filter-btn" onclick="filterByStatus('Aktywny', this)">Aktywni</button>
                <button class="filter-btn" onclick="filterByStatus('Niedawno', this)">Niedawno</button>
            </div>
        </div>

        <div class="member-grid" id="member-grid">
            <?php foreach ($team_members as $member): ?>
            <div class="member-card" data-status="<?= $member['status_label'] ?>" onclick="openMemberModal(<?= $member['id'] ?>)">
                <div class="member-card-header">
                    <div class="member-avatar-lg"><?= strtoupper(substr($member['full_name'], 0, 1)) ?></div>
                    <div class="member-card-title">
                        <p class="member-name"><?= sanitize($member['full_name']) ?></p>
                        <div class="member-role-badge" style="background: <?= $role_colors[$member['role']]['bg'] ?>; color: <?= $role_colors[$member['role']]['color'] ?>;">
                            <i class="fa-solid <?= $role_colors[$member['role']]['icon'] ?>"></i>
                            <?= $member['role'] ?>
                        </div>
                    </div>
                </div>

                <div class="member-card-stats">
                    <div class="card-stat">
                        <div class="card-stat-number"><?= $member['projects_count'] ?></div>
                        <div class="card-stat-label">Projekty</div>
                    </div>
                    <div class="card-stat">
                        <div class="card-stat-number"><?= $member['tasks_done'] ?></div>
                        <div class="card-stat-label">Zrobione</div>
                    </div>
                    <div class="card-stat">
                        <div class="card-stat-number"><?= $member['tasks_active'] ?></div>
                        <div class="card-stat-label">W toku</div>
                    </div>
                    <div class="card-stat">
                        <div class="card-stat-number"><?= $member['activity_week'] ?></div>
                        <div class="card-stat-label">Aktywny</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem;">
                    <div>
                        <span class="status-badge" style="background: <?= $status_colors[$member['status_label']] ?>20; color: <?= $status_colors[$member['status_label']] ?>;">
                            <span class="status-dot" style="background: <?= $status_colors[$member['status_label']] ?>;"></span>
                            <?= $member['status_label'] ?>
                        </span>
                    </div>
                    <div style="color: var(--text-muted);">
                        <?php 
                        if ($member['last_login']) {
                            echo '<i class="fa-regular fa-clock"></i> ' . date('d.m', strtotime($member['last_login']));
                        } else {
                            echo 'Nigdy';
                        }
                        ?>
                    </div>
                </div>

                <?php if ($user_role === 'Owner' && $member['id'] !== $user_id): ?>
                <div class="member-card-actions">
                    <?php if ($member['role'] !== 'Owner'): ?>
                    <button class="card-action-btn" onclick="promoteMember(event, <?= $member['id'] ?>)" title="Promuj">
                        <i class="fa-solid fa-arrow-up"></i> Promuj
                    </button>
                    <?php endif; ?>
                    <button class="card-action-btn" onclick="removeMember(event, <?= $member['id'] ?>)" style="color: var(--danger);" title="Usuń">
                        <i class="fa-solid fa-trash"></i> Usuń
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Invitations -->
        <?php if (($user_role === 'Owner' || $user_role === 'Administrator') && !empty($pending_invites)): ?>
        <div class="sidebar-card">
            <h3><i class="fa-solid fa-paper-plane"></i> Zaproszenia (<?= count($pending_invites) ?>)</h3>
            <?php foreach (array_slice($pending_invites, 0, 5) as $inv): ?>
            <div class="invite-item-card">
                <div class="invite-email-card"><?= sanitize($inv['email']) ?></div>
                <div class="invite-meta-card">
                    <i class="fa-solid fa-hourglass-end"></i>
                    <?= $inv['status_label'] ?> • <?= date('d.m', strtotime($inv['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Activity Feed -->
        <div class="sidebar-card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Aktywność (7 dni)</h3>
            <div class="activity-feed">
                <?php if (empty($activity_logs)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 1rem; font-size: 0.9rem;">Brak aktywności</p>
                <?php else: ?>
                    <?php foreach (array_slice($activity_logs, 0, 12) as $log): ?>
                    <div class="activity-item-card">
                        <div class="activity-avatar-sm"><?= strtoupper(substr($log['full_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="activity-content-card">
                            <div class="activity-user-card"><?= sanitize($log['full_name'] ?? 'System') ?></div>
                            <div style="color: var(--text-muted);">
                                <?= sanitize(mb_substr($log['action'], 0, 35)) ?>
                            </div>
                            <div class="activity-time-card">
                                <i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($log['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Invite Modal -->
<div class="modal-overlay" id="invite-modal">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-user-plus"></i> Zaproś do zespołu</h2>
            <button class="modal-close" onclick="closeInviteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Adres e-mail *</label>
                <input class="form-control" type="email" id="invite-email" placeholder="email@example.com" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Rola</label>
                <select class="form-control" id="invite-role">
                    <option value="Member"><i class="fa-solid fa-user"></i> Członek</option>
                    <option value="Administrator"><i class="fa-solid fa-shield"></i> Administrator</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeInviteModal()">Anuluj</button>
            <button class="btn btn-primary" onclick="sendInvite()"><i class="fa-solid fa-paper-plane"></i> Wyślij zaproszenie</button>
        </div>
    </div>
</div>

<script>
function filterByStatus(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    document.querySelectorAll('.member-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function openInviteModal() {
    document.getElementById('invite-email').value = '';
    document.getElementById('invite-role').value = 'Member';
    document.getElementById('invite-modal').classList.add('active');
    document.getElementById('invite-email').focus();
}

function closeInviteModal() {
    document.getElementById('invite-modal').classList.remove('active');
}

async function sendInvite() {
    const email = document.getElementById('invite-email').value.trim();
    const role = document.getElementById('invite-role').value;

    if (!email || !email.includes('@')) {
        Toast.error('Podaj prawidłowy adres e-mail.');
        return;
    }

    const btn = document.querySelector('#invite-modal .btn-primary');
    btn.disabled = true;

    const payload = { email, role };
    const json = await apiPost('/api/team.php?action=invite', payload);
    
    btn.disabled = false;
    if (json.success) {
        Toast.success('Zaproszenie wysłane!');
        closeInviteModal();
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error(json.error || 'Błąd wysyłania zaproszenia');
    }
}

function promoteMember(e, id) {
    e.stopPropagation();
    confirmDialog('Promować tego użytkownika na Administratora?', async () => {
        const json = await apiPost('/api/team.php?action=promote', { user_id: parseInt(id) });
        if (json.success) {
            Toast.success('Użytkownik promowany!');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.error || 'Błąd promocji');
        }
    });
}

function removeMember(e, id) {
    e.stopPropagation();
    confirmDialog('Usunąć tego użytkownika z zespołu?', async () => {
        const json = await apiPost('/api/team.php?action=remove', { user_id: parseInt(id) });
        if (json.success) {
            Toast.success('Użytkownik usunięty!');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.error || 'Błąd usuwania');
        }
    });
}

function openMemberModal(id) {
    // Opens member detail modal (future enhancement)
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
