<div class="modal-overlay" id="event-modal">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title" id="event-modal-title">Nowe wydarzenie</h2>
            <button type="button" class="modal-close" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="event-id">
            <div class="form-group">
                <label class="form-label" for="event-title">Tytuł *</label>
                <input class="form-control" type="text" id="event-title" placeholder="Co się będzie dziać?" maxlength="255">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="event-date">Data *</label>
                    <input class="form-control" type="date" id="event-date">
                </div>
                <div class="form-group">
                    <label class="form-label" for="event-time">Czas</label>
                    <input class="form-control" type="time" id="event-time">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Opis</label>
                <textarea class="form-control" id="event-description" rows="3" placeholder="Szczegóły..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="event-delete-btn" onclick="deleteEvent()" style="display: none;">
                <i class="fa-solid fa-trash"></i> Usuń
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeEventModal()">Anuluj</button>
            <button type="button" class="btn btn-primary" onclick="saveEvent()">
                <i class="fa-solid fa-floppy-disk"></i> Zapisz
            </button>
        </div>
    </div>
</div>