<?php
/**
 * Database Installation Script
 * Creates all necessary tables and default admin user
 * 
 * IMPORTANT: Delete this file after installation for security!
 */

require_once __DIR__ . '/../api/config.php';

// Prevent running in production
if (!DEBUG_MODE) {
    die('Installation disabled in production mode.');
}

$messages = [];
$errors = [];

try {
    // Connect without database first to create it if needed
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $messages[] = "Database '" . DB_NAME . "' ready.";

    // Create admin_users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Table 'admin_users' created.";

    // Create guests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `guests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `status` ENUM('confirmed', 'declined') NOT NULL DEFAULT 'confirmed',
            `message` TEXT,
            `quiz_score` INT DEFAULT NULL,
            `quiz_answers` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Table 'guests' created.";

    // Create quiz_questions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `quiz_questions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `question` VARCHAR(255) NOT NULL,
            `options` JSON NOT NULL,
            `correct_answer` VARCHAR(100) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Table 'quiz_questions' created.";

    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(50) NOT NULL UNIQUE,
            `setting_value` TEXT,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Table 'settings' created.";

    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;

    if (!$adminExists) {
        // Create default admin user
        $defaultPassword = 'MikoMae2026!';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $hashedPassword]);
        $messages[] = "Default admin user created.";
        $messages[] = "Username: admin";
        $messages[] = "Password: " . $defaultPassword;
    } else {
        $messages[] = "Admin user already exists.";
    }

    // Insert default quiz questions if none exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM quiz_questions");
    $questionCount = $stmt->fetchColumn();

    if ($questionCount == 0) {
        $defaultQuestions = [
            ['Where was our first date?', '["At a coffee shop","In the park","At the movies","Fancy dinner"]', 'At a coffee shop', 1],
            ['Who said "I love you" first?', '["Miko","Mae","At the same time","The cat"]', 'At the same time', 2],
            ['What is Mae\'s favorite dessert?', '["Chocolate Cake","Ice Cream","Cheesecake","Fresh Fruit"]', 'Chocolate Cake', 3],
            ['Where did Miko propose?', '["At the beach","Top of a mountain","At home","At a restaurant"]', 'At the beach', 4],
            ['What is our first joint hobby?', '["Hiking","Cooking","Gaming","Photography"]', 'Hiking', 5],
            ['How many years have we been together?', '["2 Years","3 Years","4 Years","5 Years"]', '3 Years', 6],
            ['Who is the better cook?', '["Miko","Mae","We order delivery","It\'s a tie"]', 'Miko', 7],
            ['What was the first movie we watched?', '["Titanic","The Notebook","The Avengers","The Lion King"]', 'The Notebook', 8],
            ['What is Miko\'s dream travel destination?', '["Japan","Italy","Iceland","New Zealand"]', 'Japan', 9],
            ['What is our "Song"?', '["Perfect - Ed Sheeran","A Thousand Years","I Won\'t Give Up","All of Me"]', 'Perfect - Ed Sheeran', 10],
        ];

        $stmt = $pdo->prepare("INSERT INTO quiz_questions (question, options, correct_answer, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($defaultQuestions as $q) {
            $stmt->execute($q);
        }
        $messages[] = "Default quiz questions added (10 questions).";
    }

    // Insert default settings
    $defaultSettings = [
        ['couple_names', 'Miko & Mae'],
        ['wedding_date', '2026-02-28'],
        ['wedding_time', '15:00'],
        ['venue_name', 'San Agustin Church'],
        ['venue_address', 'Gen. Luna St, Intramuros, Manila'],
        ['rsvp_deadline', '2026-01-15'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $s) {
        $stmt->execute($s);
    }
    $messages[] = "Default settings configured.";

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Website - Installation</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2A9D8F, #264653);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #264653;
            margin-bottom: 24px;
            font-size: 1.75rem;
        }

        .message {
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 16px;
            border-radius: 8px;
            margin-top: 24px;
            border-left: 4px solid #ffc107;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2A9D8F;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }

        .btn:hover {
            background: #238b7e;
        }

        .credentials {
            background: #e8f5e9;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
        }

        .credentials strong {
            color: #2A9D8F;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎉 Wedding Website Installation</h1>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="message error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message success">✓
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($errors)): ?>
            <div class="credentials">
                <strong>Default Admin Login:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>MikoMae2026!</code>
            </div>

            <div class="warning">
                <strong>⚠️ Security Warning:</strong><br>
                Please delete this file (<code>setup/install.php</code>) after installation!<br>
                Also change the default password immediately after first login.
            </div>

            <a href="../login.php" class="btn">Go to Admin Login →</a>
        <?php endif; ?>
    </div>
</body>

</html>