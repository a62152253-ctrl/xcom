<?php
// includes/components/topbar.php
?>
<header class="top-bar">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="global-search" placeholder="Szukaj zadań, projektów..." onkeyup="handleGlobalSearch(this.value)">
                    <div id="search-results" style="display:none;position:absolute;top:45px;left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:1000;max-height:250px;overflow-y:auto;padding:.5rem"></div>
                </div>

                <div class="top-actions">
                    <!-- Command Palette hint -->
                    <div class="cmd-hint" onclick="openCommandPalette()">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Szukaj</span>
                        <kbd>Ctrl</kbd><kbd>K</kbd>
                    </div>

                    <!-- Quick actions -->
                    <div class="topbar-quick">
                        <button class="btn btn-primary" style="padding:5px 12px;font-size:12px" onclick="window.location.href='/pages/tasks.php'">
                            <i class="fa-solid fa-plus"></i> Zadanie
                        </button>
                        <button class="btn btn-secondary" style="padding:5px 12px;font-size:12px" onclick="window.location.href='/pages/projects.php'">
                            <i class="fa-solid fa-folder-plus"></i> Projekt
                        </button>
                    </div>

                    <!-- Theme toggle -->
                    <div class="theme-toggler" onclick="toggleTheme()" id="theme-toggle-btn">
                        <i class="fa-solid <?php echo ($_SESSION['user_theme'] ?? 'dark') === 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </div>

                    <!-- Notification Bell -->
                    <div class="notification-badge" onclick="toggleNotificationsDropdown()">
                        <i class="fa-solid fa-bell"></i>
                        <span class="badge" id="notif-count"><?php echo $unread_notifications_count; ?></span>
                    </div>
                </div>
            </header>
