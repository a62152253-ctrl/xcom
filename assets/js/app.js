/**
 * TaskManager Pro — Global App JS
 * Toast system, AJAX helpers, microinteractions, mobile menu, notification polling
 */

'use strict';

// ─── Toast Notification System ────────────────────────────────────────────────
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 3500) {
        if (!this.container) this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;

        const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        toast.innerHTML = `
            <i class="fa-solid ${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;

        this.container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('toast--visible'));

        setTimeout(() => {
            toast.classList.remove('toast--visible');
            setTimeout(() => toast.remove(), 350);
        }, duration);

        return toast;
    },

    success: (msg, dur) => Toast.show(msg, 'success', dur),
    error:   (msg, dur) => Toast.show(msg, 'error', dur),
    warning: (msg, dur) => Toast.show(msg, 'warning', dur),
    info:    (msg, dur) => Toast.show(msg, 'info', dur),
};

// ─── AJAX Helper ──────────────────────────────────────────────────────────────
async function apiPost(url, data = {}) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        return json;
    } catch (e) {
        console.error('API Error:', e);
        return { success: false, error: 'Błąd połączenia z serwerem.' };
    }
}

async function apiGet(url) {
    try {
        const res = await fetch(url);
        return await res.json();
    } catch (e) {
        return null;
    }
}

// ─── Animated Counter ─────────────────────────────────────────────────────────
function animateCounter(el, target, duration = 800) {
    const start = 0;
    const startTime = performance.now();
    const update = (now) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (target - start) * eased);
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

// ─── Mobile Sidebar ───────────────────────────────────────────────────────────
const MobileMenu = {
    sidebar: null,
    overlay: null,

    init() {
        this.sidebar = document.querySelector('.sidebar');
        if (!this.sidebar) return;

        // Create hamburger button
        const burger = document.createElement('button');
        burger.className = 'hamburger-btn';
        burger.id = 'hamburger-btn';
        burger.innerHTML = '<i class="fa-solid fa-bars"></i>';
        burger.setAttribute('aria-label', 'Menu');
        document.querySelector('.top-bar')?.prepend(burger);

        // Create overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'sidebar-overlay';
        this.overlay.id = 'sidebar-overlay';
        document.body.appendChild(this.overlay);

        burger.addEventListener('click', () => this.toggle());
        this.overlay.addEventListener('click', () => this.close());

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });
    },

    toggle() {
        this.sidebar?.classList.toggle('sidebar--open');
        this.overlay?.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    },

    close() {
        this.sidebar?.classList.remove('sidebar--open');
        this.overlay?.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    }
};

// ─── Scroll Reveal Animations ─────────────────────────────────────────────────
const ScrollReveal = {
    observer: null,

    init() {
        const elements = document.querySelectorAll('.card, .stat-card, .kpi-card, .note-card, .team-card, .file-card, .archive-card');
        elements.forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `opacity .4s ease ${i * 0.05}s, transform .4s ease ${i * 0.05}s`;
        });

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    this.observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        elements.forEach(el => this.observer.observe(el));
    }
};

// ─── Live Notification Polling ────────────────────────────────────────────────
const NotifPoller = {
    lastCount: 0,
    interval: null,

    init() {
        this.badge = document.getElementById('notif-count');
        if (!this.badge) return;

        this.lastCount = parseInt(this.badge.textContent) || 0;
        this.interval = setInterval(() => this.poll(), 30000); // every 30s
    },

    async poll() {
        const data = await apiGet('/api/notifications.php?action=count');
        if (!data) return;

        const count = data.unread_count || 0;
        const badge = document.getElementById('notif-count');
        if (badge) badge.textContent = count;

        // Show toast for new notifications
        if (count > this.lastCount) {
            const diff = count - this.lastCount;
            Toast.info(`🔔 Masz ${diff} nowe powiadomienie${diff > 1 ? 'a' : ''}!`, 4000);
            // Pulse badge
            badge?.classList.add('badge-pulse');
            setTimeout(() => badge?.classList.remove('badge-pulse'), 2000);
        }
        this.lastCount = count;
    }
};

// ─── Global Search ─────────────────────────────────────────────────────────────
const GlobalSearch = {
    input: null,
    results: null,
    timeout: null,

    init() {
        this.input = document.getElementById('global-search');
        this.results = document.getElementById('search-results');
        if (!this.input) return;

        this.input.addEventListener('input', (e) => {
            clearTimeout(this.timeout);
            const q = e.target.value.trim();
            if (q.length < 2) {
                this.hide();
                return;
            }
            this.timeout = setTimeout(() => this.search(q), 300);
        });

        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const q = this.input.value.trim();
                if (q) window.location.href = `/pages/search.php?q=${encodeURIComponent(q)}`;
            }
            if (e.key === 'Escape') this.hide();
        });

        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.results?.contains(e.target)) this.hide();
        });
    },

    async search(q) {
        if (!this.results) return;
        this.results.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Szukam...</div>';
        this.results.style.display = 'block';

        const data = await apiGet(`/api/search.php?q=${encodeURIComponent(q)}&limit=6`);
        if (!data) { this.hide(); return; }

        if (!data.results || data.results.length === 0) {
            this.results.innerHTML = '<div class="search-empty">Brak wyników dla "<strong>' + q + '</strong>"</div>';
            return;
        }

        const html = data.results.map(r => `
            <a href="${r.url}" class="search-result-item">
                <div class="search-result-icon search-result-icon--${r.type}">
                    <i class="fa-solid ${r.type === 'task' ? 'fa-list-check' : 'fa-folder'}"></i>
                </div>
                <div>
                    <div class="search-result-title">${r.title}</div>
                    <div class="search-result-sub">${r.subtitle || ''}</div>
                </div>
            </a>
        `).join('');

        this.results.innerHTML = html + `<a href="/pages/search.php?q=${encodeURIComponent(q)}" class="search-see-all">Zobacz wszystkie wyniki →</a>`;
    },

    hide() {
        if (this.results) this.results.style.display = 'none';
    }
};

// ─── Confirmation Dialog ───────────────────────────────────────────────────────
function confirmDialog(message, onConfirm, danger = false) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
        <div class="modal-window" style="max-width:420px">
            <div class="modal-body" style="text-align:center;padding:2rem">
                <div style="font-size:2.5rem;margin-bottom:1rem;color:${danger ? 'var(--danger)' : 'var(--warning)'}">
                    <i class="fa-solid ${danger ? 'fa-trash-can' : 'fa-circle-question'}"></i>
                </div>
                <p style="font-size:1rem;font-weight:600;margin-bottom:.5rem">${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()" style="width:auto">Anuluj</button>
                <button class="btn btn-${danger ? 'danger' : 'primary'} confirm-ok" style="width:auto">Potwierdź</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('.confirm-ok').addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
}

// ─── Skeleton Loader ─────────────────────────────────────────────────────────
function skeletonLoad(container, rows = 3) {
    container.innerHTML = Array(rows).fill(`
        <div class="skeleton-item">
            <div class="skeleton skeleton-avatar"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:.5rem">
                <div class="skeleton skeleton-line" style="width:60%"></div>
                <div class="skeleton skeleton-line" style="width:40%"></div>
            </div>
        </div>
    `).join('');
}

// ─── Theme Toggle ─────────────────────────────────────────────────────────────
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);

    const btn = document.getElementById('theme-toggle-btn');
    if (btn) btn.innerHTML = `<i class="fa-solid ${next === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;

    // Persist via API
    apiPost('/api/profile.php?action=theme', { theme: next });
}

