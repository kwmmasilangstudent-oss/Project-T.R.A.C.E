<?php
/**
 * Database Seed Script
 * 
 * Creates default accounts for each role with password "admin123"
 * 
 * Usage:
 *   - Via browser: http://localhost/FinalTrace/database/seed.php
 *   - Via CLI: php database/seed.php
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDbConnection();

$passwordHash = password_hash('admin123', PASSWORD_DEFAULT);

$users = [
    [
        'full_name' => 'Administrator',
        'email' => 'admin@trace.test',
        'role' => 'admin',
        'status' => 'active'
    ],
    [
        'full_name' => 'Barangay Secretary',
        'email' => 'secretary@trace.test',
        'role' => 'secretary',
        'status' => 'active'
    ],
    [
        'full_name' => 'Resident User',
        'email' => 'resident@trace.test',
        'role' => 'resident',
        'status' => 'active'
    ]
];

$results = [];

foreach ($users as $user) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$user['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ?, status = ? WHERE id = ?');
        $update->execute([$passwordHash, $user['full_name'], $user['role'], $user['status'], $existing['id']]);
        $results[] = "Updated: {$user['full_name']} ({$user['email']}) - Role: {$user['role']}";
    } else {
        $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$user['full_name'], $user['email'], $passwordHash, $user['role'], $user['status']]);
        $results[] = "Created: {$user['full_name']} ({$user['email']}) - Role: {$user['role']}";
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seed - Project T.R.A.C.E.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="glass-card p-5">
            <h2 class="mb-4">Database Seed Results</h2>
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle"></i> Seed Completed</h5>
                <p class="mb-0">All accounts have been created/updated with password: <strong>admin123</strong></p>
            </div>
            <ul class="list-group list-group-flush mt-3">
                <?php foreach ($results as $result): ?>
                    <li class="list-group-item">
                        <i class="bi bi-check text-success me-2"></i><?php echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-4">
                <h5>Login Credentials</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Administrator</td>
                            <td>admin@trace.test</td>
                            <td><code>admin123</code></td>
                        </tr>
                        <tr>
                            <td>Barangay Secretary</td>
                            <td>secretary@trace.test</td>
                            <td><code>admin123</code></td>
                        </tr>
                        <tr>
                            <td>Resident</td>
                            <td>resident@trace.test</td>
                            <td><code>admin123</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary">Go to Login</a>
            </div>
        </div>
    </div>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 1.2rem;
        }
    </style>
</body>
</html>
