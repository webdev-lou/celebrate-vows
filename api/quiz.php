<?php
/**
 * Quiz Questions API
 * CRUD operations for quiz questions
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
        getQuestions($id);
        break;
    case 'POST':
        if (!Session::isLoggedIn())
            jsonResponse(['error' => 'Unauthorized'], 401);
        createQuestion();
        break;
    case 'PUT':
        if (!Session::isLoggedIn())
            jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$id)
            jsonResponse(['error' => 'Question ID required'], 400);
        updateQuestion($id);
        break;
    case 'DELETE':
        if (!Session::isLoggedIn())
            jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$id)
            jsonResponse(['error' => 'Question ID required'], 400);
        deleteQuestion($id);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getQuestions($id = null)
{
    try {
        $db = getDB();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE id = ?");
            $stmt->execute([$id]);
            $question = $stmt->fetch();

            if ($question) {
                $question['options'] = json_decode($question['options'], true);
                jsonResponse($question);
            } else {
                jsonResponse(['error' => 'Question not found'], 404);
            }
        } else {
            $stmt = $db->query("SELECT * FROM quiz_questions ORDER BY sort_order ASC");
            $questions = $stmt->fetchAll();

            // Decode options for each question
            foreach ($questions as &$q) {
                $q['options'] = json_decode($q['options'], true);
            }

            jsonResponse(['questions' => $questions]);
        }
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function createQuestion()
{
    $input = getJsonInput();

    $question = sanitize($input['question'] ?? '');
    $options = $input['options'] ?? [];
    $correctAnswer = sanitize($input['correct_answer'] ?? '');
    $sortOrder = intval($input['sort_order'] ?? 0);

    if (empty($question) || empty($options) || empty($correctAnswer)) {
        jsonResponse(['error' => 'Question, options, and correct answer are required'], 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO quiz_questions (question, options, correct_answer, sort_order) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $question,
            json_encode($options),
            $correctAnswer,
            $sortOrder
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Question created successfully',
            'id' => $db->lastInsertId()
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function updateQuestion($id)
{
    $input = getJsonInput();

    try {
        $db = getDB();

        // Check if question exists
        $stmt = $db->prepare("SELECT id FROM quiz_questions WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Question not found'], 404);
        }

        $updates = [];
        $params = [];

        if (isset($input['question'])) {
            $updates[] = "question = ?";
            $params[] = sanitize($input['question']);
        }
        if (isset($input['options'])) {
            $updates[] = "options = ?";
            $params[] = json_encode($input['options']);
        }
        if (isset($input['correct_answer'])) {
            $updates[] = "correct_answer = ?";
            $params[] = sanitize($input['correct_answer']);
        }
        if (isset($input['sort_order'])) {
            $updates[] = "sort_order = ?";
            $params[] = intval($input['sort_order']);
        }

        if (empty($updates)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $sql = "UPDATE quiz_questions SET " . implode(", ", $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'message' => 'Question updated successfully']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function deleteQuestion($id)
{
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM quiz_questions WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Question deleted successfully']);
        } else {
            jsonResponse(['error' => 'Question not found'], 404);
        }
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
