<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get all team members
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM projects p WHERE p.created_by = u.id) as projects_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.created_by = u.id) as tasks_count,
           u.last_login
    FROM users u
    WHERE u.status = 'Active'
    ORDER BY u.created_at ASC
");
$stmt->execute();
$team_members = $stmt->fetchAll();

// Get pending invitations
$stmt_invites = $db->prepare("SELECT * FROM workspace_invites WHERE status = 'pending' ORDER BY created_at DESC");
$stmt_invites->execute();
$pending_invites = $stmt_invites->fetchAll();

// Get activity logs for team
$stmt_logs = $db->prepare("
    SELECT l.*, u.full_name, u.avatar
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 20
");
$stmt_logs->execute();
$activity_logs = $stmt_logs->fetchAll();

// Role colors
$role_colors = [
    'Owner' => ['bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#ef4444', 'icon' => 'fa-crown'],
    'Administrator' => ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#f59e0b', 'icon' => 'fa-shield'],
    'Member' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'color' => '#3b82f6', 'icon' => 'fa-user']
];
?>

<style>
.team-header {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    margin-bottom: 2.5rem;
    gap: 2rem;
}

.team-header h1 {
    margin: 0;
    font-size: 2.25rem;
    font-weight: 700;
}

.team-header-subtitle {
    color: var(--text-muted);
    margin-top: 0.5rem;
}

.team-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.team-stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.team-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.team-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.team-stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 600;
}

.team-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.team-members-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.team-members-card h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.team-search {
    position: relative;
    margin-bottom: 1.5rem;
}

.team-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.team-search input {
    padding-left: 2.75rem !important;
}

.member-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.member-item {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--bg-secondary);
    border-radius: 10px;
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
    cursor: pointer;
}

.member-item:hover {
    background: var(--bg-tertiary);
    border-color: var(--primary);
    transform: translateX(4px);
}

.member-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.member-info h3 {
    margin: 0;
    font-weight: 600;
    color: var(--text-primary);
}

.member-email {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.member-meta {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    gap: 0.75rem;
}

.member-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
}

.member-actions {
    display: flex;
    gap: 0.5rem;
}

