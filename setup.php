<?php
$db_host = base64_decode($_POST[base64_encode('DB_HOST')] ?? '');
$db_name = base64_decode($_POST[base64_encode('DB_NAME')] ?? '');
$db_user = base64_decode($_POST[base64_encode('DB_USER')] ?? '');
$db_pass = base64_decode($_POST[base64_encode('DB_PASS')] ?? '');
$root     = base64_decode($_POST[base64_encode('ROOT')] ?? '');
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $tables = [
        'users' => [
            'create' => "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                image TEXT NOT NULL,
                username VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                allowed_routes TEXT NOT NULL,
                details TEXT NOT NULL,
                preferences TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'default' => [
                'check' => "SELECT COUNT(*) FROM users WHERE role = 'superadmin'",
                'insert' => "INSERT INTO users (name, username, password, role, allowed_routes, details, preferences) VALUES (?, ?, ?, ?, ?, ?, ?)",
                'params' => [
                    'Super Admin',
                    'admin',
                    password_hash('admin123', PASSWORD_DEFAULT),
                    'superadmin',
                    json_encode([]),
                    json_encode([]),
                    json_encode(['color_theme' => [
                        'bg' => '#ffffff',
                        'accent' => '#2563eb',
                        'textPrimary' => '#0f172a',
                        'textSecondary' => '#64748b'
                    ]])
                ]
            ]
        ],
        'navbar' => [
            'create' => "CREATE TABLE IF NOT EXISTS navbar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id VARCHAR(100) NOT NULL,
                label VARCHAR(100) UNIQUE NOT NULL,
                slug VARCHAR(255) NOT NULL,
                position INT(11) NOT NULL,
                is_view INT(11) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'default' => [
                'check' => "SELECT COUNT(*) FROM navbar WHERE parent_id = '0'",
                'insert' => "INSERT INTO navbar (parent_id, label, slug, position, is_view) VALUES (?, ?, ?, ?, ?)",
                'params' => ['0', 'Dashboard', '', 1, 1]
            ]
        ],
        'static_types' => [
            'create' => "CREATE TABLE IF NOT EXISTS static_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type_for VARCHAR(255) NOT NULL,
                name VARCHAR(255) UNIQUE NOT NULL,
                value1 VARCHAR(255) NOT NULL,
                value2 VARCHAR(255) NOT NULL,
                value3 VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'default' => [
                'check' => "SELECT COUNT(*) FROM static_types WHERE 1",
                'insert' => "INSERT INTO static_types (type_for, name, value1) VALUES (?, ?, ?)",
                'params' => [
                    ['', 'Role', 'role'],
                    ['role', 'Super Admin', 'superadmin'],
                    ['role', 'Admin', 'admin'],
                    ['role', 'Manager', 'manager'],
                    ['role', 'Staff', 'staff'],
                    ['role', 'Customer', 'customer'],
                    ['role', 'Agent', 'agent'],
                    ['', 'Entries Per Page', 'entries_per_page'],
                    ['entries_per_page', '10 Entries', '10']
                ]
            ]
        ]
    ];
    foreach ($tables as $name => $table) {
        $pdo->exec($table['create']);
        if (!empty($table['default'])) {
            $checkStmt = $pdo->prepare($table['default']['check']);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() == 0) {
                $insertStmt = $pdo->prepare($table['default']['insert']);
                $params = $table['default']['params'];
                if (isset($params[0]) && is_array($params[0])) {
                    foreach ($params as $row) {
                        $insertStmt->execute($row);
                    }
                } else {
                    $insertStmt->execute($params);
                }
            }
        }
    }
    header("Location: $root");
    exit;
} catch (PDOException $e) {
    file_put_contents('./logs/db-logs.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    exit;
}
