<?php
session_start();
require_once("../config/config.php"); // adjust path if needed

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to rate.";
    exit;
}

$user_id = $_SESSION['user_id'];
$research_id = intval($_POST['research_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);

if ($research_id <= 0 || $rating < 1 || $rating > 5) {
    echo "Invalid data.";
    exit;
}

// Check if rating already exists
$check = $conn->prepare("SELECT id FROM research_ratings WHERE research_id = ? AND user_id = ?");
$check->bind_param("ii", $research_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Update existing rating
    $update = $conn->prepare("UPDATE research_ratings SET rating = ?, updated_at = NOW() WHERE research_id = ? AND user_id = ?");
    $update->bind_param("iii", $rating, $research_id, $user_id);
    if ($update->execute()) {
        echo "Rating updated!";
    } else {
        echo "Error updating rating.";
    }
} else {
    // Insert new rating
    $insert = $conn->prepare("INSERT INTO research_ratings (research_id, user_id, rating) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $research_id, $user_id, $rating);
    if ($insert->execute()) {
        echo "Rating saved!";
    } else {
        echo "Error saving rating.";
    }
}

$conn->close();
?>
