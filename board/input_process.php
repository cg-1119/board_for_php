<?php
ob_start();
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 게시물 추가 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = stripslashes($_POST['content']); // stripslashes()를 사용해 백슬래시 제거
    $username = $_POST['username'];
    $userpassword = $_POST['userpassword'];
    $created_at = date('Y-m-d H:i:s');
    $has_attachment = isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0 ? 1 : 0;

    // 게시물 삽입
    $stmt = $mysqli->prepare("INSERT INTO posts (title, content, created_at, has_attachment, username, userpassword) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("sssiss", $title, $content, $created_at, $has_attachment, $username, $userpassword);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $postId = $stmt->insert_id;

    // 파일 첨부 처리
    if ($has_attachment) {
        $uploadDir = './uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filePath = $uploadDir . basename($_FILES['attachment']['name']);
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
            $stmt = $mysqli->prepare("INSERT INTO attachments (post_id, file_name, file_path) VALUES (?, ?, ?)");
            if (!$stmt) {
                die("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("iss", $postId, $_FILES['attachment']['name'], $filePath);
            if (!$stmt->execute()) {
                die("Execute failed: " . $stmt->error);
            }
        } else {
            echo "<p>파일 업로드 실패</p>";
        }
    }

    // 첨부 파일 확인 후 출력
    if ($has_attachment) {
        $stmt = $mysqli->prepare("SELECT file_name, file_path FROM attachments WHERE post_id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("i", $postId);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->bind_result($file_name, $file_path);
        if ($stmt->fetch()) {
            echo "<p>첨부 파일: <a href='" . htmlspecialchars($file_path) . "' target='_blank'>" . htmlspecialchars($file_name) . "</a></p>";
        }
        $stmt->close();
    }

    // 리다이렉트
    header("Location: index.php");
    ob_end_flush();
}
?>