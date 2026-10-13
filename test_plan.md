1. *Audyt bezpieczeństwa*
   - Utwórz katalog `security/` i dodaj `Csrf.php`, `Permissions.php`, `SecurityAudit.php`.
   - Zaktualizuj `includes/middleware.php` i upewnij się, że używa się w nim klas `security`.
2. *Dokończ architekturę MVC*
   - Utwórz katalogi `controllers/`, `services/`, `models/`.
   - Przenieś logikę z `api/tasks.php` do kontrolerów/usług/modeli, np. `TaskService::getUserTasks($user_id)`.
3. *Premium UX upgrade*
   - Dodaj smart empty states i onboarding w `pages/dashboard.php`.
   - Użyj `.empty-state-premium` ze wszystkimi wymaganymi elementami (ikony, tytuły, sub-teksty, przycisk CTA).
   - Dodaj "Activity timeline" w `pages/dashboard.php`.
4. *System powiadomień*
   - Zbuduj klasy w `notifications/NotificationService.php` i `notifications/NotificationCenter.php`.
   - Zaprojektuj logikę powiadomień.
5. *Analytics*
   - Rozbuduj `pages/reports.php` i wyświetlanie na `dashboard.php` o "Produktywność", "Czas pracy", "Ukończone zadania", "Aktywne projekty".
6. *Global Search*
   - Ulepsz `api/search.php` oraz `pages/search.php` obsługę (np. skrót CTRL+K) i zaawansowane wyniki.
7. *Design System*
   - Przenieś lub wydziel zmienne w `assets/css/` (variables, buttons, cards, forms, animations).
8. *Testy*
   - Dodaj testy PHPUnit w `tests/` dla autentykacji i CRUD.
9. *Weryfikacja zmian frontendowych i PHP*
   - Sprawdzenie logiki, uruchomienie lintingu PHP i testów Playwright.
10. *Complete pre commit steps*
   - Complete pre commit steps to ensure proper testing, verification, review, and reflection are done.
