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

// GET 요청: 비밀번호 입력 폼 표시
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo '
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>댓글 삭제</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1 class="mb-4">댓글 삭제</h1>
            <form method="POST" action="delete_comment.php?comment_id=' . $commentId . '&post_id=' . $postId . '">
                <div class="mb-3">
                    <label for="password" class="form-label">비밀번호:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger">삭제</button>
                <a href="content.php?post_id=' . $postId . '" class="btn btn-secondary">취소</a>
            </form>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
    exit();
}

// POST 요청: 댓글 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // 비밀번호 확인
    $stmt = mysqli_prepare($mysqli, "SELECT userpassword FROM comments WHERE id = ?");
    if (!$stmt) {
        die('쿼리 준비 실패: ' . mysqli_error($mysqli));
    }

    mysqli_stmt_bind_param($stmt, "i", $commentId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $storedPassword);
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        if ($password === $storedPassword) {
            // 댓글 삭제
            $deleteStmt = mysqli_prepare($mysqli, "DELETE FROM comments WHERE id = ?");
            if (!$deleteStmt) {
                die('댓글 삭제 준비 실패: ' . mysqli_error($mysqli));
            }

            mysqli_stmt_bind_param($deleteStmt, "i", $commentId);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);

            // 댓글 수 업데이트
            $updateStmt = mysqli_prepare($mysqli, "UPDATE posts SET comments = comments - 1 WHERE id = ?");
            if (!$updateStmt) {
                die('댓글 수 업데이트 실패: ' . mysqli_error($mysqli));
            }

            mysqli_stmt_bind_param($updateStmt, "i", $postId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            echo '<meta charset="UTF-8"><script>alert("댓글이 삭제되었습니다."); window.location.href = "content.php?post_id=' . $postId . '";</script>';
        } else {
            echo "<meta charset='UTF-8'><script>alert('비밀번호가 일치하지 않습니다.'); window.history.back();</script>";
        }
    } else {
        echo "<meta charset='UTF-8'><script>alert('댓글을 찾을 수 없습니다.'); window.history.back();</script>";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($mysqli);
?>
