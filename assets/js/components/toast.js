
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
