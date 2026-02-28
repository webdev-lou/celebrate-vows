<?php
/**
 * Media Download API
 * Creates a ZIP archive of all uploaded media and streams it to the browser
 */

// Catch any stray output
ob_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

// Discard any output from includes
ob_end_clean();

// Admin only
if (!Session::isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized');
}

set_time_limit(0);
ini_set('memory_limit', '512M');

$uploadDir = __DIR__ . '/../uploads/media/';

// Get filter type
$type = $_GET['type'] ?? 'all';

try {
    $db = getDB();
    $sql = "SELECT * FROM media_uploads WHERE 1=1";

    if ($type === 'image') {
        $sql .= " AND file_type = 'image'";
    } elseif ($type === 'video') {
        $sql .= " AND file_type = 'video'";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $media = $stmt->fetchAll();

    if (empty($media)) {
        http_response_code(404);
        die('No media files found');
    }

    // Create ZIP filename
    $dateStr = date('Y-m-d');
    $typeLabel = $type === 'all' ? 'all-media' : ($type === 'image' ? 'photos' : 'videos');
    $zipFilename = "miko-mae-wedding-{$typeLabel}-{$dateStr}.zip";
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFilename;

    // Remove old temp file if exists
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();
    $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result !== true) {
        http_response_code(500);
        die('Could not create ZIP file (error code: ' . $result . ')');
    }

    $addedCount = 0;
    foreach ($media as $item) {
        $filePath = __DIR__ . '/../' . $item['file_path'];
        if (file_exists($filePath)) {
            // Organize by uploader name
            $uploaderFolder = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $item['uploader_name'] ?: 'Anonymous');
            $entryName = $uploaderFolder . '/' . $item['original_name'];

            // Handle duplicate filenames
            $counter = 1;
            $baseName = pathinfo($item['original_name'], PATHINFO_FILENAME);
            $ext = pathinfo($item['original_name'], PATHINFO_EXTENSION);
            while ($zip->locateName($entryName) !== false) {
                $entryName = $uploaderFolder . '/' . $baseName . '_' . $counter . '.' . $ext;
                $counter++;
            }

            $zip->addFile($filePath, $entryName);
            $addedCount++;
        }
    }

    if ($addedCount === 0) {
        $zip->close();
        if (file_exists($zipPath))
            unlink($zipPath);
        http_response_code(404);
        die('No files could be added to the archive');
    }

    // Must close before reading - this actually writes the ZIP
    $zip->close();

    // Verify the ZIP was created successfully
    if (!file_exists($zipPath) || filesize($zipPath) === 0) {
        http_response_code(500);
        die('ZIP file creation failed');
    }

    $fileSize = filesize($zipPath);

    // Clean any remaining output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Stream the ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // Use chunked reading for large files
    $handle = fopen($zipPath, 'rb');
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);

    // Clean up temp file
    unlink($zipPath);
    exit;

} catch (Exception $e) {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
