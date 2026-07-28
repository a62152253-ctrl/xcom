function toggleUserStatus(userId, newStatus) {
        if (confirm(`Czy na pewno chcesz zmienić status użytkownika na ${newStatus}?`)) {
            fetch('/api/admin.php?action=toggle_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Błąd zapisu statusu');
                }
            });
        }
    }

    function changeUserRole(userId, newRole) {
        fetch('/api/admin.php?action=change_role', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, role: newRole })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Rola została zaktualizowana!');
                location.reload();
            } else {
                alert(data.error || 'Błąd zapisu roli');
                location.reload();
            }
        });
    }