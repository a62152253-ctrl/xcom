<!-- ── SECURITY TAB ── -->
                <form method="POST" action="/api/profile.php?action=settings">
            <div class="settings-section">
                <h2 class="settings-section-title">Zmiana hasła</h2>
                <p style="color:var(--text-secondary);font-size:.875rem;margin-bottom:1.25rem">Zostaw puste jeśli nie chcesz zmieniać hasła.</p>

                <link rel="stylesheet" href="/assets/css/auth.css">
                <script src="/assets/js/auth.js" defer></script>

                <div class="form-group">
                    <label class="form-label">Nowe hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" name="password" id="password" placeholder="Minimum 8 znaków" style="padding-right:2.5rem" maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div id="pwd-strength-container" class="pwd-strength-meter">
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                    </div>
                    <div id="pwd-strength-text" class="pwd-strength-text"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Potwierdź nowe hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Powtórz hasło" maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Informacje o sesji</h2>
                <div class="session-info-card">
                    <i class="fa-solid fa-globe"></i>
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Aktualna sesja</div>
                        <div style="color:var(--text-muted);font-size:.8rem">IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '—', ENT_QUOTES, 'UTF-8') ?> · <?= date('d.m.Y H:i') ?></div>
                    </div>
                    <span class="badge-pill badge-active">Aktywna</span>
                </div>
                <a href="/auth/logout.php" class="btn btn-secondary" style="width:auto;margin-top:1rem;color:var(--danger)">
                    <i class="fa-solid fa-right-from-bracket"></i> Wyloguj się ze wszystkich urządzeń
                </a>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-lock"></i> Zmień hasło</button>
        </form>

