<!-- ── NOTIFICATIONS TAB ── -->
                <form method="POST" action="/api/profile.php?action=settings">
            <div class="settings-section">
                <h2 class="settings-section-title">Powiadomienia email</h2>
                <div class="settings-toggle-row">
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Powiadomienia e-mail</div>
                        <div style="color:var(--text-muted);font-size:.8rem">Otrzymuj powiadomienia o przypisanych zadaniach i komentarzach</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_notifications" <?= ($user_data['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Powiadomienia push</h2>
                <div class="settings-toggle-row">
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Powiadomienia w przeglądarce</div>
                        <div style="color:var(--text-muted);font-size:.8rem">Natychmiastowe alerty o nowych zadaniach</div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="requestPushPermission()" style="width:auto;font-size:.8rem" id="push-btn">
                        <i class="fa-solid fa-bell"></i> Włącz
                    </button>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
        </form>

