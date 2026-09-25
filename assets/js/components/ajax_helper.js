
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
        console.error('API Error:', e);
        return null;
    }
}
