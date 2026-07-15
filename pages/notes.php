<?php
// pages/notes.php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY is_pinned DESC, updated_at DESC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

$note_colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-note-sticky"></i> Notatki</h1>
        <p class="page-subtitle">Twoje prywatne notatki — widoczne tylko dla Ciebie.</p>
    </div>
    <button class="btn btn-primary" onclick="openNoteModal()">
        <i class="fa-solid fa-plus"></i> Nowa notatka
    </button>
</div>

<!-- Search -->
<div class="notes-search-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" class="form-control notes-search" id="notes-search" placeholder="Szukaj w notatkach..." oninput="filterNotes(this.value)">
</div>

<!-- Notes grid -->
<div class="notes-grid" id="notes-grid">
<?php if (empty($notes)): ?>
    <div class="notes-empty">
        <i class="fa-regular fa-note-sticky"></i>
        <p>Nie masz jeszcze żadnych notatek.<br>Kliknij „Nowa notatka", aby zacząć.</p>
    </div>
<?php else: ?>
    <?php foreach ($notes as $n): ?>
    <div class="note-card" data-id="<?= $n['id'] ?>" data-search="<?= strtolower(sanitize($n['title']) . ' ' . sanitize($n['content'])) ?>"
         style="border-top:4px solid <?= $n['color'] ?>">
        <?php if ($n['is_pinned']): ?>
        <div class="note-pin"><i class="fa-solid fa-thumbtack"></i></div>
        <?php endif; ?>
        <h3 class="note-title"><?= sanitize($n['title']) ?></h3>
        <p class="note-preview"><?= nl2br(sanitize(mb_substr($n['content'] ?? '', 0, 150))) ?><?= mb_strlen($n['content'] ?? '') > 150 ? '…' : '' ?></p>
        <?php if (!empty($n['tags'])): ?>
        <div class="note-tags">
            <?php foreach (array_filter(array_map('trim', explode(',', $n['tags']))) as $tag): ?>
            <span class="note-tag"><?= sanitize($tag) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="note-footer">
            <span class="note-date"><?= date('d.m.Y H:i', strtotime($n['updated_at'])) ?></span>
            <div class="note-actions">
                <button class="note-btn" onclick='editNote(<?= json_encode($n) ?>)' title="Edytuj"><i class="fa-solid fa-pen"></i></button>
                <button class="note-btn note-btn-del" onclick="deleteNote(<?= $n['id'] ?>)" title="Usuń"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Modal -->
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

<script>
let selectedColor = '#3b82f6';

function selectColor(c) {
    selectedColor = c;
    document.getElementById('note-color').value = c;
    document.querySelectorAll('.color-chip').forEach(el => {
        el.classList.toggle('color-chip--active', el.dataset.color === c);
    });
}

// Init first color
document.querySelector('.color-chip')?.classList.add('color-chip--active');

function openNoteModal() {
    document.getElementById('note-id').value = '';
    document.getElementById('note-title').value = '';
    document.getElementById('note-content').value = '';
    document.getElementById('note-tags').value = '';
    document.getElementById('note-pinned').checked = false;
    document.getElementById('note-modal-title').textContent = 'Nowa notatka';
    selectColor('#3b82f6');
    document.getElementById('note-modal').classList.add('active');
}

function closeNoteModal() {
    document.getElementById('note-modal').classList.remove('active');
}

function editNote(n) {
    document.getElementById('note-id').value = n.id;
    document.getElementById('note-title').value = n.title;
    document.getElementById('note-content').value = n.content || '';
    document.getElementById('note-tags').value = n.tags || '';
    document.getElementById('note-pinned').checked = n.is_pinned == 1;
    document.getElementById('note-modal-title').textContent = 'Edytuj notatkę';
    selectColor(n.color || '#3b82f6');
    document.getElementById('note-modal').classList.add('active');
}

async function saveNote() {
    const id = document.getElementById('note-id').value;
    const payload = {
        id: id || null,
        title: document.getElementById('note-title').value.trim() || 'Bez tytułu',
        content: document.getElementById('note-content').value,
        tags: document.getElementById('note-tags').value,
        color: document.getElementById('note-color').value,
        is_pinned: document.getElementById('note-pinned').checked ? 1 : 0
    };
    const action = id ? 'update' : 'create';
    const res = await fetch('/api/notes.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd zapisu');
}

async function deleteNote(id) {
    if (!confirm('Usunąć notatkę?')) return;
    const res = await fetch('/api/notes.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
}

function filterNotes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.note-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNoteModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
