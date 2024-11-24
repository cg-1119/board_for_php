<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 게시물 수정 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postId = $_POST['post_id'];
    $title = $_POST['title'];
    $content = stripslashes($_POST['content']);

    // 파일 첨부 처리
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = './uploads/';
        $fileName = basename($_FILES['attachment']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
            $hasAttachment = 1;

            // 기존 첨부 파일 삭제
            $stmt = $mysqli->prepare("DELETE FROM attachments WHERE post_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }

            // 새 첨부 파일 등록
            $stmt = $mysqli->prepare("INSERT INTO attachments (post_id, file_name, file_path) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $postId, $fileName, $filePath);
                $stmt->execute();
                $stmt->close();
            }

            // posts 테이블의 has_attachment 업데이트
            $stmt = $mysqli->prepare("UPDATE posts SET has_attachment = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            die("파일 업로드 실패.");
        }
    } else {
        // 새 파일이 없으면 has_attachment 유지
        $stmt = $mysqli->prepare("UPDATE posts SET has_attachment = (SELECT COUNT(*) FROM attachments WHERE post_id = ?) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $postId, $postId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 게시물 업데이트
    $stmt = $mysqli->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("ssi", $title, $content, $postId);
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
