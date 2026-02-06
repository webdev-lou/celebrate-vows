<?php
/**
 * Guests API
 * CRUD operations for wedding guests/RSVP
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        getGuests($id);
        break;
    case 'POST':
        createGuest();
        break;
    case 'PUT':
        if (!$id)
            jsonResponse(['error' => 'Guest ID required'], 400);
        updateGuest($id);
        break;
    case 'DELETE':
        if (!$id)
            jsonResponse(['error' => 'Guest ID required'], 400);
        deleteGuest($id);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getGuests($id = null)
{
    // Require admin login for viewing guests
    if (!Session::isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    try {
        $db = getDB();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM guests WHERE id = ?");
            $stmt->execute([$id]);
            $guest = $stmt->fetch();

            if ($guest) {
                $guest['quiz_answers'] = json_decode($guest['quiz_answers'], true);
                jsonResponse($guest);
            } else {
                jsonResponse(['error' => 'Guest not found'], 404);
            }
        } else {
            // Get all guests with optional filters
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;

            $sql = "SELECT * FROM guests WHERE 1=1";
            $params = [];

            if ($status && in_array($status, ['confirmed', 'declined'])) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            if ($search) {
                $sql .= " AND name LIKE ?";
                $params[] = "%$search%";
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $guests = $stmt->fetchAll();

            // Decode quiz_answers for each guest
            foreach ($guests as &$guest) {
                $guest['quiz_answers'] = json_decode($guest['quiz_answers'], true);
            }

            // Get stats
            $statsStmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
                FROM guests
            ");
            $stats = $statsStmt->fetch();

            jsonResponse([
                'guests' => $guests,
                'stats' => $stats
            ]);
        }
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function createGuest()
{
    // Public endpoint - no auth required for RSVP submission
    $input = getJsonInput();

    $name = sanitize($input['name'] ?? '');
    $status = $input['status'] ?? 'confirmed';
    $message = sanitize($input['message'] ?? '');
    $quizAnswers = $input['quiz_answers'] ?? [];
    $quizScore = $input['quiz_score'] ?? null;

    if (empty($name)) {
        jsonResponse(['error' => 'Name is required'], 400);
    }

    if (!in_array($status, ['confirmed', 'declined'])) {
        $status = 'confirmed';
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO guests (name, status, message, quiz_score, quiz_answers) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $status,
            $message,
            $quizScore,
            json_encode($quizAnswers)
        ]);

        $newId = $db->lastInsertId();

        jsonResponse([
            'success' => true,
            'message' => 'RSVP submitted successfully',
            'id' => $newId
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function updateGuest($id)
{
    // Require admin login
    if (!Session::isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();

    try {
        $db = getDB();

        // Check if guest exists
        $stmt = $db->prepare("SELECT id FROM guests WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Guest not found'], 404);
        }

        // Build update query dynamically
        $updates = [];
        $params = [];

        if (isset($input['name'])) {
            $updates[] = "name = ?";
            $params[] = sanitize($input['name']);
        }
        if (isset($input['status'])) {
            $updates[] = "status = ?";
            $params[] = in_array($input['status'], ['confirmed', 'declined']) ? $input['status'] : 'confirmed';
        }
        if (isset($input['message'])) {
            $updates[] = "message = ?";
            $params[] = sanitize($input['message']);
        }
        if (isset($input['quiz_score'])) {
            $updates[] = "quiz_score = ?";
            $params[] = intval($input['quiz_score']);
        }

        if (empty($updates)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $sql = "UPDATE guests SET " . implode(", ", $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'message' => 'Guest updated successfully']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function deleteGuest($id)
{
    // Require admin login
    if (!Session::isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM guests WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Guest deleted successfully']);
        } else {
            jsonResponse(['error' => 'Guest not found'], 404);
        }
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
