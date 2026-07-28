function applyFilter(type, value) {
    const params = new URLSearchParams(window.location.search);
    if (type === 'date') params.set('date', value);
    else if (type === 'action') params.set('action', value);
    params.set('page', '1');
    window.location.href = '/pages/logs.php?' + params.toString();
}

function goToPage(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    window.location.href = '/pages/logs.php?' + params.toString();
}