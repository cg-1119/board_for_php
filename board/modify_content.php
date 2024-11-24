<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 게시물 가져오기
if (isset($_GET['post_id'])) {
    $postId = $_GET['post_id'];

    // 비밀번호 확인 폼 처리
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password_check'])) {
        $userPassword = $_POST['userpassword'];
        $stmt = $mysqli->prepare("SELECT userpassword FROM posts WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("i", $postId);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->bind_result($storedPassword);
        $stmt->fetch();
        $stmt->close();

        if ($userPassword !== $storedPassword) {
            echo '<meta charset="UTF-8"><script>alert("비밀번호가 일치하지 않습니다."); window.history.back();</script>';
            exit();
        }
    } elseif (!isset($_POST['password_check'])) {
        // 비밀번호 입력 폼 표시
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 확인</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="cumtom.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">게시물 수정 - 비밀번호 확인</h1>
        <form method="POST" action="modify_content.php?post_id=' . $postId . '">
            <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
            <div class="mb-3">
                <label for="userpassword" class="form-label">비밀번호:</label>
                <input type="password" id="userpassword" name="userpassword" class="form-control" required>
            </div>
            <button type="submit" name="password_check" class="btn btn-primary">확인</button>
            <a href="content.php?post_id=' . $postId . '" class="btn btn-secondary">취소</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
        exit();
    }

    // 게시물 데이터 가져오기
    $stmt = $mysqli->prepare("SELECT title, content FROM posts WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->bind_result($title, $content);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Invalid post ID.");
}
// 첨부 파일 처리
$fileInfo = null;
if (isset($postId)) {
    // 첨부 파일 여부 확인
    $stmt = $mysqli->prepare("SELECT has_attachment FROM posts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $stmt->bind_result($hasAttachment);
        $stmt->fetch();
        $stmt->close();

        // 첨부 파일이 있는 경우 attachments 테이블에서 파일 정보 가져오기
        if ($hasAttachment) {
            $stmt = $mysqli->prepare("SELECT file_name, file_path FROM attachments WHERE post_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->bind_result($fileName, $filePath);
                if ($stmt->fetch()) {
                    $fileInfo = array(
                        'file_name' => $fileName,
                        'file_path' => $filePath
                    );
                }
                $stmt->close();
            }
        }
    }
}

// 게시물 수정 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['password_check'])) {
    $postId = $_POST['post_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 게시물 작성</title>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="cumtom.css" rel="stylesheet">
    <style>
        .main-container {
            width: 795px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
<div class="main-container mt-5">
    <h1 class="text-center mb-4">게시물 수정</h1>
    <form method="POST" action="modify_content_process.php" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
        <div class="mb-3">
            <label for="title" class="form-label">제목</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label" >내용</label>
            <div id="editor">
                <?php echo htmlspecialchars_decode($content); ?>
            </div>
            <textarea id="content" name="content" class="form-control" style="display:none;"></textarea>
        </div>
        <div class="mb-3">
            <label for="attachment" class="form-label">파일 첨부</label>
            <?php if ($fileInfo): ?>
                <div class="mb-2">
                    <p>기존 파일:
                        <a href="<?php echo htmlspecialchars($fileInfo['file_path']); ?>" target="_blank">
                            <?php echo htmlspecialchars($fileInfo['file_name']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            <input type="file" id="attachment" name="attachment" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">수정하기</button>
    </form>
</div>

<script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
        }
    }
</script>
<script type="module">
    import {
        ClassicEditor,
        Essentials,
        Paragraph,
        Bold,
        Italic,
        Font
    } from 'ckeditor5';
    ClassicEditor
        .create( document.querySelector( '#editor' ), {
            plugins: [ Essentials, Paragraph, Bold, Italic, Font ],
            toolbar: [
                'undo', 'redo', '|', 'bold', 'italic', '|',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
            ]
        } )
        .then( editor => {
            window.editor = editor;
            document.querySelector('form').addEventListener('submit', (event) => {
                document.querySelector('#content').value = editor.getData();
            });
        } )
        .catch( error => {
            console.error( error );
        } );
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>