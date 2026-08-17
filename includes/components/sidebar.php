<?php
// includes/components/sidebar.php
?>
<aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <a href="/pages/dashboard.php" class="sidebar-logo">
                    <i class="fa-solid fa-square-check"></i>
                    <span>TaskManager</span>
                </a>
            </div>

            <!-- Workspace Badge -->
            <div class="workspace-badge" onclick="openCommandPalette()" title="Ctrl+K — Command Palette">
                <div class="workspace-avatar">T</div>
                <div class="workspace-info">
                    <div class="workspace-name"><?php echo sanitize($user_name); ?>'s Workspace</div>
                    <div class="workspace-plan">Pro · <?= $ws_members ?> członków</div>
                </div>
                <i class="fa-solid fa-chevron-down workspace-chevron"></i>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">Główne</span>
                <a href="/pages/dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span><?php echo __('dashboard'); ?></span>
                </a>
                <a href="/pages/projects.php" class="nav-item <?php echo $current_page == 'projects.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?php echo __('projects'); ?></span>
                    <?php if ($ws_projects > 0): ?>
                    <span class="nav-badge" style="background:var(--primary)"><?= $ws_projects ?></span>
                    <?php endif; ?>
                </a>
                <a href="/pages/tasks.php" class="nav-item <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span><?php echo __('tasks'); ?></span>
                </a>
                <a href="/pages/calendar.php" class="nav-item <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span><?php echo __('calendar'); ?></span>
                </a>

                <span class="nav-section-label">Analizy</span>
                <a href="/pages/reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-bar"></i>
                    <span>Raporty</span>
                </a>
                <a href="/pages/logs.php" class="nav-item <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-history"></i>
                    <span>Logi</span>
                </a>

                <span class="nav-section-label">Zasoby</span>
                <a href="/pages/notes.php" class="nav-item <?php echo $current_page == 'notes.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>Notatki</span>
                </a>
                <a href="/pages/files.php" class="nav-item <?php echo $current_page == 'files.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder"></i>
                    <span>Pliki</span>
                </a>
                <a href="/pages/archive.php" class="nav-item <?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archiwum</span>
                </a>

                <span class="nav-section-label">Konto</span>
                <a href="/pages/notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Powiadomienia</span>
                    <?php if ($unread_notifications_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_notifications_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/pages/profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i>
                    <span><?php echo __('profile'); ?></span>
                </a>
                <a href="/pages/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-sliders"></i>
                    <span><?php echo __('settings'); ?></span>
                </a>
                <?php if ($user_role === 'Owner' || $user_role === 'Administrator'): ?>
                <a href="/pages/admin.php" class="nav-item <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span><?php echo __('admin'); ?></span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo sanitize($user_name); ?></div>
                    <div class="user-role"><?php echo sanitize($user_role); ?></div>
                </div>
                <a href="/auth/logout.php" title="<?php echo __('logout'); ?>" style="color:var(--text-secondary)">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>
