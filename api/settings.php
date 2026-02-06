<?php
/**
 * Settings API
 * Manage site configuration settings
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getSettings();
        break;
    case 'POST':
        if (!Session::isLoggedIn()) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        updateSettings();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getSettings()
{
    try {
        $db = getDB();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        jsonResponse($settings);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function updateSettings()
{
    $input = getJsonInput();

    if (empty($input)) {
        jsonResponse(['error' => 'No settings provided'], 400);
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (:key, :value1) 
            ON DUPLICATE KEY UPDATE setting_value = :value2
        ");

        foreach ($input as $key => $value) {
            // Validate key to allow only expected settings
            $allowedKeys = [
                'couple_names',
                'wedding_date',
                'wedding_time',
                'venue_name',
                'venue_address',
                'rsvp_deadline',
                'wedding_hashtag',
                'groom_name',
                'bride_name'
            ];

            if (in_array($key, $allowedKeys)) {
                $sanitizedValue = sanitize($value);
                $stmt->execute([
                    ':key' => $key,
                    ':value1' => $sanitizedValue,
                    ':value2' => $sanitizedValue
                ]);
            }
        }

        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Settings updated successfully']);

    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}
