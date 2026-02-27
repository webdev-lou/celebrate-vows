<?php
/**
 * Media Upload API
 * Handles photo and video uploads from guests
 * Supports chunked uploads for large files
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
define('CHUNK_DIR', __DIR__ . '/../uploads/chunks/');
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
        $action = $_POST['action'] ?? 'upload';
        if ($action === 'chunk') {
            handleChunkUpload();
        } elseif ($action === 'assemble') {
            assembleChunks();
        } else {
            uploadMedia();
        }
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
// POST - Handle chunk upload
// ================================
function handleChunkUpload()
{
    set_time_limit(120);

    if (!is_dir(CHUNK_DIR)) {
        mkdir(CHUNK_DIR, 0755, true);
    }

    $uploadId = $_POST['upload_id'] ?? null;
    $chunkIndex = intval($_POST['chunk_index'] ?? -1);
    $totalChunks = intval($_POST['total_chunks'] ?? 0);

    if (!$uploadId || $chunkIndex < 0 || $totalChunks <= 0) {
        jsonResponse(['error' => 'Missing chunk parameters'], 400);
    }

    // Sanitize upload_id to prevent directory traversal
    $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId);

    if (empty($_FILES['chunk'])) {
        jsonResponse(['error' => 'No chunk data received'], 400);
    }

    $chunk = $_FILES['chunk'];
    if ($chunk['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Chunk upload error: ' . $chunk['error']], 400);
    }

    // Create directory for this upload
    $uploadChunkDir = CHUNK_DIR . $uploadId . '/';
    if (!is_dir($uploadChunkDir)) {
        mkdir($uploadChunkDir, 0755, true);
    }

    // Save chunk
    $chunkPath = $uploadChunkDir . 'chunk_' . str_pad($chunkIndex, 5, '0', STR_PAD_LEFT);
    if (!move_uploaded_file($chunk['tmp_name'], $chunkPath)) {
        jsonResponse(['error' => 'Failed to save chunk'], 500);
    }

    jsonResponse([
        'success' => true,
        'chunk_index' => $chunkIndex,
        'received' => $chunkIndex + 1,
        'total' => $totalChunks
    ]);
}

// ================================
// POST - Assemble chunks into file
// ================================
function assembleChunks()
{
    global $ALLOWED_TYPES, $ALLOWED_IMAGE_TYPES;

    set_time_limit(300);

    $uploadId = $_POST['upload_id'] ?? null;
    $fileName = $_POST['file_name'] ?? 'unknown';
    $totalChunks = intval($_POST['total_chunks'] ?? 0);
    $totalSize = intval($_POST['total_size'] ?? 0);
    $uploaderName = trim($_POST['uploader_name'] ?? 'Anonymous');

    if (!$uploadId || $totalChunks <= 0) {
        jsonResponse(['error' => 'Missing assemble parameters'], 400);
    }

    $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId);
    $uploadChunkDir = CHUNK_DIR . $uploadId . '/';

    if (!is_dir($uploadChunkDir)) {
        jsonResponse(['error' => 'Upload chunks not found'], 404);
    }

    // Validate total file size
    if ($totalSize > MAX_FILE_SIZE) {
        // Clean up chunks
        cleanupChunks($uploadChunkDir);
        jsonResponse(['error' => 'File too large (max 400MB)'], 400);
    }

    try {
        ensureTable();

        // Create upload directory
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // Determine extension and generate unique name
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueName = uniqid('media_', true) . '.' . $extension;
        $destination = UPLOAD_DIR . $uniqueName;

        // Assemble chunks into final file
        $destFile = fopen($destination, 'wb');
        if (!$destFile) {
            cleanupChunks($uploadChunkDir);
            jsonResponse(['error' => 'Failed to create output file'], 500);
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $uploadChunkDir . 'chunk_' . str_pad($i, 5, '0', STR_PAD_LEFT);
            if (!file_exists($chunkPath)) {
                fclose($destFile);
                unlink($destination);
                cleanupChunks($uploadChunkDir);
                jsonResponse(['error' => "Missing chunk $i"], 400);
            }
            $chunkData = file_get_contents($chunkPath);
            fwrite($destFile, $chunkData);
            unset($chunkData); // Free memory
        }
        fclose($destFile);

        // Clean up chunks
        cleanupChunks($uploadChunkDir);

        // Validate MIME type of assembled file
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($destination);

        if (!in_array($mimeType, $ALLOWED_TYPES)) {
            unlink($destination);
            jsonResponse(['error' => 'Invalid file type: ' . $mimeType], 400);
        }

        // Determine file type
        $fileType = in_array($mimeType, $ALLOWED_IMAGE_TYPES) ? 'image' : 'video';

        // Rename with proper prefix
        $properName = uniqid($fileType . '_', true) . '.' . $extension;
        $properDest = UPLOAD_DIR . $properName;
        rename($destination, $properDest);

        // Get actual file size
        $actualSize = filesize($properDest);

        // Save to database
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO media_uploads (original_name, file_name, file_path, file_type, mime_type, file_size, uploader_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $fileName,
            $properName,
            'uploads/media/' . $properName,
            $fileType,
            $mimeType,
            $actualSize,
            sanitize($uploaderName ?: 'Anonymous')
        ]);

        jsonResponse([
            'success' => true,
            'uploaded' => [
                [
                    'id' => $db->lastInsertId(),
                    'name' => $fileName,
                    'type' => $fileType
                ]
            ],
            'uploaded_count' => 1,
            'total_files' => 1
        ], 201);

    } catch (Exception $e) {
        jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}

function cleanupChunks($dir)
{
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
        rmdir($dir);
    }
}

// ================================
// POST - Upload media (public) - for small files
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
