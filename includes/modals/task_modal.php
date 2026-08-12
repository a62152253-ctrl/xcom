<!-- Task Modal -->
<div class="modal-overlay" id="task-modal">
    <div class="modal-window" style="max-width: 640px;">
        <div class="modal-header">
            <h2 class="modal-title" id="task-modal-title">Nowe zadanie</h2>
            <button type="button" class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="task-id-modal">
            <div class="form-group">
                <label class="form-label" for="task-name">Tytuł zadania *</label>
                <input class="form-control" type="text" id="task-name-modal" placeholder="Co trzeba zrobić?" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="project-description">Opis</label>
                <textarea class="form-control" id="task-desc-modal" rows="3" placeholder="Szczegóły, wymagania..." maxlength="5000"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="task-project">Projekt *</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <select class="form-control" id="task-project-modal" style="flex: 1;">
                            <option value="">-- Wybierz projekt --</option>
                            <?php foreach ($user_projects as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $filter_project == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="openCreateProjectModal()" style="padding: 0.75rem 1rem; font-size: 0.9rem;">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="task-assign">Przypisz do</label>
                    <select class="form-control" id="task-assign-modal">
                        <option value="">-- Nieprzypisany --</option>
                        <?php foreach ($all_users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= sanitize($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="task-priority">Priorytet</label>
                    <select class="form-control" id="task-priority-modal">
                        <option value="Low">🟢 Niski</option>
                        <option value="Medium" selected>🔵 Średni</option>
                        <option value="High">🟡 Wysoki</option>
                        <option value="Critical">🔴 Krytyczny</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="task-status">Status</label>
                    <select class="form-control" id="task-status-modal">
                        <option value="To Do">To Do</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Review">Review</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Termin (deadline)</label>
                <input class="form-control" type="date" id="task-deadline">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="task-delete-btn" onclick="deleteCurrentTask()" style="display: none;">
                <i class="fa-solid fa-trash"></i> Usuń
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Anuluj</button>
            <button type="button" class="btn btn-primary" onclick="saveTask()" id="task-save-btn">
                <i class="fa-solid fa-floppy-disk"></i> Zapisz
            </button>
        </div>
    </div>
</div>
