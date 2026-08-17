<?php
// includes/components/command_palette.php
?>
<div class="cmd-overlay" id="cmdPalette" onclick="closeCommandPalette()">
            <div class="cmd-palette" onclick="event.stopPropagation()">
                <div class="cmd-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="cmdInput" placeholder="Wpisz komendę lub wyszukaj..."
                        oninput="filterCmdItems(this.value)" autocomplete="off">
                </div>
                <div class="cmd-body" id="cmdBody">
                    <!-- Quick actions -->
                    <div class="cmd-section-label">⚡ Szybkie akcje</div>
                    <div class="cmd-item" data-search="nowe zadanie dodaj" onclick="window.location.href='/pages/tasks.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-plus"></i></div>
                        <div><div class="cmd-item-text">Nowe zadanie</div><div class="cmd-item-sub">Stwórz nowe zadanie w projekcie</div></div>
                        <span class="cmd-item-kbd">N</span>
                    </div>
                    <div class="cmd-item" data-search="nowy projekt stwórz" onclick="window.location.href='/pages/projects.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-folder-plus"></i></div>
                        <div><div class="cmd-item-text">Nowy projekt</div><div class="cmd-item-sub">Stwórz projekt zespołowy</div></div>
                    </div>
                    <div class="cmd-item" data-search="dodaj użytkownika team" onclick="window.location.href='/pages/team.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <div><div class="cmd-item-text">Dodaj użytkownika</div><div class="cmd-item-sub">Zarządzaj zespołem</div></div>
                    </div>
                    <div class="cmd-item" data-search="notatka stwórz notatkę" onclick="window.location.href='/pages/notes.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-note-sticky"></i></div>
                        <div><div class="cmd-item-text">Nowa notatka</div><div class="cmd-item-sub">Stwórz prywatną notatkę</div></div>
                    </div>

                    <!-- Navigation -->
                    <div class="cmd-section-label">🧭 Nawigacja</div>
                    <div class="cmd-item" data-search="dashboard panel główny" onclick="window.location.href='/pages/dashboard.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="cmd-item-text">Dashboard</div>
                    </div>
                    <div class="cmd-item" data-search="projekty folder" onclick="window.location.href='/pages/projects.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="cmd-item-text">Projekty</div>
                    </div>
                    <div class="cmd-item" data-search="zadania lista" onclick="window.location.href='/pages/tasks.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-list-check"></i></div>
                        <div class="cmd-item-text">Zadania</div>
                    </div>
                    <div class="cmd-item" data-search="kalendarz calendar" onclick="window.location.href='/pages/calendar.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="cmd-item-text">Kalendarz</div>
                    </div>
                    <div class="cmd-item" data-search="zespół team użytkownicy" onclick="window.location.href='/pages/team.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="cmd-item-text">Zespół</div>
                    </div>
                    <div class="cmd-item" data-search="raporty statystyki wykresy" onclick="window.location.href='/pages/reports.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-chart-bar"></i></div>
                        <div class="cmd-item-text">Raporty</div>
                    </div>
                    <div class="cmd-item" data-search="ustawienia settings" onclick="window.location.href='/pages/settings.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-sliders"></i></div>
                        <div class="cmd-item-text">Ustawienia</div>
                    </div>

                    <!-- Theme -->
                    <div class="cmd-section-label">🎨 Motyw</div>
                    <div class="cmd-item" data-search="dark ciemny motyw" onclick="setThemeDark();closeCommandPalette()">
                        <div class="cmd-item-icon"><i class="fa-solid fa-moon"></i></div>
                        <div class="cmd-item-text">Ciemny motyw</div>
                    </div>
                    <div class="cmd-item" data-search="light jasny motyw" onclick="setThemeLight();closeCommandPalette()">
                        <div class="cmd-item-icon"><i class="fa-solid fa-sun"></i></div>
                        <div class="cmd-item-text">Jasny motyw</div>
                    </div>
                </div>
                <div class="cmd-footer">
                    <span><kbd>↑</kbd><kbd>↓</kbd> Nawigacja</span>
                    <span><kbd>Enter</kbd> Wybierz</span>
                    <span><kbd>Esc</kbd> Zamknij</span>
                </div>
            </div>
            </div>
