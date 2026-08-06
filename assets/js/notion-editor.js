// assets/js/notion-editor.js - Notion Block Editor Logic
let saveTimeout = null;
let activeBlockEl = null;

// Bind global editor clicks and click-outs
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notion-slash-menu') && activeBlockEl) {
            hideSlashMenu();
        }
    });
});

async function openPage(id) {
    const res = await fetch(`/api/notes.php?action=get&id=${id}`);
    const data = await res.json();
    if (!data.success) return;

    activePage = data.note;
    window.history.pushState(null, '', `?id=${id}`);

    document.getElementById('notion-empty-state').style.display = 'none';
    document.getElementById('notion-header-bar').style.display = 'flex';
    document.getElementById('notion-canvas').style.display = 'block';

    document.getElementById('page-icon-btn').textContent = activePage.icon || '📝';
    document.getElementById('notion-page-title').value = activePage.title || '';

    // Favorites button state
    const favBtn = document.getElementById('btn-favorite');
    const favIcon = favBtn.querySelector('i');
    if (activePage.is_favorite == 1) {
        favIcon.className = 'fa-solid fa-star';
        favBtn.classList.add('active');
    } else {
        favIcon.className = 'fa-regular fa-star';
        favBtn.classList.remove('active');
    }

    renderBreadcrumbs(id);

    // Render Blocks
    const editor = document.getElementById('notion-editor');
    editor.innerHTML = '';
    let blocks = [];
    try { blocks = JSON.parse(activePage.content || '[]'); } catch(e) {}
    if (blocks.length === 0) blocks = [{ type: 'p', text: '' }];

    blocks.forEach(b => editor.appendChild(createBlockElement(b.type, b.text, b.checked)));
    updateBlockPlaceholders();
    renderSubpagesWidget(id);

    document.querySelectorAll('.sidebar-page-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === id);
    });
}

function renderBreadcrumbs(id) {
    const path = [];
    let curr = pagesList.find(p => p.id === id);
    while (curr) {
        path.unshift(curr);
        curr = pagesList.find(p => p.id === curr.parent_id);
    }
    const bcrumb = document.getElementById('notion-breadcrumbs');
    bcrumb.innerHTML = '<span>Obszar roboczy</span> / ' + path.map((p, idx) => {
        if (idx === path.length - 1) return `<strong>${p.title || 'Bez tytułu'}</strong>`;
        return `<span onclick="openPage(${p.id})">${p.title || 'Bez tytułu'}</span>`;
    }).join(' / ');
}

function renderSubpagesWidget(id) {
    const subpagesList = document.getElementById('subpages-list');
    const subs = pagesList.filter(p => p.parent_id == id && p.is_archived == 0 && p.is_trash == 0);
    if (subs.length === 0) {
        subpagesList.innerHTML = `<div style="font-size:12px;color:var(--text-muted);grid-column:1/-1">Brak podstron.</div>`;
    } else {
        subpagesList.innerHTML = subs.map(s => `
            <div class="subpage-card" onclick="openPage(${s.id})">
                <span>${s.icon || '📝'}</span>
                <span>${s.title || 'Bez tytułu'}</span>
            </div>
        `).join('');
    }
}

function createBlockElement(type, text = '', checked = false) {
    const wrap = document.createElement('div');
    wrap.className = 'notion-block-container';

    if (type === 'todo') {
        wrap.className += ' notion-block-todo-container' + (checked ? ' checked' : '');
        const chk = document.createElement('input');
        chk.type = 'checkbox';
        chk.className = 'notion-block-todo-check';
        chk.checked = checked;
        chk.onchange = () => { wrap.classList.toggle('checked', chk.checked); triggerAutosave(); };
        wrap.appendChild(chk);
    } else if (type === 'bullet') {
        wrap.className += ' notion-block-bullet-container';
        const dot = document.createElement('span');
        dot.className = 'notion-block-bullet-dot';
        dot.innerHTML = '•';
        wrap.appendChild(dot);
    }

    const block = document.createElement('div');
    block.className = `notion-block notion-block-${type}`;
    block.contentEditable = 'true';
    block.dataset.type = type;
    block.innerHTML = text;

    block.onkeydown = (e) => handleBlockKeyDown(e, block);
    block.oninput = () => {
        updateBlockPlaceholders();
        handleSlashCommand(block);
        triggerAutosave();
    };

    wrap.appendChild(block);
    return wrap;
}

function updateBlockPlaceholders() {
    document.querySelectorAll('.notion-block').forEach(b => {
        const type = b.dataset.type;
        let placeholder = 'Wpisz tekst lub wpisz / aby wybrać blok...';
        if (type === 'h1') placeholder = 'Nagłówek 1';
        if (type === 'h2') placeholder = 'Nagłówek 2';
        if (type === 'todo') placeholder = 'Zadanie do zrobienia';
        if (type === 'bullet') placeholder = 'Lista punktowana';
        if (type === 'quote') placeholder = 'Cytat';
        if (type === 'code') placeholder = 'Kod źródłowy...';
        b.setAttribute('placeholder', placeholder);
    });
}

function handleBlockKeyDown(e, block) {
    const wrap = block.parentElement;
    if (e.key === 'Enter') {
        e.preventDefault();
        const newBlock = createBlockElement('p');
        wrap.after(newBlock);
        newBlock.querySelector('.notion-block').focus();
        triggerAutosave();
    } else if (e.key === 'Backspace' && block.textContent === '') {
        const prev = wrap.previousElementSibling;
        if (prev && prev.querySelector('.notion-block')) {
            e.preventDefault();
            const prevBlock = prev.querySelector('.notion-block');
            prevBlock.focus();
            const range = document.createRange();
            const sel = window.getSelection();
            range.selectNodeContents(prevBlock);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            wrap.remove();
            triggerAutosave();
        }
    } else if (e.key === 'ArrowUp') {
        wrap.previousElementSibling?.querySelector('.notion-block')?.focus();
    } else if (e.key === 'ArrowDown') {
        wrap.nextElementSibling?.querySelector('.notion-block')?.focus();
    }
}
