// assets/js/notion-sidebar.js - Notion Workspace Sidebar Tree
let pagesList = [];
let activePage = null;

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('notion-workspace-container');
    if (!container) return;
    loadSidebarPages().then(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const activeId = urlParams.get('id') || container.dataset.activeId;
        if (activeId) openPage(parseInt(activeId));
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#page-icon-btn') && !e.target.closest('#notion-emoji-picker')) {
            const picker = document.getElementById('notion-emoji-picker');
            if (picker) picker.style.display = 'none';
        }
    });
});

async function loadSidebarPages() {
    const res = await fetch('/api/notes.php?action=list');
    const data = await res.json();
    if (data.success) {
        pagesList = data.notes || [];
        renderSidebarTree();
    }
}

function renderSidebarTree() {
    const treeContainer = document.getElementById('sidebar-pages-tree');
    if (!treeContainer) return;
    const activePages = pagesList.filter(p => p.is_archived == 0 && p.is_trash == 0);
    const roots = activePages.filter(p => !p.parent_id);

    const buildNodeHTML = (page) => {
        const children = activePages.filter(p => p.parent_id == page.id);
        const hasChildren = children.length > 0;
        const icon = page.icon || '📝';
        const isActive = activePage && activePage.id === page.id ? 'active' : '';
        let html = `
            <div class="sidebar-page-item-wrap">
                <div class="sidebar-page-item ${isActive}" data-id="${page.id}" onclick="openPage(${page.id})">
                    <span class="sidebar-page-caret ${hasChildren ? 'has-children' : ''}"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="sidebar-page-icon">${icon}</span>
                    <span class="sidebar-page-title-txt">${page.title || 'Bez tytułu'}</span>
                    <div class="sidebar-page-actions" onclick="event.stopPropagation()">
                        <button class="sidebar-page-action-btn" onclick="createNewPageInSidebar(${page.id})" title="Dodaj podstronę"><i class="fa-solid fa-plus"></i></button>
                        <button class="sidebar-page-action-btn" onclick="deletePageImmediate(${page.id})" title="Przenieś do kosza"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
                <div class="sidebar-page-children" id="children-${page.id}">
        `;
        children.forEach(child => { html += buildNodeHTML(child); });
        html += `</div></div>`;
        return html;
    };

    let treeHTML = roots.map(buildNodeHTML).join('');
    if (!treeHTML) treeHTML = `<div style="font-size:11px;color:var(--text-muted);padding:8px">Brak stron.</div>`;

    const trashed = pagesList.filter(p => p.is_trash == 1);
    const archived = pagesList.filter(p => p.is_archived == 1);
    if (trashed.length > 0 || archived.length > 0) treeHTML += `<div style="border-top:1px solid var(--border-color);margin-top:10px;padding-top:10px"></div>`;
    if (archived.length > 0) treeHTML += `<div class="sidebar-page-item" onclick="openArchiveView()"><span class="sidebar-page-icon">📦</span><span class="sidebar-page-title-txt">Archiwum (${archived.length})</span></div>`;
    if (trashed.length > 0) treeHTML += `<div class="sidebar-page-item" onclick="openTrashView()"><span class="sidebar-page-icon">🗑️</span><span class="sidebar-page-title-txt">Kosz (${trashed.length})</span></div>`;

    treeContainer.innerHTML = treeHTML;
    document.querySelectorAll('.sidebar-page-caret.has-children').forEach(caret => {
        caret.onclick = (e) => {
            e.stopPropagation();
            caret.classList.toggle('expanded');
            document.getElementById(`children-${caret.closest('.sidebar-page-item').dataset.id}`).classList.toggle('expanded');
        };
    });
}

async function createNewPageInSidebar(parentId) {
    const res = await fetch('/api/notes.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ parent_id: parentId, title: 'Bez tytułu', icon: '📝' })
    });
    const data = await res.json();
    if (data.success) {
        await loadSidebarPages();
        openPage(data.id);
    }
}

async function deletePageImmediate(id) {
    if (!confirm('Przenieść stronę do kosza?')) return;
    const res = await fetch('/api/notes.php?action=toggle_trash', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if ((await res.json()).success) {
        await loadSidebarPages();
        if (activePage && activePage.id === id) location.reload();
        else renderSidebarTree();
    }
}

function openTrashView() {
    const trashed = pagesList.filter(p => p.is_trash == 1);
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
        <div class="modal-window" style="max-width:480px">
            <div class="modal-header"><h2 class="modal-title">🗑️ Kosz strony</h2><button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button></div>
            <div class="modal-body" style="padding:1rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:1rem"><span style="font-size:12px;color:var(--text-secondary)">Usunięte strony są tutaj.</span><button class="btn btn-danger btn-sm" onclick="emptyTrashAll()">Opróżnij kosz</button></div>
                <div class="trash-list" style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto">
                    ${trashed.map(p => `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-tertiary);border-radius:6px"><span>${p.icon || '📝'} ${p.title}</span><div style="display:flex;gap:4px"><button class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px" onclick="restorePage(${p.id})">Przywróć</button><button class="btn btn-danger btn-sm" style="padding:2px 8px;font-size:11px;color:var(--danger)" onclick="deletePagePermanent(${p.id})">Usuń</button></div></div>`).join('')}
                </div>
            </div>
        </div>`;
    document.body.appendChild(modal);
}

function openArchiveView() {
    const archived = pagesList.filter(p => p.is_archived == 1);
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
        <div class="modal-window" style="max-width:480px">
            <div class="modal-header"><h2 class="modal-title">📦 Archiwum strony</h2><button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button></div>
            <div class="modal-body" style="padding:1rem">
                <div class="archive-list" style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto">
                    ${archived.map(p => `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-tertiary);border-radius:6px"><span>${p.icon || '📝'} ${p.title}</span><div style="display:flex;gap:4px"><button class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px" onclick="unarchivePage(${p.id})">Przywróć</button></div></div>`).join('')}
                </div>
            </div>
        </div>`;
    document.body.appendChild(modal);
}

async function restorePage(id) {
    const res = await fetch('/api/notes.php?action=toggle_trash', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    if ((await res.json()).success) location.reload();
}
async function unarchivePage(id) {
    const res = await fetch('/api/notes.php?action=toggle_archive', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    if ((await res.json()).success) location.reload();
}
async function deletePagePermanent(id) {
    if (!confirm('Usunąć tę stronę na zawsze? Tej operacji nie można cofnąć.')) return;
    const res = await fetch('/api/notes.php?action=delete_permanently', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    if ((await res.json()).success) location.reload();
}
async function emptyTrashAll() {
    if (!confirm('Opróżnić cały kosz stron?')) return;
    const res = await fetch('/api/notes.php?action=empty_trash', { method: 'POST' });
    if ((await res.json()).success) location.reload();
}
