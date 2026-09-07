1. **Analyze existing code structure**: The project has business logic and database queries mixed with HTML in `pages/*.php` files.
2. **Implement MVC/Service-oriented Architecture**:
    *   Create `models/` for database interactions.
    *   Create `services/` for business logic (if needed).
    *   Create `controllers/` to handle requests (optional, given the current API structure, might stick to API endpoints + pure view pages).
3. **Componentize Views**:
    *   Extract recurring UI elements (modals, cards) into `includes/modals/` and `includes/components/`.
    *   Update pages to use `require_once` or simple includes for these components.
4. **Refactor API**:
    *   Clean up `api/*.php` to use models/services instead of raw SQL queries where possible.
5. **Testing & Verification**:
    *   Ensure the application still works.
