<div class="modal-overlay" id="add-member-modal">
    <div class="modal-window" style="max-width:480px">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Dodaj członka</h2>
            <button class="modal-close" onclick="closeAddMemberModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="member-project-id">
            <div class="form-group">
                <label class="form-label">Adres e-mail użytkownika</label>
                <input class="form-control" type="email" id="member-email" placeholder="np. kolega@firma.pl" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Rola w projekcie</label>
                <select class="form-control" id="member-role">
                    <option value="Member">Member</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddMemberModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="submitAddMember()" style="width:auto">Dodaj</button>
        </div>
    </div>
</div>
