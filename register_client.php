<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = trim($_POST['company']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Validation
    if (empty($company) || empty($email) || empty($password)) {
        die(json_encode(['status' => 'error', 'message' => 'Required fields are missing']));
    }

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            die(json_encode(['status' => 'error', 'message' => 'Email already registered']));
        }

        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new client
        $stmt = $pdo->prepare("
            INSERT INTO clients 
            (company_name, email, contact_person, phone, password_hash)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $company,
            $email,
            $contact,
            $phone,
            $hash
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        die(json_encode(['status' => 'error', 'message' => 'Registration failed']));
    }
}