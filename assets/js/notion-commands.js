// assets/js/notion-commands.js - Notion Workspace Slash Commands & Actions
function handleSlashCommand(block) {
    const text = block.textContent;
    if (text.endsWith('/')) {
        activeBlockEl = block;
        showSlashMenu(block);
    } else if (!text.includes('/')) {
        hideSlashMenu();
    }
}

function showSlashMenu(block) {
    const menu = document.getElementById('notion-slash-menu');
    const rect = block.getBoundingClientRect();
    menu.style.display = 'block';
    menu.style.top = `${window.scrollY + rect.bottom + 4}px`;
    menu.style.left = `${window.scrollX + rect.left}px`;
}

function hideSlashMenu() {
    const menu = document.getElementById('notion-slash-menu');
    if (menu) menu.style.display = 'none';
}

function convertActiveBlock(type) {
    if (!activeBlockEl) return;
    const wrap = activeBlockEl.parentElement;
    const txt = activeBlockEl.textContent.replace('/', '').trim();
    const newWrap = createBlockElement(type, txt);
    wrap.replaceWith(newWrap);
    newWrap.querySelector('.notion-block').focus();
    hideSlashMenu();
    triggerAutosave();
}

function handleTitleInput() {
    const title = document.getElementById('notion-page-title').value.trim() || 'Bez tytułu';
    if (activePage) activePage.title = title;
    triggerAutosave();
}

function triggerAutosave() {
    const status = document.getElementById('notion-save-status');
    if (status) status.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Zapisywanie...`;

    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveCurrentPage, 1200);
}

async function saveCurrentPage() {
    if (!activePage) return;
    const title = document.getElementById('notion-page-title').value.trim() || 'Bez tytułu';
    const icon = document.getElementById('page-icon-btn').textContent;

    const blocks = [];
    document.querySelectorAll('.notion-block').forEach(b => {
        const type = b.dataset.type;
        const text = b.innerHTML;
        const container = b.parentElement;
        const checked = container.classList.contains('checked');
        blocks.push({ type, text, checked });
    });

    const payload = {
        id: activePage.id,
        title,
        icon,
        content: JSON.stringify(blocks),
        parent_id: activePage.parent_id,
        is_favorite: activePage.is_favorite,
        is_archived: activePage.is_archived,
        is_trash: activePage.is_trash,
        color: activePage.color,
        tags: activePage.tags,
        is_pinned: activePage.is_pinned
    };

    const res = await fetch('/api/notes.php?action=update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
        document.getElementById('notion-save-status').innerHTML = `<i class="fa-solid fa-circle-check"></i> Zapisano`;
        await loadSidebarPages();
        renderBreadcrumbs(activePage.id);
    }
}

async function createNewSubpage() {
    if (activePage) createNewPageInSidebar(activePage.id);
}

async function togglePageFavorite() {
    if (!activePage) return;
    activePage.is_favorite = activePage.is_favorite == 1 ? 0 : 1;
    await saveCurrentPage();
}

async function togglePageArchive() {
    if (!activePage) return;
    activePage.is_archived = activePage.is_archived == 1 ? 0 : 1;
    await saveCurrentPage();
    location.reload();
}

async function togglePageTrash() {
    if (!activePage) return;
    if (confirm('Przenieść stronę do kosza?')) {
        activePage.is_trash = 1;
        await saveCurrentPage();
        location.reload();
    }
}

function toggleEmojiPicker(e) {
    e.stopPropagation();
    const picker = document.getElementById('notion-emoji-picker');
    picker.style.display = picker.style.display === 'none' ? 'grid' : 'none';
}

function selectEmoji(emoji) {
    document.getElementById('page-icon-btn').textContent = emoji;
    document.getElementById('notion-emoji-picker').style.display = 'none';
    triggerAutosave();
}
