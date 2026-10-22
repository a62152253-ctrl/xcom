<?php
// api/profile.php - User profile & settings updates
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // When using multipart/form-data for file uploads, JSON payload might be empty
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // UPDATE PROFILE
    if ($action === 'settings') {
        $full_name = trim($input['full_name'] ?? '');
        $language = in_array($input['language'] ?? 'pl', ['pl', 'en']) ? $input['language'] : 'pl';
        $password = trim($input['password'] ?? '');
        $confirm_password = trim($input['confirm_password'] ?? '');

        if (strlen($full_name) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Imię i nazwisko za długie.']);
            exit;
        }

        try {
            $db->beginTransaction();

            // Update full_name
            if (!empty($full_name)) {
                $stmt = $db->prepare("UPDATE users SET full_name=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$full_name, $user_id]);
            }

            // Update password if provided
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception('Hasło musi mieć co najmniej 8 znaków.');
                }
                if ($password !== $confirm_password) {
                    throw new Exception('Hasła nie pasują.');
                }
                $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
                $stmt = $db->prepare("UPDATE users SET password_hash=? WHERE id=?");
                $stmt->execute([$hash, $user_id]);
            }

            // Update settings
            $stmt = $db->prepare("UPDATE settings SET language=?, email_notifications=? WHERE user_id=?");
            $stmt->execute([$language, (int)($input['email_notifications'] ?? 1), $user_id]);

            // Handle file upload securely
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if ($file['size'] > $max_size) {
                    throw new Exception('Plik jest za duży (max 2MB).');
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($mime_type, $allowed_mimes)) {
                    throw new Exception('Niedozwolony format pliku. Użyj JPG, PNG lub WEBP.');
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array(strtolower($ext), $allowed_exts)) {
                    throw new Exception('Niedozwolone rozszerzenie pliku.');
                }

                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $avatar_url = '/uploads/' . $filename;
                    $stmt = $db->prepare("UPDATE users SET avatar=? WHERE id=?");
                    $stmt->execute([$avatar_url, $user_id]);
                    $_SESSION['user_avatar'] = $avatar_url;
                } else {
                    throw new Exception('Błąd podczas zapisywania pliku.');
                }
            }

            $db->commit();

            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_language'] = $language;

            log_activity($user_id, 'profile_update', 'Updated profile settings');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // UPDATE THEME
    if ($action === 'theme') {
        $theme = in_array($input['theme'] ?? 'dark', ['light', 'dark']) ? $input['theme'] : 'dark';

        try {
            $stmt = $db->prepare("UPDATE settings SET theme=? WHERE user_id=?");
            $stmt->execute([$theme, $user_id]);

            $_SESSION['user_theme'] = $theme;

            log_activity($user_id, 'theme_change', "Changed theme to $theme");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("Theme update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne żądanie.']);
exit;