// ─── Notification Dropdown ────────────────────────────────────────────────────
async function toggleNotificationsDropdown() {
    const dropdown = document.getElementById('notif-dropdown');
    if (!dropdown) return;
    const isOpen = dropdown.classList.contains('active');

    if (!isOpen) {
        dropdown.classList.add('active');
        const container = document.getElementById('notif-list-container');
        if (container) {
            skeletonLoad(container, 3);
            const data = await apiGet('/api/notifications.php');
            if (data?.notifications) {
                if (data.notifications.length === 0) {
                    container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:.85rem"><i class="fa-solid fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i>Brak nowych powiadomień</div>';
                } else {
                    container.innerHTML = data.notifications.slice(0, 8).map(n => `
                        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markNotifRead(${n.id}, this)">
                            <div style="font-weight:600;font-size:.8rem;margin-bottom:.2rem">${n.title}</div>
                            <div style="color:var(--text-secondary);font-size:.75rem">${n.message}</div>
                        </div>
                    `).join('');
                }
            }
            // Mark all read
            if (data?.unread_count > 0) {
                await apiPost('/api/notifications.php?action=mark_all_read');
                const badge = document.getElementById('notif-count');
                if (badge) badge.textContent = 0;
            }
        }
    } else {
        dropdown.classList.remove('active');
    }
}

