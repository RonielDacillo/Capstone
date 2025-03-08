<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'staff';

    // Validate inputs
    if (empty($username) || empty($password)) {
        die(json_encode(['status' => 'error', 'message' => 'All fields are required']));
    }

    try {
        // Get user with role check
        $stmt = $pdo->prepare("
            SELECT * FROM admins 
            WHERE username = :username 
            AND role = :role 
            AND is_active = TRUE
        ");
        $stmt->execute([':username' => $username, ':role' => $role]);
        $user = $stmt->fetch();

        // Account lock check
        if ($user && $user['account_locked_until'] > time()) {
            $remaining = $user['account_locked_until'] - time();
            die(json_encode([
                'status' => 'error',
                'message' => "Account locked. Try again in ".gmdate("i:s", $remaining)
            ]));
        }

        // Verify credentials
        if ($user && password_verify($password, $user['password_hash'])) {
            // Successful login
            $_SESSION['admin'] = [
                'id' => $user['admin_id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'last_login' => $user['last_login'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ];

            // Update admin record
            $pdo->prepare("
                UPDATE admins 
                SET 
                    last_login = NOW(),
                    login_attempts = 0,
                    account_locked_until = NULL 
                WHERE admin_id = :id
            ")->execute([':id' => $user['admin_id']);

            // Log successful attempt
            log_attempt($user['admin_id'], true);

            // Redirect based on role
            $redirect = match($user['role']) {
                'superadmin' => 'superadmin_dashboard.php',
                'developer' => 'developer_dashboard.php',
                default => 'staff_dashboard.php'
            };

            echo json_encode(['status' => 'success', 'redirect' => $redirect]);
            exit;
        } else {
            // Failed attempt
            if ($user) {
                $attempts = $user['login_attempts'] + 1;
                $lock_time = $attempts >= 5 ? time() + 900 : null; // 15 min lock
                
                $pdo->prepare("
                    UPDATE admins 
                    SET 
                        login_attempts = :attempts,
                        account_locked_until = :lock_time 
                    WHERE admin_id = :id
                ")->execute([
                    ':attempts' => $attempts,
                    ':lock_time' => $lock_time,
                    ':id' => $user['admin_id']
                ]);
            }

            log_attempt($user['admin_id'] ?? null, false);
            throw new Exception('Invalid credentials');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        die(json_encode([
            'status' => 'error',
            'message' => 'Authentication failed. Check credentials.'
        ]));
    }
}

function log_attempt($admin_id, $success) {
    global $pdo;
    $pdo->prepare("
        INSERT INTO login_attempts 
        (admin_id, ip_address, user_agent, successful)
        VALUES (:admin_id, :ip, :ua, :success)
    ")->execute([
        ':admin_id' => $admin_id,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT'],
        ':success' => $success
    ]);
}