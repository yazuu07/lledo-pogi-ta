<?php
session_start();
require 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check how many photos the user has already taken
$query = "SELECT COUNT(*) AS photo_count FROM uploads WHERE user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// If this is the first photo, set location to 'In'
if ($result['photo_count'] == 0) {
    $location = 'In';
} else {
    // If this is not the first photo, set location to 'Out'
    $location = 'Out';
}

// Check if the image data is provided
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['image']) || empty($data['image'])) {
    echo json_encode(["success" => false, "error" => "No image provided"]);
    exit();
}

$imageData = $data['image'];
$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$image = base64_decode($imageData);

if (!$image) {
    echo json_encode(["success" => false, "error" => "Invalid image data"]);
    exit();
}

// Define the directory for saving images
$uploadsDir = 'uploads/';
if (!file_exists($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        echo json_encode(["success" => false, "error" => "Failed to create upload directory"]);
        exit();
    }
}

$filename = uniqid('photo_') . '.jpg';
$filePath = $uploadsDir . $filename;

// Save the image to the file
if (file_put_contents($filePath, $image) === false) {
    echo json_encode(["success" => false, "error" => "Failed to save image"]);
    exit();
}

// Insert the image with the determined location into the database
try {
    $stmt = $pdo->prepare("INSERT INTO uploads (user_id, image_path, location) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $filePath, $location]);

    echo json_encode(["success" => true, "location" => $location]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
