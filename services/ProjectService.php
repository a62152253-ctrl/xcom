<?php
// services/ProjectService.php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../security/Permissions.php';
require_once __DIR__ . '/../includes/functions.php';

class ProjectService {
    public static function getUserProjects($user_id) {
        $db = Database::getInstance()->getConnection();
        return Project::getUserProjects($db, $user_id);
    }

    public static function getProjectById($project_id) {
        $db = Database::getInstance()->getConnection();
        return Project::getProjectById($db, $project_id);
    }

    public static function createProject($data) {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();
            $project_id = Project::createProject($db, $data);
            Project::addMember($db, $project_id, $data['created_by'], 'Owner');
            $db->commit();

            log_activity($data['created_by'], 'project_create', 'Created project: ' . $data['name']);
            return $project_id;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updateProject($project_id, $data) {
        if (!Permissions::hasProjectAccess($project_id, 'Administrator')) {
            throw new Exception("Brak uprawnień do edycji tego projektu.");
        }

        $db = Database::getInstance()->getConnection();
        Project::updateProject($db, $project_id, $data);
        log_activity($_SESSION['user_id'], 'project_edit', 'Updated project details for project ID ' . $project_id);
        return true;
    }

    public static function archiveProject($project_id) {
        if (!Permissions::hasProjectAccess($project_id, 'Administrator')) {
            throw new Exception("Brak uprawnień.");
        }

        $db = Database::getInstance()->getConnection();
        Project::archiveProject($db, $project_id);
        log_activity($_SESSION['user_id'], 'project_archive', 'Archived project ID ' . $project_id);
        return true;
    }

    public static function restoreProject($project_id) {
        if (!Permissions::hasProjectAccess($project_id, 'Administrator')) {
            throw new Exception("Brak uprawnień.");
        }

        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?")->execute([$project_id]);
        log_activity($_SESSION['user_id'], 'project_restore', 'Restored archived project ID ' . $project_id);
        return true;
    }

    public static function deleteProject($project_id) {
        $db = Database::getInstance()->getConnection();
        $check = $db->prepare("SELECT created_by FROM projects WHERE id = ? AND is_archived = 1");
        $check->execute([$project_id]);
        $proj = $check->fetch();

        if (!$proj || ($_SESSION['user_role'] !== 'Owner' && $proj['created_by'] != $_SESSION['user_id'])) {
            throw new Exception("Brak uprawnień do trwałego usunięcia.");
        }

        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
        log_activity($_SESSION['user_id'], 'project_delete', 'Permanently deleted project ID ' . $project_id);
        return true;
    }

    public static function addMember($project_id, $email, $role) {
        if (!Permissions::hasProjectAccess($project_id, 'Administrator')) {
            throw new Exception("Brak uprawnień.");
        }

        $db = Database::getInstance()->getConnection();
        $target_user = User::findByEmail($db, $email);

        if (!$target_user) {
            throw new Exception("Nie znaleziono użytkownika o tym adresie e-mail.");
        }

        try {
            Project::addMember($db, $project_id, $target_user['id'], $role);

            $project = Project::getProjectById($db, $project_id);
            create_notification($target_user['id'], 'Dodano do projektu', "Zostałeś dodany do projektu '{$project['name']}' jako $role.", 'system');
            log_activity($_SESSION['user_id'], 'project_add_member', "Added $email to project $project_id");
            return true;
        } catch (PDOException $e) {
            throw new Exception("Ten użytkownik jest już członkiem projektu.");
        }
    }
}
