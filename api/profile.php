<?php
// api/profile.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Save Theme Preference
    if ($action === 'theme') {
        $theme = $input['theme'] ?? 'light';
        if (!in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }
        
        $stmt = $db->prepare("UPDATE settings SET theme = ? WHERE user_id = ?");
        $stmt->execute([$theme, $user_id]);
        
        $_SESSION['user_theme'] = $theme;
        echo json_encode(['success' => true]);
        exit;
    }

    // Save Full Settings (language, notifications, password, profile info)
    if ($action === 'settings') {
        // We will read normal POST form data if it's sent via application/x-www-form-urlencoded or multipart/form-data
        $full_name = trim($_POST['full_name'] ?? '');
        $language = $_POST['language'] ?? 'pl';
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        
        if (!in_array($language, ['pl', 'en'])) {
            $language = 'pl';
        }
        
        if (empty($full_name)) {
            echo json_encode(['error' => 'Imię i nazwisko nie mogą być puste.']);
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            // Update User Profile
            $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$full_name, $user_id]);
            $_SESSION['user_name'] = $full_name;
            
            // Update User Settings
            $stmt_settings = $db->prepare("UPDATE settings SET language = ?, email_notifications = ? WHERE user_id = ?");
            $stmt_settings->execute([$language, $email_notifications, $user_id]);
            $_SESSION['user_language'] = $language;
            
            // Handle Password Change if requested
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Hasło musi mieć co najmniej 6 znaków.');
                }
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pw = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt_pw->execute([$new_hash, $user_id]);
                log_activity($user_id, 'password_change', 'User updated password');
            }
            
            // Handle Avatar Upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception('Niedozwolony format pliku awatara. Użyj JPG, PNG lub GIF.');
                }
                
                if ($file['size'] > MAX_FILE_SIZE) {
                    throw new Exception('Rozmiar pliku awatara przekracza 5MB.');
                }
                
                // Create uploads directory if not exists
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                
                $avatar_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $avatar_path = '/uploads/' . $avatar_name;
                
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $avatar_name)) {
                    $stmt_av = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt_av->execute([$avatar_path, $user_id]);
                    $_SESSION['user_avatar'] = $avatar_path;
                } else {
                    throw new Exception('Nie udało się zapisać pliku awatara.');
                }
            }
            
            $db->commit();
            log_activity($user_id, 'profile_update', 'Updated profile settings');
            
            header("Location: /pages/settings.php?success=1");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: /pages/settings.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid Request']);
exit;