function markNotifRead(id, el) {
    el?.classList.remove('unread');
}

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('notif-dropdown');
    const bell = document.querySelector('.notification-badge');
    if (dropdown?.classList.contains('active') && !dropdown.contains(e.target) && !bell?.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});

// ─── Global Search handler (for header) ────────────────────────────────────
function handleGlobalSearch(val) {
    GlobalSearch.search(val);
}

// ─── Input Auto-grow for textareas ───────────────────────────────────────────
document.querySelectorAll('textarea[data-autogrow]')?.forEach(el => {
    el.addEventListener('input', () => {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    });
});

// ─── Ripple Effect on Buttons ─────────────────────────────────────────────────
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn');
    if (!btn) return;

    const ripple = document.createElement('span');
    ripple.className = 'btn-ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = `width:${size}px;height:${size}px;top:${e.clientY - rect.top - size/2}px;left:${e.clientX - rect.left - size/2}px`;
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
});

// ─── Command Palette ──────────────────────────────────────────────────────────
let cmdSelectedIdx = -1;

function openCommandPalette() {
    const pal = document.getElementById('cmdPalette');
    if (!pal) return;
    pal.classList.add('open');
    setTimeout(() => {
        const inp = document.getElementById('cmdInput');
        if (inp) { inp.value = ''; inp.focus(); }
    }, 50);
    cmdSelectedIdx = -1;
    filterCmdItems('');
}

function closeCommandPalette() {
    const pal = document.getElementById('cmdPalette');
    if (pal) pal.classList.remove('open');
}

function filterCmdItems(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('#cmdBody .cmd-item').forEach(item => {
        const searchText = (item.dataset.search || '') + ' ' + (item.querySelector('.cmd-item-text')?.textContent || '');
        item.style.display = (!q || searchText.toLowerCase().includes(q)) ? 'flex' : 'none';
    });
    // Hide empty sections
    document.querySelectorAll('#cmdBody .cmd-section-label').forEach(label => {
        let next = label.nextElementSibling;
        let hasVisible = false;
        while (next && !next.classList.contains('cmd-section-label')) {
            if (next.style.display !== 'none') hasVisible = true;
            next = next.nextElementSibling;
        }
        label.style.display = hasVisible ? '' : 'none';
    });
    cmdSelectedIdx = -1;
}

function setThemeDark() {
    document.documentElement.setAttribute('data-theme','dark');
    fetch('/api/profile.php?action=update_theme', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({theme:'dark'})}).catch(()=>{});
}
function setThemeLight() {
    document.documentElement.setAttribute('data-theme','light');
    fetch('/api/profile.php?action=update_theme', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({theme:'light'})}).catch(()=>{});
}

// ─── Initialize all on DOMContentLoaded ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    Toast.init();
    MobileMenu.init();
    NotifPoller.init();
    GlobalSearch.init();
    ScrollReveal.init();

    // Animate stat counters
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.dataset.counter);
        if (!isNaN(target)) animateCounter(el, target);
    });

    // Command Palette keyboard shortcut
    document.addEventListener('keydown', e => {
        // Ctrl+K or Cmd+K — open command palette
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const pal = document.getElementById('cmdPalette');
            if (pal && pal.classList.contains('open')) closeCommandPalette();
            else openCommandPalette();
            return;
        }

        const pal = document.getElementById('cmdPalette');
        if (!pal || !pal.classList.contains('open')) {
            // Global: Escape closes modals
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active,.modal-ov.open').forEach(m => {
                    m.classList.remove('active','open');
                });
            }
            return;
        }

        const items = [...document.querySelectorAll('#cmdBody .cmd-item')].filter(i => i.style.display !== 'none');
        if (e.key === 'Escape') { e.preventDefault(); closeCommandPalette(); }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            cmdSelectedIdx = Math.min(cmdSelectedIdx + 1, items.length - 1);
            items.forEach((it,i) => it.classList.toggle('selected', i === cmdSelectedIdx));
            items[cmdSelectedIdx]?.scrollIntoView({block:'nearest'});
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            cmdSelectedIdx = Math.max(cmdSelectedIdx - 1, 0);
            items.forEach((it,i) => it.classList.toggle('selected', i === cmdSelectedIdx));
            items[cmdSelectedIdx]?.scrollIntoView({block:'nearest'});
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (cmdSelectedIdx >= 0 && items[cmdSelectedIdx]) items[cmdSelectedIdx].click();
        }
    });
});


// ─── Generic Modal Handler ────────────────────────────────────────────────
document.addEventListener('click', (e) => {
    // Close modal if overlay is clicked
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
