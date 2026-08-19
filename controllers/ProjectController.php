<?php
// controllers/ProjectController.php
require_once __DIR__ . '/../services/ProjectService.php';

class ProjectController {
    private $projectService;

    public function __construct() {
        $this->projectService = new ProjectService();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'] ?? 'Member';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'list') { // Modified original API that returned all without action, to be explicit if needed, but original used action='' for list
                $projects = $this->projectService->getAllProjects($user_id);
                echo json_encode(['projects' => $projects]);
                exit;
            } else {
                $projects = $this->projectService->getAllProjects($user_id);
                echo json_encode(['projects' => $projects]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            try {
                switch ($action) {
                    case 'create':
                        $name = trim($input['name'] ?? '');
                        $description = trim($input['description'] ?? '');
                        $color = trim($input['color'] ?? '#3b82f6');
                        $deadline = trim($input['deadline'] ?? null);

                        $project_id = $this->projectService->createProject($name, $description, $color, $deadline, $user_id);
                        echo json_encode(['success' => true, 'project_id' => $project_id]);
                        break;

                    case 'edit':
                        $id = $input['id'] ?? null;
                        $name = trim($input['name'] ?? '');
                        $description = trim($input['description'] ?? '');
                        $color = trim($input['color'] ?? '#3b82f6');
                        $deadline = trim($input['deadline'] ?? null);

                        $this->projectService->editProject($id, $name, $description, $color, $deadline, $user_id);
                        echo json_encode(['success' => true]);
                        break;

                    case 'archive':
                        $id = $input['id'] ?? null;
                        $this->projectService->archiveProject($id, $user_id);
                        echo json_encode(['success' => true]);
                        break;

                    case 'restore':
                        $id = (int)($input['id'] ?? 0);
                        $this->projectService->restoreProject($id, $user_id);
                        echo json_encode(['success' => true]);
                        break;

                    case 'delete':
                        $id = (int)($input['id'] ?? 0);
                        $this->projectService->deleteProject($id, $user_id, $user_role);
                        echo json_encode(['success' => true]);
                        break;

                    case 'add_member':
                        $project_id = $input['project_id'] ?? null;
                        $email = trim($input['email'] ?? '');
                        $role = $input['role'] ?? 'Member';

                        $this->projectService->addMember($project_id, $email, $role, $user_id);
                        echo json_encode(['success' => true]);
                        break;

                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Błędne zapytanie']);
                        break;
                }
            } catch (Exception $e) {
                $code = $e->getCode();
                if (!$code || $code < 100 || $code > 599) { $code = 400; }
                http_response_code($code);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Błędne zapytanie']);
        exit;
    }
}
