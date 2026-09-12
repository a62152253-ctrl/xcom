<?php
// controllers/ProjectController.php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../includes/functions.php';

class ProjectController {
    private $db;
    private $projectModel;
    private $userId;

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->projectModel = new Project($db);
        $this->userId = $userId;
    }

    public function handleRequest($method, $action) {
        if ($method === 'GET') {
            // Get all accessible projects
            $projects = $this->projectModel->listAccessible($this->userId);
            echo json_encode(['projects' => $projects]);
            exit;
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            switch ($action) {
                case 'create':
                    $this->createProject($input);
                    break;
                case 'edit':
                    $this->editProject($input);
                    break;
                case 'archive':
                    $this->archiveProject($input);
                    break;
                case 'restore':
                    $this->restoreProject($input);
                    break;
                case 'delete':
                    $this->deleteProject($input);
                    break;
                case 'add_member':
                    $this->addMember($input);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Błędne zapytanie']);
                    exit;
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
    }

    private function createProject($input) {
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa projektu jest wymagana.']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            $project_id = $this->projectModel->create($name, $description, $color, $deadline, $this->userId);
            $this->projectModel->addMember($project_id, $this->userId, 'Owner');

            $this->db->commit();

            log_activity($this->userId, 'project_create', 'Created project: ' . sanitize($name));
            echo json_encode(['success' => true, 'project_id' => $project_id]);
            exit;
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
            exit;
        }
    }

    private function editProject($input) {
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);

        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do edycji tego projektu.']);
            exit;
        }

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa projektu jest wymagana.']);
            exit;
        }

        $this->projectModel->edit($id, $name, $description, $color, $deadline);

        log_activity($this->userId, 'project_edit', 'Updated project details for project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    private function archiveProject($input) {
        $id = $input['id'] ?? null;

        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $this->projectModel->archive($id);

        log_activity($this->userId, 'project_archive', 'Archived project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    private function restoreProject($input) {
        $id = (int)($input['id'] ?? 0);
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        $this->projectModel->restore($id);
        log_activity($this->userId, 'project_restore', 'Restored archived project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    private function deleteProject($input) {
        $id = (int)($input['id'] ?? 0);
        $proj = $this->projectModel->getProjectCreator($id);
        if (!$proj || ($_SESSION['user_role'] !== 'Owner' && $proj['created_by'] != $this->userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do trwałego usunięcia.']);
            exit;
        }
        $this->projectModel->delete($id);
        log_activity($this->userId, 'project_delete', 'Permanently deleted project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    private function addMember($input) {
        $project_id = $input['project_id'] ?? null;
        $email = trim($input['email'] ?? '');
        $role = $input['role'] ?? 'Member';

        if (!$project_id || !has_project_access($project_id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $target_user = $this->projectModel->getUserByEmail($email);

        if (!$target_user) {
            http_response_code(404);
            echo json_encode(['error' => 'Nie znaleziono użytkownika o tym adresie e-mail.']);
            exit;
        }

        try {
            $this->projectModel->addMember($project_id, $target_user['id'], $role);

            $p_name = $this->projectModel->getProjectName($project_id);

            create_notification($target_user['id'], 'Dodano do projektu', "Zostałeś dodany do projektu '$p_name' jako $role.", 'system');

            log_activity($this->userId, 'project_add_member', "Added $email to project $project_id");
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Ten użytkownik jest już członkiem projektu.']);
            exit;
        }
    }
}
