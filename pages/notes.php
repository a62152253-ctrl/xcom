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
    <div class="empty-state-premium" style="grid-column:1/-1">
        <div class="es-icon">📝</div>
        <div class="es-title">Brak notatek</div>
        <div class="es-sub">Zacznij od stworzenia swojej pierwszej notatki. Są one widoczne tylko dla Ciebie.</div>
        <button class="es-btn" onclick="openNoteModal()"><i class="fa-solid fa-plus"></i> Nowa notatka</button>
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

<?php require_once __DIR__ . '/../includes/modals/note_modal.php'; ?>

<script src="/assets/js/notes.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
