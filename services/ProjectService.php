<?php
// services/ProjectService.php
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../includes/functions.php';

class ProjectService {
    private $projectModel;

    public function __construct() {
        $this->projectModel = new ProjectModel();
    }

    public function getAllProjects($user_id) {
        return $this->projectModel->getAllProjects($user_id);
    }

    public function getProjectsWithStats($user_id, $sort, $filter) {
        $sort_sql = match($sort) {
            'deadline' => 'p.deadline ASC NULLS LAST',
            'created'  => 'p.created_at DESC',
            default    => 'p.name ASC'
        };

        $all_projects = $this->projectModel->getProjectsWithStats($user_id, $sort_sql);

        $projects = match($filter) {
            'mine'   => array_filter($all_projects, fn($p) => (int)$p['created_by'] === $user_id),
            'shared' => array_filter($all_projects, fn($p) => (int)$p['created_by'] !== $user_id),
            default  => $all_projects
        };

        return array_values($projects);
    }

    public function createProject($name, $description, $color, $deadline, $user_id) {
        if (empty($name)) {
            throw new Exception('Nazwa projektu jest wymagana.');
        }

        try {
            $this->projectModel->beginTransaction();

            $project_id = $this->projectModel->createProject($name, $description, $color, $deadline ?: null, $user_id);
            $this->projectModel->addProjectOwner($project_id, $user_id);

            $this->projectModel->commit();

            log_activity($user_id, 'project_create', 'Created project: ' . sanitize($name));
            return $project_id;
        } catch (Exception $e) {
            $this->projectModel->rollBack();
            throw new Exception('Błąd serwera: ' . $e->getMessage());
        }
    }

    public function editProject($id, $name, $description, $color, $deadline, $user_id) {
        if (!$id || !has_project_access($id, 'Administrator')) {
            throw new Exception('Brak uprawnień do edycji tego projektu.', 403);
        }

        if (empty($name)) {
            throw new Exception('Nazwa projektu jest wymagana.');
        }

        $this->projectModel->updateProject($id, $name, $description, $color, $deadline ?: null);
        log_activity($user_id, 'project_edit', 'Updated project details for project ID ' . $id);
    }

    public function archiveProject($id, $user_id) {
        if (!$id || !has_project_access($id, 'Administrator')) {
            throw new Exception('Brak uprawnień.', 403);
        }

        $this->projectModel->archiveProject($id);
        log_activity($user_id, 'project_archive', 'Archived project ID ' . $id);
    }

    public function restoreProject($id, $user_id) {
        if (!$id || !has_project_access($id, 'Administrator')) {
            throw new Exception('Brak uprawnień.', 403);
        }

        $this->projectModel->restoreProject($id);
        log_activity($user_id, 'project_restore', 'Restored archived project ID ' . $id);
    }

    public function deleteProject($id, $user_id, $user_role) {
        $proj = $this->projectModel->getArchivedProjectCreator($id);

        if (!$proj || ($user_role !== 'Owner' && $proj['created_by'] != $user_id)) {
            throw new Exception('Brak uprawnień do trwałego usunięcia.', 403);
        }

        $this->projectModel->deleteProject($id);
        log_activity($user_id, 'project_delete', 'Permanently deleted project ID ' . $id);
    }

    public function addMember($project_id, $email, $role, $user_id) {
        if (!$project_id || !has_project_access($project_id, 'Administrator')) {
            throw new Exception('Brak uprawnień.', 403);
        }

        $target_user = $this->projectModel->findUserByEmail($email);

        if (!$target_user) {
            throw new Exception('Nie znaleziono użytkownika o tym adresie e-mail.', 404);
        }

        try {
            $this->projectModel->addProjectMember($project_id, $target_user['id'], $role);

            $p_name = $this->projectModel->getProjectName($project_id);
            create_notification($target_user['id'], 'Dodano do projektu', "Zostałeś dodany do projektu '$p_name' jako $role.", 'system');

            log_activity($user_id, 'project_add_member', "Added $email to project $project_id");
        } catch (PDOException $e) {
            throw new Exception('Ten użytkownik jest już członkiem projektu.', 400);
        }
    }
}
