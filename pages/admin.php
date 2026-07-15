<?php
// pages/admin.php
require_once __DIR__ . '/../includes/header.php';

// Only allow Owner and Administrator
require_role(['Owner', 'Administrator']);

$db = Database::getInstance()->getConnection();

// Fetch all users
$stmt_users = $db->query("SELECT id, email, full_name, role, status, created_at FROM users ORDER BY id ASC");
$users = $stmt_users->fetchAll();

// Fetch all activity logs (latest 50)
$stmt_logs = $db->query("
    SELECT l.*, u.full_name as user_name 
    FROM activity_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC LIMIT 50
");
$all_logs = $stmt_logs->fetchAll();
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.75rem; font-weight: 700;">Panel Administratora</h1>
    <p style="color: var(--text-secondary);">Zarządzaj użytkownikami, rolami, kopiami zapasowymi bazy danych oraz przeglądaj logi bezpieczeństwa.</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start; margin-bottom: 2rem;">
    <!-- User Management Table -->
    <div class="card">
        <h2 class="card-title"><i class="fa-solid fa-users"></i> Zarządzanie użytkownikami</h2>
        <div class="custom-table-container">
            <table class="custom-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Użytkownik</th>
                        <th>E-mail</th>
                        <th>Rola</th>
                        <th>Status</th>
                        <th style="text-align: right;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo sanitize($u['full_name']); ?></td>
                            <td><?php echo sanitize($u['email']); ?></td>
                            <td>
                                <select class="form-control" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; width: auto;" onchange="changeUserRole(<?php echo $u['id']; ?>, this.value)" <?php echo $_SESSION['user_role'] !== 'Owner' ? 'disabled' : ''; ?>>
                                    <option value="Member" <?php echo $u['role'] === 'Member' ? 'selected' : ''; ?>>Member</option>
                                    <option value="Administrator" <?php echo $u['role'] === 'Administrator' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="Owner" <?php echo $u['role'] === 'Owner' ? 'selected' : ''; ?>>Owner</option>
                                </select>
                            </td>
                            <td>
                                <span class="kanban-card-tag <?php echo $u['status'] === 'Active' ? 'tag-low' : 'tag-critical'; ?>" style="font-size: 0.7rem; border-radius: 4px;">
                                    <?php echo $u['status']; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'Owner'): ?>
                                    <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: auto;" onclick="toggleUserStatus(<?php echo $u['id']; ?>, '<?php echo $u['status'] === 'Active' ? 'Blocked' : 'Active'; ?>')">
                                        <?php echo $u['status'] === 'Active' ? 'Zablokuj' : 'Odblokuj'; ?>
                                    </button>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">Brak akcji</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Backup Operations Card -->
    <div class="card">
        <h2 class="card-title"><i class="fa-solid fa-database"></i> Kopia zapasowa</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
            Pobierz pełny zrzut struktury i danych z bazy MySQL bezpośrednio do pliku SQL w przeglądarce.
        </p>
        <a class="btn btn-primary" href="/api/admin.php?action=backup" style="text-align: center;">
            <i class="fa-solid fa-cloud-arrow-down"></i> Utwórz i pobierz backup
        </a>
    </div>
</div>

<!-- Global System logs -->
<div class="card">
    <h2 class="card-title"><i class="fa-solid fa-shield-halved"></i> Logi systemowe</h2>
    <div style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 0.8rem; display: flex; flex-direction: column; gap: 0.5rem; background: var(--bg-primary); padding: 1rem; border-radius: var(--radius-md);">
        <?php foreach ($all_logs as $log): ?>
            <div>
                <span style="color: var(--text-muted);">[<?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>]</span>
                <span style="color: var(--primary); font-weight: 600;">[IP: <?php echo sanitize($log['ip_address'] ?? '127.0.0.1'); ?>]</span>
                <span style="color: var(--success);">[<?php echo sanitize($log['user_name'] ?? 'System'); ?>]</span>
                <strong><?php echo sanitize($log['action']); ?></strong>:
                <span style="color: var(--text-secondary);"><?php echo sanitize($log['details']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function toggleUserStatus(userId, newStatus) {
        if (confirm(`Czy na pewno chcesz zmienić status użytkownika na ${newStatus}?`)) {
            fetch('/api/admin.php?action=toggle_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Błąd zapisu statusu');
                }
            });
        }
    }

    function changeUserRole(userId, newRole) {
        fetch('/api/admin.php?action=change_role', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, role: newRole })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Rola została zaktualizowana!');
                location.reload();
            } else {
                alert(data.error || 'Błąd zapisu roli');
                location.reload();
            }
        });
    }
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
