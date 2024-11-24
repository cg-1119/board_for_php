<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 댓글 추가 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postId = $_POST['post_id'];
    $username = $_POST['username'];
    $userpassword = $_POST['userpassword'];
    $content = $_POST['content'];
    $created_at = date('Y-m-d H:i:s');

    // 댓글 삽입
    $stmt = $mysqli->prepare("INSERT INTO comments (post_id, username, userpassword, content, created_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("issss", $postId, $username, $userpassword, $content, $created_at);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // 댓글 수 업데이트
    $stmt = $mysqli->prepare("UPDATE posts SET comments = IFNULL(comments, 0) + 1 WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    header("Location: content.php?post_id=$postId");
    exit();
} else {
    die("Invalid request method.");
}
?>