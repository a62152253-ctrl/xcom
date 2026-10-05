<?php
// controllers/ProjectController.php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../includes/functions.php';

class ProjectController {
    public static function handleGet($db, $user_id) {
        $projects = Project::getAll($db, $user_id);
        self::ok(['projects' => $projects]);
    }

    public static function handlePost($db, $user_id, $action, $input) {
        if ($action === 'create') self::createProject($db, $user_id, $input);
        if ($action === 'edit') self::editProject($db, $user_id, $input);
        if ($action === 'archive') self::archiveProject($db, $user_id, $input);
        if ($action === 'restore') self::restoreProject($db, $user_id, $input);
        if ($action === 'delete') self::deleteProject($db, $user_id, $input);
        if ($action === 'add_member') self::addMember($db, $user_id, $input);
    }

    private static function err($code, $msg) {
        http_response_code($code);
        echo json_encode(['error' => $msg]);
        exit;
    }

    private static function ok($data) {
        echo json_encode($data);
        exit;
    }

    private static function createProject($db, $user_id, $input) {
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);

        if (empty($name)) self::err(400, 'Nazwa projektu jest wymagana.');

        try {
            $db->beginTransaction();
            $project_id = Project::create($db, $name, $description, $color, $deadline, $user_id);
            $db->commit();
            log_activity($user_id, 'project_create', 'Created project: ' . sanitize($name));
            self::ok(['success' => true, 'project_id' => $project_id]);
        } catch (Exception $e) {
            $db->rollBack();
            self::err(500, 'Błąd serwera: ' . $e->getMessage());
        }
    }

    private static function editProject($db, $user_id, $input) {
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);

        if (!$id || !has_project_access($id, 'Administrator')) self::err(403, 'Brak uprawnień do edycji tego projektu.');
        if (empty($name)) self::err(400, 'Nazwa projektu jest wymagana.');

        Project::update($db, $id, $name, $description, $color, $deadline);
        log_activity($user_id, 'project_edit', 'Updated project details for project ID ' . $id);
        self::ok(['success' => true]);
    }

    private static function archiveProject($db, $user_id, $input) {
        $id = $input['id'] ?? null;
        if (!$id || !has_project_access($id, 'Administrator')) self::err(403, 'Brak uprawnień.');
        Project::archive($db, $id);
        log_activity($user_id, 'project_archive', 'Archived project ID ' . $id);
        self::ok(['success' => true]);
    }

    private static function restoreProject($db, $user_id, $input) {
        $id = (int)($input['id'] ?? 0);
        if (!$id || !has_project_access($id, 'Administrator')) self::err(403, 'Brak uprawnień.');
        Project::restore($db, $id);
        log_activity($user_id, 'project_restore', 'Restored archived project ID ' . $id);
        self::ok(['success' => true]);
    }

    private static function deleteProject($db, $user_id, $input) {
        $id = (int)($input['id'] ?? 0);
        $proj = Project::getArchivedCreator($db, $id);
        if (!$proj || ($_SESSION['user_role'] !== 'Owner' && $proj['created_by'] != $user_id)) {
            self::err(403, 'Brak uprawnień do trwałego usunięcia.');
        }
        Project::delete($db, $id);
        log_activity($user_id, 'project_delete', 'Permanently deleted project ID ' . $id);
        self::ok(['success' => true]);
    }

    private static function addMember($db, $user_id, $input) {
        $project_id = $input['project_id'] ?? null;
        $email = trim($input['email'] ?? '');
        $role = $input['role'] ?? 'Member';

        if (!$project_id || !has_project_access($project_id, 'Administrator')) self::err(403, 'Brak uprawnień.');

        $target_user = Project::findUserByEmail($db, $email);
        if (!$target_user) self::err(404, 'Nie znaleziono użytkownika o tym adresie e-mail.');

        try {
            Project::addMember($db, $project_id, $target_user['id'], $role);
            $p_name = Project::getById($db, $project_id)['name'] ?? 'Projekt';
            create_notification($target_user['id'], 'Dodano do projektu', "Zostałeś dodany do projektu '$p_name' jako $role.", 'system');
            log_activity($user_id, 'project_add_member', "Added $email to project $project_id");
            self::ok(['success' => true]);
        } catch (PDOException $e) {
            self::err(400, 'Ten użytkownik jest już członkiem projektu.');
        }
    }
}
