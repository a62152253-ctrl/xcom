<div class="modal-overlay" id="note-modal">
    <div class="modal-window" style="max-width:560px">
        <div class="modal-header">
            <h2 class="modal-title" id="note-modal-title">Nowa notatka</h2>
            <button class="modal-close" onclick="closeNoteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="note-id">
            <div class="form-group">
                <label class="form-label">Tytuł</label>
                <input class="form-control" type="text" id="note-title" placeholder="np. Pomysł na feature...">
            </div>
            <div class="form-group">
                <label class="form-label">Treść</label>
                <textarea class="form-control" id="note-content" rows="6" placeholder="Wpisz notatke..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tagi (oddziel przecinkami)</label>
                <input class="form-control" type="text" id="note-tags" placeholder="np. pomysł, ważne, projekt-X">
            </div>
            <div class="form-group">
                <label class="form-label">Kolor</label>
                <div class="color-picker-row">
                    <?php foreach ($note_colors as $c): ?>
                    <div class="color-chip" data-color="<?= $c ?>" style="background:<?= $c ?>" onclick="selectColor('<?= $c ?>')"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="note-color" value="#3b82f6">
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" id="note-pinned"> Przypnij notatkę na górze
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeNoteModal()" style="width:auto">Anuluj</button>
            <button class="btn btn-primary" onclick="saveNote()" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
        </div>
    </div>
</div>
