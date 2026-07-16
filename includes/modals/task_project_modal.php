<!-- Create Project Modal -->
<div class="modal-overlay" id="project-modal">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Nowy projekt</h2>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nazwa projektu *</label>
                <input class="form-control" type="text" id="project-name" placeholder="Nazwa" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Opis</label>
                <textarea class="form-control" id="project-description" rows="2" placeholder="Krótki opis..." maxlength="1000"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kolor</label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php $colors = ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#f97316']; ?>
                    <?php foreach ($colors as $c): ?>
                    <div style="width: 40px; height: 40px; background: <?= $c ?>; border-radius: 8px; cursor: pointer; border: 3px solid transparent; transition: all 0.2s;" onclick="selectProjectColor('<?= $c ?>')" id="color-<?= md5($c) ?>"></div>
                    <?php endforeach; ?>
                    <input type="hidden" id="project-color" value="#3b82f6">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Termin (opcjonalnie)</label>
                <input class="form-control" type="date" id="project-deadline">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateProjectModal()">Anuluj</button>
            <button class="btn btn-primary" onclick="saveNewProject()"><i class="fa-solid fa-plus"></i> Utwórz</button>
        </div>
    </div>
</div>