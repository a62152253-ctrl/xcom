<!-- ── APPEARANCE TAB ── -->
                <div class="settings-section">
            <h2 class="settings-section-title">Motyw kolorystyczny</h2>
            <div class="theme-picker-grid">
                <div class="theme-option <?= ($user_data['theme'] ?? 'dark') === 'dark' ? 'theme-option--active' : '' ?>"
                     onclick="setTheme('dark')" id="theme-dark">
                    <div class="theme-preview theme-preview--dark">
                        <div class="theme-preview-sidebar"></div>
                        <div class="theme-preview-content"></div>
                    </div>
                    <div class="theme-option-label"><i class="fa-solid fa-moon"></i> Ciemny</div>
                </div>
                <div class="theme-option <?= ($user_data['theme'] ?? 'dark') === 'light' ? 'theme-option--active' : '' ?>"
                     onclick="setTheme('light')" id="theme-light">
                    <div class="theme-preview theme-preview--light">
                        <div class="theme-preview-sidebar"></div>
                        <div class="theme-preview-content"></div>
                    </div>
                    <div class="theme-option-label"><i class="fa-solid fa-sun"></i> Jasny</div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h2 class="settings-section-title">Podgląd na żywo</h2>
            <p style="color:var(--text-secondary);font-size:.875rem">Zmiany motywu są stosowane natychmiast. Nie musisz zapisywać.</p>
        </div>


