<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// UTF-8 설정
if (!$mysqli->set_charset("utf8")) {
    die("Failed to set charset to utf8: " . $mysqli->error);
}

// GET 요청 처리 (비밀번호 입력 폼 표시)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['post_id'])) {
    $postId = intval($_GET['post_id']);
    echo '
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>게시글 삭제</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1 class="mb-4">게시글 삭제</h1>
            <form method="POST" action="delete_content.php">
                <input type="hidden" name="post_id" value="' . $postId . '">
                <div class="mb-3">
                    <label for="password" class="form-label">비밀번호:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger">삭제</button>
                <a href="content.php?post_id=' . $postId . '" class="btn btn-secondary">취소</a>
            </form>
        </div>
    </body>
    </html>';
    exit();
}

// POST 요청 처리 (삭제 수행)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['password'])) {
    $postId = intval($_POST['post_id']);
    $password = $_POST['password'];

    // 게시글 비밀번호 확인
    $stmt = $mysqli->prepare("SELECT userpassword FROM posts WHERE id = ?");
    if (!$stmt) {
        die("쿼리 준비 실패: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    if ($stmt->fetch()) {
        $stmt->close();
        if ($storedPassword === $password) {
            // 게시글 삭제
            $deleteStmt = $mysqli->prepare("DELETE FROM posts WHERE id = ?");
            $deleteStmt->bind_param("i", $postId);
            $deleteStmt->execute();
            $deleteStmt->close();

            // 첨부 파일 삭제
            $deleteAttachmentsStmt = $mysqli->prepare("DELETE FROM attachments WHERE post_id = ?");
            $deleteAttachmentsStmt->bind_param("i", $postId);
            $deleteAttachmentsStmt->execute();
            $deleteAttachmentsStmt->close();

            // 기존 ID와 새로운 ID 매핑 생성
            $idMapping = array();
            $result = $mysqli->query("SELECT id FROM posts ORDER BY id");
            $newId = 1;
            while ($row = $result->fetch_assoc()) {
                $idMapping[$row['id']] = $newId; // 기존 ID => 새로운 ID 매핑
                $newId++;
            }

            // posts 테이블 ID 재정렬
            foreach ($idMapping as $oldId => $newId) {
                $mysqli->query("UPDATE posts SET id = $newId WHERE id = $oldId");
            }

            // AUTO_INCREMENT 값 재조정
            $maxIdResult = $mysqli->query("SELECT MAX(id) AS max_id FROM posts");
            $maxIdRow = $maxIdResult->fetch_assoc();
            $maxId = $maxIdRow['max_id'];
            $mysqli->query("ALTER TABLE posts AUTO_INCREMENT = " . ($maxId + 1));

            // comments 테이블의 post_id 업데이트
            foreach ($idMapping as $oldId => $newId) {
                $mysqli->query("UPDATE comments SET post_id = $newId WHERE post_id = $oldId");
            }



            echo "<meta charset='UTF-8'><script>alert('게시글이 삭제되었습니다.'); window.location.href = 'index.php';</script>";
        } else {
            echo "<meta charset='UTF-8'><script>alert('비밀번호가 일치하지 않습니다.'); window.history.back();</script>";
        }
    } else {
        echo "<meta charset='UTF-8'><script>alert('게시글을 찾을 수 없습니다.'); window.history.back();</script>";
    }
} else {
    echo "<meta charset='UTF-8'><script>alert('잘못된 요청입니다.'); window.history.back();</script>";
}
?>
