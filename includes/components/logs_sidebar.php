<!-- Sidebar Filters -->
    <aside class="logs-sidebar">
        <h3><i class="fa-solid fa-sliders"></i> Filtry</h3>

        <!-- Date Filter -->
        <div class="filter-group">
            <label class="filter-label">Data</label>
            <input type="date" class="filter-control" value="<?= $filter_date ?>" onchange="applyFilter('date', this.value)">
        </div>

        <!-- Action Quick Filters -->
        <div class="filter-group">
            <label class="filter-label">Typ Akcji</label>
            <button class="filter-btn <?= !$filter_action ? 'active' : '' ?>" onclick="applyFilter('action', '')">
                <i class="fa-solid fa-list"></i> Wszystkie
            </button>
            <button class="filter-btn <?= $filter_action === 'login' ? 'active' : '' ?>" onclick="applyFilter('action', 'login')">
                <i class="fa-solid fa-right-to-bracket"></i> Logowanie
            </button>
            <button class="filter-btn <?= $filter_action === 'task' ? 'active' : '' ?>" onclick="applyFilter('action', 'task')">
                <i class="fa-solid fa-list-check"></i> Zadania
            </button>
            <button class="filter-btn <?= $filter_action === 'project' ? 'active' : '' ?>" onclick="applyFilter('action', 'project')">
                <i class="fa-solid fa-folder"></i> Projekty
            </button>
            <button class="filter-btn <?= $filter_action === 'user' ? 'active' : '' ?>" onclick="applyFilter('action', 'user')">
                <i class="fa-solid fa-user"></i> Użytkownicy
            </button>
        </div>

        <!-- Clear Filters -->
        <button class="filter-btn" onclick="window.location.href='/pages/logs.php'" style="margin-top: 1rem; border-color: var(--border-color); color: var(--text-secondary);">
            <i class="fa-solid fa-redo"></i> Resetuj
        </button>
    </aside>