.member-actions button {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.member-actions button:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.sidebar-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.sidebar-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.invite-item {
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border-left: 4px solid var(--warning);
}

.invite-email {
    font-weight: 600;
    color: var(--text-primary);
}

.invite-status {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.activity-item {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    gap: 0.75rem;
    font-size: 0.85rem;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-action {
    font-weight: 600;
    color: var(--text-primary);
}

.activity-time {
    color: var(--text-muted);
    font-size: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-bottom: 0.75rem;
}

@media (max-width: 1024px) {
    .team-content {
        grid-template-columns: 1fr;
    }

    .team-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .member-item {
        grid-template-columns: auto 1fr;
    }

    .member-role-badge,
    .member-actions {
        grid-column: 2;
    }
}

@media (max-width: 640px) {
    .team-header {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .team-header h1 {
        font-size: 1.5rem;
    }

    .team-stats {
        grid-template-columns: 1fr;
    }

    .member-item {
        grid-template-columns: auto 1fr;
        gap: 0.75rem;
    }

    .member-actions button {
        padding: 0.4rem 0.5rem;
        font-size: 0.8rem;
    }
}
</style>

<!-- Page Header -->
<div class="team-header animate-fade">
    <div>
        <h1><i class="fa-solid fa-users"></i> Zespół</h1>
        <p class="team-header-subtitle">Zarządzaj członkami zespołu, rolami i uprawnieniami.</p>
    </div>
    <?php if ($user_role === 'Owner' || $user_role === 'Administrator'): ?>
    <button class="btn btn-primary" onclick="openInviteModal()">
        <i class="fa-solid fa-user-plus"></i> Zaproś do zespołu
    </button>
    <?php endif; ?>
</div>

<!-- Team Stats -->
<div class="team-stats animate-slide-up">
    <div class="team-stat-card">
        <div class="team-stat-number"><?= count($team_members) ?></div>
        <div class="team-stat-label">Członków</div>
    </div>
    <div class="team-stat-card">
        <div class="team-stat-number"><?= count($pending_invites) ?></div>
        <div class="team-stat-label">Zaproszenia</div>
    </div>
    <div class="team-stat-card">
        <div class="team-stat-number">
            <?php 
            $admins = array_filter($team_members, fn($m) => $m['role'] === 'Owner' || $m['role'] === 'Administrator');
            echo count($admins);
            ?>
        </div>
        <div class="team-stat-label">Administratorów</div>
    </div>
</div>

<!-- Main Content -->
<div class="team-content">
    <!-- Team Members -->
    <div class="team-members-card">
        <h2><i class="fa-solid fa-people-group"></i> Członkowie zespołu</h2>
        
        <div class="team-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="form-control" placeholder="Szukaj członków..." id="member-search" oninput="filterMembers(this.value)">
        </div>

        <div class="member-list" id="member-list">
            <?php if (empty($team_members)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-user"></i>
                <p>Brak członków zespołu</p>
            </div>
            <?php else: ?>
                <?php foreach ($team_members as $member): ?>
                <div class="member-item" data-search="<?= strtolower(sanitize($member['full_name'] . ' ' . $member['email'])) ?>">
                    <div class="member-avatar"><?= strtoupper(substr($member['full_name'], 0, 1)) ?></div>
                    
                    <div class="member-info">
                        <h3><?= sanitize($member['full_name']) ?></h3>
                        <div class="member-email"><?= sanitize($member['email']) ?></div>
                        <div class="member-meta">
                            <span><i class="fa-solid fa-folder"></i> <?= $member['projects_count'] ?> projektów</span>
                            <span><i class="fa-solid fa-list-check"></i> <?= $member['tasks_count'] ?> zadań</span>
                        </div>
                    </div>

                    <div class="member-role-badge" style="background: <?= $role_colors[$member['role']]['bg'] ?>; color: <?= $role_colors[$member['role']]['color'] ?>;">
                        <i class="fa-solid <?= $role_colors[$member['role']]['icon'] ?>"></i>
                        <?= $member['role'] ?>
                    </div>

                    <?php if ($user_role === 'Owner' && $member['id'] !== $user_id): ?>
                    <div class="member-actions">
                        <?php if ($member['role'] !== 'Owner'): ?>
                        <button onclick="promoteMember(<?= $member['id'] ?>)" title="Promuj">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <?php endif; ?>
                        <button onclick="removeMember(<?= $member['id'] ?>)" title="Usuń" style="color: var(--danger);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Pending Invitations -->
        <?php if ($user_role === 'Owner' || $user_role === 'Administrator'): ?>
        <div class="sidebar-section">
            <h3><i class="fa-solid fa-paper-plane"></i> Zaproszenia (<?= count($pending_invites) ?>)</h3>
            
            <?php if (empty($pending_invites)): ?>
            <div class="empty-state" style="padding: 1rem;">
                <p style="font-size: 0.9rem;">Brak zaproszonych użytkowników</p>
            </div>
            <?php else: ?>
                <?php foreach ($pending_invites as $inv): ?>
                <div class="invite-item">
                    <div class="invite-email"><?= sanitize($inv['email']) ?></div>
                    <div class="invite-status">
                        <i class="fa-solid fa-hourglass-end"></i>
                        Oczekuje (<?= date('d.m.Y', strtotime($inv['created_at'])) ?>)
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="sidebar-section">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Ostatnia aktywność</h3>
            
            <?php if (empty($activity_logs)): ?>
            <div class="empty-state" style="padding: 1rem;">
                <p style="font-size: 0.9rem;">Brak aktywności</p>
            </div>
            <?php else: ?>
                <?php foreach (array_slice($activity_logs, 0, 8) as $log): ?>
                <div class="activity-item">
                    <div class="activity-avatar"><?= strtoupper(substr($log['full_name'] ?? 'S', 0, 1)) ?></div>
                    <div class="activity-content">
                        <div class="activity-action"><?= sanitize($log['full_name'] ?? 'System') ?></div>
                        <div class="activity-time">
                            <?= sanitize(mb_substr($log['action'], 0, 40)) ?>
                            • <?= date('H:i', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Invite Modal -->
<div class="modal-overlay" id="invite-modal">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Zaproś do zespołu</h2>
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
                    <option value="Member">Członek</option>
                    <option value="Administrator">Administrator</option>
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
function filterMembers(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.member-item').forEach(item => {
        const search = item.dataset.search || '';
        item.style.display = search.includes(q) ? '' : 'none';
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

async function promoteMember(id) {
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

async function removeMember(id) {
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
