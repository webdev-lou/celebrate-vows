<?php
/**
 * Media Upload API
 * Handles photo and video uploads from guests
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

// Upload config
define('UPLOAD_DIR', __DIR__ . '/../uploads/media/');
define('MAX_FILE_SIZE', 400 * 1024 * 1024); // 400MB
define('MAX_FILES', 50);

$ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-msvideo'];
$ALLOWED_TYPES = array_merge($ALLOWED_IMAGE_TYPES, $ALLOWED_VIDEO_TYPES);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getMedia();
        break;
    case 'POST':
        uploadMedia();
        break;
    case 'DELETE':
        deleteMedia();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

// ================================
// Create table if not exists
// ================================
function ensureTable()
{
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS media_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_name VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('image', 'video') NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT NOT NULL,
            uploader_name VARCHAR(255) DEFAULT 'Anonymous',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// ================================
// GET - List media (admin only)
// ================================
function getMedia()
{
    if (!Session::isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    try {
        ensureTable();
        $db = getDB();

        $type = $_GET['type'] ?? 'all';
        $sql = "SELECT * FROM media_uploads WHERE 1=1";
        $params = [];

        if ($type === 'image') {
            $sql .= " AND file_type = 'image'";
        } elseif ($type === 'video') {
            $sql .= " AND file_type = 'video'";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $media = $stmt->fetchAll();

        // Get stats
        $statsStmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN file_type = 'image' THEN 1 ELSE 0 END) as images,
                SUM(CASE WHEN file_type = 'video' THEN 1 ELSE 0 END) as videos,
                COALESCE(SUM(file_size), 0) as total_size
            FROM media_uploads
        ");
        $stats = $statsStmt->fetch();

        jsonResponse([
            'media' => $media,
            'stats' => $stats
        ]);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ================================
// POST - Upload media (public)
// ================================
function uploadMedia()
{
    global $ALLOWED_TYPES, $ALLOWED_IMAGE_TYPES;

    try {
        // Disable timeout for large uploads
        set_time_limit(0);

        ensureTable();

        // Create upload directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // Check if files were uploaded
        if (empty($_FILES['files'])) {
            jsonResponse(['error' => 'No files uploaded'], 400);
        }

        $uploaderName = trim($_POST['uploader_name'] ?? 'Anonymous');
        if (empty($uploaderName)) {
            $uploaderName = 'Anonymous';
        }

        $files = $_FILES['files'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        if ($fileCount > MAX_FILES) {
            jsonResponse(['error' => 'Maximum ' . MAX_FILES . ' files allowed per upload'], 400);
        }

        $uploaded = [];
        $errors = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            // Check for upload errors
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "$name: Upload failed (error code: $error)";
                continue;
            }

            // Validate file size
            if ($size > MAX_FILE_SIZE) {
                $errors[] = "$name: File too large (max 400MB)";
                continue;
            }

            // Validate MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);

            if (!in_array($mimeType, $ALLOWED_TYPES)) {
                $errors[] = "$name: Invalid file type. Only photos and videos are allowed.";
                continue;
            }

            // Determine file type
            $fileType = in_array($mimeType, $ALLOWED_IMAGE_TYPES) ? 'image' : 'video';

            // Generate unique filename
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $uniqueName = uniqid($fileType . '_', true) . '.' . $extension;
            $destination = UPLOAD_DIR . $uniqueName;

            // Move uploaded file
            if (move_uploaded_file($tmpName, $destination)) {
                // Save to database
                $db = getDB();
                $stmt = $db->prepare("
                    INSERT INTO media_uploads (original_name, file_name, file_path, file_type, mime_type, file_size, uploader_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name,
                    $uniqueName,
                    'uploads/media/' . $uniqueName,
                    $fileType,
                    $mimeType,
                    $size,
                    sanitize($uploaderName)
                ]);

                $uploaded[] = [
                    'id' => $db->lastInsertId(),
                    'name' => $name,
                    'type' => $fileType
                ];
            } else {
                $errors[] = "$name: Failed to save file";
            }
        }

        $response = [
            'success' => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'uploaded_count' => count($uploaded),
            'total_files' => $fileCount
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        jsonResponse($response, count($uploaded) > 0 ? 201 : 400);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ================================
// DELETE - Remove media (admin only)
// ================================
function deleteMedia()
{
    if (!Session::isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Media ID required'], 400);
    }

    try {
        $db = getDB();

        // Get file info first
        $stmt = $db->prepare("SELECT * FROM media_uploads WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();

        if (!$media) {
            jsonResponse(['error' => 'Media not found'], 404);
        }

        // Delete file from disk
        $filePath = __DIR__ . '/../' . $media['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $stmt = $db->prepare("DELETE FROM media_uploads WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Media deleted successfully']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
