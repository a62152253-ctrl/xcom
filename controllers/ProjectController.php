<?php
// controllers/ProjectController.php
require_once __DIR__ . '/../services/ProjectService.php';
require_once __DIR__ . '/../includes/session.php';

class ProjectController {
    public static function handleRequest($action, $method, $input, $get) {
        try {
            if ($method === 'GET') {
                if (empty($action) || $action === 'list') {
                    $projects = ProjectService::getUserProjects($_SESSION['user_id']);
                    return ['projects' => $projects];
                }
            }

            if ($method === 'POST') {
                if ($action === 'create') {
                    $data = [
                        'name' => trim($input['name'] ?? ''),
                        'description' => trim($input['description'] ?? ''),
                        'color' => trim($input['color'] ?? '#3b82f6'),
                        'deadline' => trim($input['deadline'] ?? null),
                        'created_by' => $_SESSION['user_id']
                    ];

                    if (empty($data['name'])) {
                        http_response_code(400);
                        return ['error' => 'Nazwa projektu jest wymagana.'];
                    }

                    $project_id = ProjectService::createProject($data);
                    return ['success' => true, 'project_id' => $project_id];
                }

                if ($action === 'edit') {
                    $project_id = (int)($input['id'] ?? 0);
                    $data = [
                        'name' => trim($input['name'] ?? ''),
                        'description' => trim($input['description'] ?? ''),
                        'color' => trim($input['color'] ?? '#3b82f6'),
                        'deadline' => trim($input['deadline'] ?? null)
                    ];

                    if (!$project_id || empty($data['name'])) {
                        http_response_code(400);
                        return ['error' => 'Brak wymaganych danych.'];
                    }

                    ProjectService::updateProject($project_id, $data);
                    return ['success' => true];
                }

                if ($action === 'archive') {
                    $project_id = (int)($input['id'] ?? 0);
                    if (!$project_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID projektu.'];
                    }
                    ProjectService::archiveProject($project_id);
                    return ['success' => true];
                }

                if ($action === 'restore') {
                    $project_id = (int)($input['id'] ?? 0);
                    if (!$project_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID projektu.'];
                    }
                    ProjectService::restoreProject($project_id);
                    return ['success' => true];
                }

                if ($action === 'delete') {
                    $project_id = (int)($input['id'] ?? 0);
                    if (!$project_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID projektu.'];
                    }
                    ProjectService::deleteProject($project_id);
                    return ['success' => true];
                }

                if ($action === 'add_member') {
                    $project_id = (int)($input['project_id'] ?? 0);
                    $email = trim($input['email'] ?? '');
                    $role = $input['role'] ?? 'Member';

                    if (!$project_id || empty($email)) {
                        http_response_code(400);
                        return ['error' => 'Brak wymaganych danych.'];
                    }

                    ProjectService::addMember($project_id, $email, $role);
                    return ['success' => true];
                }
            }

            http_response_code(400);
            return ['error' => 'Błędne zapytanie'];
        } catch (Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
}
