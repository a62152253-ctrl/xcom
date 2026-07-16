<!-- Event Modal -->
<div class="modal-overlay" id="event-modal">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title" id="event-modal-title">Nowe wydarzenie</h2>
            <button class="modal-close" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="event-id">
            <div class="form-group">
                <label class="form-label">Tytuł *</label>
                <input class="form-control" type="text" id="event-title" name="event-title" placeholder="Co się będzie dziać?" maxlength="255">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Data *</label>
                    <input class="form-control" type="date" id="event-date" name="event-date">
                </div>
                <div class="form-group">
                    <label class="form-label">Czas</label>
                    <input class="form-control" type="time" id="event-time" name="event-time">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Opis</label>
                <textarea class="form-control" id="event-description" name="event-description" rows="3" placeholder="Szczegóły..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="event-delete-btn" onclick="deleteEvent()" style="display: none;">
                <i class="fa-solid fa-trash"></i> Usuń
            </button>
            <button class="btn btn-secondary" onclick="closeEventModal()">Anuluj</button>
            <button class="btn btn-primary" onclick="saveEvent()">
                <i class="fa-solid fa-floppy-disk"></i> Zapisz
            </button>
        </div>
    </div>
</div>
