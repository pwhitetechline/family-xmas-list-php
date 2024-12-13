<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=localhost;dbname=christmas_list;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "Connected to database successfully!<br>";
    
    // Test UUID generation and insertion
    $uuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $name = "Test User";
    $email = "test" . time() . "@example.com";
    $password = password_hash("password123", PASSWORD_DEFAULT);
    
    echo "Attempting to insert test user...<br>";
    echo "UUID: $uuid<br>";
    echo "Name: $name<br>";
    echo "Email: $email<br>";
    
    // Insert test user
    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$uuid, $name, $email, $password]);
    
    if ($result) {
        echo "Test user inserted successfully!<br>";
        
        // Verify the insertion
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$uuid]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "User found in database:<br>";
            echo "ID: " . $user['id'] . "<br>";
            echo "Name: " . $user['name'] . "<br>";
            echo "Email: " . $user['email'] . "<br>";
        } else {
            echo "Warning: User was inserted but could not be retrieved!<br>";
        }
    } else {
        echo "Failed to insert test user.<br>";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
