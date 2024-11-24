<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 댓글 ID 및 게시물 ID 가져오기
$commentId = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if ($commentId === 0 || $postId === 0) {
    die('<meta charset="UTF-8"><script>alert("잘못된 요청입니다."); window.location.href = "index.php";</script>');
}

// GET 요청: 비밀번호 확인 폼 표시
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['step'])) {
    echo '
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>댓글 수정</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1 class="mb-4">댓글 수정 - 비밀번호 확인</h1>
            <form method="POST" action="modify_comment.php?comment_id=' . $commentId . '&post_id=' . $postId . '&step=verify">
                <div class="mb-3">
                    <label for="password" class="form-label">비밀번호:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">확인</button>
                <a href="content.php?post_id=' . $postId . '" class="btn btn-secondary">취소</a>
            </form>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
    exit();
}

// POST 요청: 비밀번호 확인 단계
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['step']) && $_GET['step'] === 'verify') {
    $password = $_POST['password'];

    // 비밀번호 확인
    $stmt = $mysqli->prepare("SELECT userpassword, username, content FROM comments WHERE id = ?");
    if (!$stmt) {
        die('쿼리 준비 실패: ' . $mysqli->error);
    }

    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $stmt->bind_result($storedPassword, $username, $content);
    $fetchSuccess = $stmt->fetch();
    $stmt->close();
    if ($fetchSuccess) {
        if ($password === $storedPassword) {
            // 비밀번호 확인 성공, 수정 폼 표시
            echo '
            <!DOCTYPE html>
            <html lang="ko">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>댓글 수정</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-5">
                    <h1 class="mb-4">댓글 수정</h1>
                    <form method="POST" action="modify_comment.php?comment_id=' . $commentId . '&post_id=' . $postId . '&step=update">
                        <div class="mb-3">
                            <label for="username" class="form-label">작성자 이름:</label>
                            <input type="text" id="username" name="username" class="form-control" value="' . htmlspecialchars($username) . '" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">내용:</label>
                            <textarea id="content" name="content" class="form-control" rows="4" required>' . htmlspecialchars($content) . '</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">수정</button>
                        <a href="content.php?post_id=' . $postId . '" class="btn btn-secondary">취소</a>
                    </form>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>';
        } else {
            echo '<meta charset="UTF-8"><script>alert("비밀번호가 일치하지 않습니다."); window.history.back();</script>';
        }
    } else {
        echo '<meta charset="UTF-8"><script>alert("댓글을 찾을 수 없습니다."); window.history.back();</script>';
    }
    exit();
}

// POST 요청: 댓글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['step']) && $_GET['step'] === 'update') {
    $newUsername = $_POST['username'];
    $newContent = $_POST['content'];

    // 댓글 수정
    $stmt = $mysqli->prepare("UPDATE comments SET username = ?, content = ? WHERE id = ?");
    if (!$stmt) {
        die('댓글 수정 준비 실패: ' . $mysqli->error);
    }

    $stmt->bind_param("ssi", $newUsername, $newContent, $commentId);
    if ($stmt->execute()) {
        echo '<meta charset="UTF-8"><script>alert("댓글이 수정되었습니다."); window.location.href = "content.php?post_id=' . $postId . '";</script>';
    } else {
        echo '<meta charset="UTF-8"><script>alert("댓글 수정에 실패했습니다."); window.history.back();</script>';
    }
    $stmt->close();
    exit();
}

$mysqli->close();
?>
