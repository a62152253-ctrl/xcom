<div class="modal-overlay" id="create-project-modal">
    <div class="modal-window">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-folder-plus" style="color:var(--primary)"></i> Nowy projekt</h2>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nazwa projektu *</label>
                <input class="form-control" type="text" id="project-name" placeholder="np. Redesign strony www" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Opis projektu</label>
                <textarea class="form-control" id="project-desc" rows="3" placeholder="Krótki opis celów projektu..." maxlength="1000"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kolor identyfikacyjny</label>
                    <input class="form-control" type="color" id="project-color" value="#3b82f6" style="height:44px;padding:.15rem;cursor:pointer">
                </div>
                <div class="form-group">
                    <label class="form-label">Termin zakończenia</label>
                    <input class="form-control" type="date" id="project-deadline">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateProjectModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="submitCreateProject()" style="width:auto" id="create-proj-btn">
                <i class="fa-solid fa-plus"></i> Stwórz projekt
            </button>
        </div>
    </div>
</div>
