// Global Search (Command Palette)
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openCommandPalette();
    }
});

function openCommandPalette() {
    const cmdPalette = document.getElementById('cmdPalette');
    if (cmdPalette) {
        cmdPalette.style.display = 'flex';
        document.getElementById('cmdInput').focus();
    }
}

function closeCommandPalette() {
    const cmdPalette = document.getElementById('cmdPalette');
    if (cmdPalette) {
        cmdPalette.style.display = 'none';
    }
}

let cmdTimeout;
function filterCmdItems(q) {
    clearTimeout(cmdTimeout);
    q = q.trim().toLowerCase();

    // Local static filtering
    document.querySelectorAll('.cmd-item').forEach(item => {
        const searchTerms = item.getAttribute('data-search') || '';
        const text = item.innerText.toLowerCase();
        if (searchTerms.includes(q) || text.includes(q)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });

    // Dynamic database search
    if (q.length >= 2) {
        cmdTimeout = setTimeout(async () => {
            try {
                const response = await fetch('/api/search.php?q=' + encodeURIComponent(q));
                if (!response.ok) return;
                const data = await response.json();

                // Remove existing dynamic results
                document.querySelectorAll('.cmd-item-dynamic').forEach(el => el.remove());

                if (data.results && data.results.length > 0) {
                    const cmdBody = document.getElementById('cmdBody');

                    // Add section label if not exists
                    let dynLabel = document.getElementById('cmd-dynamic-label');
                    if (!dynLabel) {
                        dynLabel = document.createElement('div');
                        dynLabel.id = 'cmd-dynamic-label';
                        dynLabel.className = 'cmd-section-label';
                        dynLabel.innerText = 'Wyniki z bazy';
                        cmdBody.appendChild(dynLabel);
                    }

                    data.results.forEach(result => {
                        const div = document.createElement('div');
                        div.className = 'cmd-item cmd-item-dynamic';

                        let iconClass, url, typeLabel;
                        if (result._type === 'task') {
                            iconClass = 'fa-list-check';
                            url = '/pages/tasks.php?task_id=' + result.id;
                            typeLabel = 'Zadanie';
                        } else if (result._type === 'project') {
                            iconClass = 'fa-folder-open';
                            url = '/pages/projects.php?id=' + result.id;
                            typeLabel = 'Projekt';
                        } else if (result._type === 'note') {
                            iconClass = 'fa-note-sticky';
                            url = '/pages/notes.php';
                            typeLabel = 'Notatka';
                        }

                        div.onclick = () => window.location.href = url;

                        // Sanitize output
                        const safeTitle = result.title.replace(/</g, "&lt;").replace(/>/g, "&gt;");

                        div.innerHTML = `
                            <div class="cmd-item-icon"><i class="fa-solid ${iconClass}"></i></div>
                            <div>
                                <div class="cmd-item-text">${safeTitle}</div>
                                <div class="cmd-item-sub">${typeLabel}</div>
                            </div>
                        `;
                        cmdBody.appendChild(div);
                    });
                }
            } catch (e) {
                console.error('Search error', e);
            }
        }, 300);
    } else {
        document.querySelectorAll('.cmd-item-dynamic').forEach(el => el.remove());
        const dynLabel = document.getElementById('cmd-dynamic-label');
        if (dynLabel) dynLabel.remove();
    }
}
