<?php
// pages/team.php — Workspace sharing & team management
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// All users in the system (visible workspace members)
$stmt_members = $db->query("
    SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status != 'Done') as open_tasks,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'Done') as done_tasks,
        (SELECT MAX(created_at) FROM activity_logs WHERE user_id = u.id) as last_active
    FROM users u ORDER BY u.role ASC, u.full_name ASC
");
$members = $stmt_members->fetchAll();

// Pending invites
$stmt_invites = $db->prepare("
    SELECT * FROM workspace_invites WHERE invited_by = ? AND status = 'pending' AND expires_at > NOW()
    ORDER BY created_at DESC
");
$stmt_invites->execute([$user_id]);
$pending_invites = $stmt_invites->fetchAll();

$can_invite = in_array($_SESSION['user_role'], ['Owner', 'Administrator']);

// Shared projects count
$stmt_shared = $db->prepare("
    SELECT COUNT(DISTINCT p.id) FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
");
$stmt_shared->execute([$user_id, $user_id]);
$shared_projects = $stmt_shared->fetchColumn();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-users"></i> Workspace — Twój Zespół</h1>
        <p class="page-subtitle">Współdzielicie jedno środowisko pracy. Widzicie te same projekty i zadania.</p>
    </div>
    <?php if ($can_invite): ?>
    <button class="btn btn-primary" onclick="openInviteModal()">
        <i class="fa-solid fa-user-plus"></i> Zaproś użytkownika
    </button>
    <?php endif; ?>
</div>

<!-- Workspace stats -->
<div class="kpi-strip" style="margin-bottom:1.5rem">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="kpi-value"><?= count($members) ?></div>
            <div class="kpi-label">Członków workspace</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(16,185,129,.15);color:#10b981"><i class="fa-solid fa-folder-open"></i></div>
        <div>
            <div class="kpi-value"><?= $shared_projects ?></div>
            <div class="kpi-label">Wspólnych projektów</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(245,158,11,.15);color:#f59e0b"><i class="fa-solid fa-envelope"></i></div>
        <div>
            <div class="kpi-value"><?= count($pending_invites) ?></div>
            <div class="kpi-label">Oczekujące zaproszenia</div>
        </div>
    </div>
</div>

<!-- Team members grid -->
<div class="team-grid">
    <?php foreach ($members as $m):
        $initials = strtoupper(substr($m['full_name'], 0, 1));
        $is_me = $m['id'] == $user_id;
        $last_active = $m['last_active'] ? date('d.m.Y H:i', strtotime($m['last_active'])) : 'Brak aktywności';
        $total_t = $m['open_tasks'] + $m['done_tasks'];
        $pct = $total_t > 0 ? round($m['done_tasks'] / $total_t * 100) : 0;
        $role_class = ['Owner'=>'badge-owner','Administrator'=>'badge-admin','Member'=>'badge-member'][$m['role']] ?? 'badge-member';
    ?>
    <div class="team-card <?= $is_me ? 'team-card--me' : '' ?>">
        <?php if ($is_me): ?><div class="team-me-label">Ty</div><?php endif; ?>
        <div class="team-avatar-wrap">
            <?php if (!empty($m['avatar'])): ?>
                <img src="<?= sanitize($m['avatar']) ?>" class="team-avatar-img">
            <?php else: ?>
                <div class="team-avatar-circle"><?= $initials ?></div>
            <?php endif; ?>
            <div class="team-status-dot <?= $m['status'] === 'Active' ? 'status-active' : 'status-blocked' ?>"></div>
        </div>
        <h3 class="team-name"><?= sanitize($m['full_name']) ?></h3>
        <p class="team-email"><?= sanitize($m['email']) ?></p>
        <span class="badge-pill <?= $role_class ?>"><?= $m['role'] ?></span>

        <div class="team-stats">
            <div class="team-stat">
                <strong><?= $m['open_tasks'] ?></strong>
                <span>Otwarte</span>
            </div>
            <div class="team-stat">
                <strong><?= $m['done_tasks'] ?></strong>
                <span>Ukończone</span>
            </div>
            <div class="team-stat">
                <strong><?= $pct ?>%</strong>
                <span>Skuteczność</span>
            </div>
        </div>

        <div class="team-progress-bar">
            <div style="width:<?= $pct ?>%;background:var(--primary)"></div>
        </div>

        <div class="team-last-active">
            <i class="fa-regular fa-clock"></i> <?= $last_active ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pending invites -->
<?php if ($can_invite && !empty($pending_invites)): ?>
<div class="card" style="margin-top:1.5rem">
    <h2 class="card-title"><i class="fa-solid fa-paper-plane"></i> Oczekujące zaproszenia</h2>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead><tr><th>Email</th><th>Rola</th><th>Wysłane</th><th>Wygasa</th><th>Akcja</th></tr></thead>
            <tbody>
            <?php foreach ($pending_invites as $inv): ?>
            <tr>
                <td><?= sanitize($inv['email']) ?></td>
                <td><span class="badge-pill badge-member"><?= $inv['role'] ?></span></td>
                <td><?= date('d.m.Y', strtotime($inv['created_at'])) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($inv['expires_at'])) ?></td>
                <td>
                    <button class="btn btn-secondary" style="padding:.25rem .75rem;font-size:.75rem;width:auto"
                        onclick="cancelInvite(<?= $inv['id'] ?>)">
                        <i class="fa-solid fa-times"></i> Anuluj
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Invite user -->
<?php if ($can_invite): ?>
<div class="modal-overlay" id="invite-modal">
    <div class="modal-window" style="max-width:480px">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-user-plus"></i> Zaproś do workspace</h2>
            <button class="modal-close" onclick="closeInviteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="invite-info-box">
                <i class="fa-solid fa-circle-info"></i>
                Zaproszony użytkownik dostanie email z linkiem. Po rejestracji automatycznie dołączy do workspace i będzie widział wszystkie wspólne projekty.
            </div>
            <div class="form-group">
                <label class="form-label">Adres e-mail</label>
                <input class="form-control" type="email" id="invite-email" placeholder="np. kolega@firma.pl">
            </div>
            <div class="form-group">
                <label class="form-label">Rola w workspace</label>
                <select class="form-control" id="invite-role">
                    <option value="Member">Member — może przeglądać i edytować zadania</option>
                    <?php if ($_SESSION['user_role'] === 'Owner'): ?>
                    <option value="Administrator">Administrator — może zarządzać użytkownikami</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeInviteModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="sendInvite()" style="width:auto">
                <i class="fa-solid fa-paper-plane"></i> Wyślij zaproszenie
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="team-toast" class="team-toast"></div>

<script>
function openInviteModal() {
    document.getElementById('invite-modal').classList.add('active');
}
function closeInviteModal() {
    document.getElementById('invite-modal').classList.remove('active');
}

async function sendInvite() {
    const email = document.getElementById('invite-email').value.trim();
    const role = document.getElementById('invite-role').value;
    if (!email) return showToast('Podaj adres e-mail', 'error');

    const res = await fetch('/api/team.php?action=invite', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ email, role })
    });
    const json = await res.json();
    if (json.success) {
        closeInviteModal();
        showToast('Zaproszenie wysłane do ' + email, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(json.error || 'Błąd wysyłania zaproszenia', 'error');
    }
}

async function cancelInvite(id) {
    if (!confirm('Anulować zaproszenie?')) return;
    const res = await fetch('/api/team.php?action=cancel_invite', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('team-toast');
    t.textContent = msg;
    t.className = 'team-toast team-toast--' + type + ' team-toast--visible';
    setTimeout(() => t.classList.remove('team-toast--visible'), 3000);
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeInviteModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
