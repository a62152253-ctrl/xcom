# Fix forms.css label ID issues
sed -i 's/<label class="form-label">Tytuł zadania \*/<label class="form-label" for="task-name">Tytuł zadania \*/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Opis/<label class="form-label" for="task-desc">Opis/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Projekt \*/<label class="form-label" for="task-project">Projekt \*/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Przypisz do/<label class="form-label" for="task-assign">Przypisz do/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Priorytet/<label class="form-label" for="task-priority">Priorytet/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Status/<label class="form-label" for="task-status">Status/' includes/modals/task_modal.php
sed -i 's/<label class="form-label">Termin (deadline)/<label class="form-label" for="task-deadline">Termin (deadline)/' includes/modals/task_modal.php

sed -i 's/<label class="form-label">Nazwa projektu \*/<label class="form-label" for="project-name">Nazwa projektu \*/' includes/modals/project_modal.php
sed -i 's/<label class="form-label">Opis/<label class="form-label" for="project-description">Opis/' includes/modals/project_modal.php
sed -i 's/<label class="form-label">Kolor/<label class="form-label" for="project-color">Kolor/' includes/modals/project_modal.php
sed -i 's/<label class="form-label">Termin (opcjonalnie)/<label class="form-label" for="project-deadline">Termin (opcjonalnie)/' includes/modals/project_modal.php

# Fix CSS empty blocks/duplicates (since we concatenated files)
# We will just remove the duplicate blocks for buttons, forms, cards.
