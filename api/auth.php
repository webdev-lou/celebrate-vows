<?php
/**
 * Authentication API
 * Handles admin login, logout, and session checks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkSession();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

function handleLogin()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $input = getJsonInput();
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Username and password are required'], 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            Session::login($user['id'], $user['username']);
            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
        } else {
            jsonResponse(['error' => 'Invalid username or password'], 401);
        }
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleLogout()
{
    Session::logout();
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

function checkSession()
{
    if (Session::isLoggedIn()) {
        jsonResponse([
            'authenticated' => true,
            'user' => [
                'id' => Session::getAdminId(),
                'username' => Session::getAdminUsername()
            ]
        ]);
    } else {
        jsonResponse(['authenticated' => false], 401);
    }
}
