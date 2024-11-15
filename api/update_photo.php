<?php

require dirname(__DIR__) . "/vendor/autoload.php";
require __DIR__ . "/bootstrap.php";


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        $database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
        $conn = $database->getConnection();

        $input = json_decode(file_get_contents("php://input"), true);
        $userId = $input['user_id'] ?? null;
        $imageData = $input['profile_photo'] ?? null;

        if (!$userId) {
            throw new Exception("User ID is required");
        }

        if (!$imageData) {
            throw new Exception("Profile photo data is required");
        }

        // Decode base64 image data
        $data = explode(',', $imageData);
        $decodedImage = base64_decode(end($data));

        // Define upload directory with proper permissions
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a unique file name
        $fileName = uniqid() . '_' . time() . '.jpg';
        $uploadFilePath = $uploadDir . $fileName;

        // Save decoded image data to file
        if (!file_put_contents($uploadFilePath, $decodedImage)) {
            throw new Exception("Error saving file.");
        }

        // Base URL for accessing images
        $profilePhotoURL = $_ENV['BASE_URL'] . "uploads/" . $fileName;

        // Check if user already has a profile photo, delete it
        $stmt = $conn->prepare("SELECT profile_photo FROM user WHERE id = :id");
        $stmt->bindValue(":id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        $existingPhoto = $stmt->fetchColumn();

        if ($existingPhoto && file_exists($existingPhoto)) {
            unlink($existingPhoto); // Delete the existing profile photo file
        }

        // Update profile photo path in database
        $sql = "UPDATE user SET profile_photo = :profile_photo WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":profile_photo", $profilePhotoURL, PDO::PARAM_STR);
        $stmt->bindValue(":id", $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile photo updated successfully!',
                'file_path' => $uploadFilePath
            ]);
        } else {
            throw new Exception("Error updating profile photo in database.");
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
