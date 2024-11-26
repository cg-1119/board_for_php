<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// 현재 게시글 ID 가져오기
$currentPostId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// 이전 게시글
$prevPost = null;
$stmt = $mysqli->prepare("SELECT id, title FROM posts WHERE id < ? ORDER BY id DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $currentPostId);
    $stmt->execute();
    $stmt->bind_result($prevId, $prevTitle);
    if ($stmt->fetch()) {
        $prevPost = array('id' => $prevId, 'title' => $prevTitle);
    }
    $stmt->close();
}

// 다음 게시글
$nextPost = null;
$stmt = $mysqli->prepare("SELECT id, title FROM posts WHERE id > ? ORDER BY id ASC LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $currentPostId);
    $stmt->execute();
    $stmt->bind_result($nextId, $nextTitle);
    if ($stmt->fetch()) {
        $nextPost = array('id' => $nextId, 'title' => $nextTitle);
    }
    $stmt->close();
}

// 게시물 가져오기
if (isset($_GET['post_id'])) {
    $postId = $_GET['post_id'];

    // 조회수 업데이트
    $updateViewsStmt = $mysqli->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    if (!$updateViewsStmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $updateViewsStmt->bind_param("i", $postId);
    if (!$updateViewsStmt->execute()) {
        die("Execute failed: " . $updateViewsStmt->error);
    }
    $updateViewsStmt->close();

    // 게시물 정보 가져오기
    $stmt = $mysqli->prepare("SELECT title, content, created_at, username, views FROM posts WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->bind_result($title, $content, $created_at, $username, $views);
    $stmt->fetch();
    $stmt->close();

    // 첨부 파일 가져오기
    $stmt = $mysqli->prepare("SELECT file_name, file_path FROM attachments WHERE post_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->bind_result($file_name, $file_path);
    $attachments = array();
    while ($stmt->fetch()) {
        $attachments[] = array(
            'file_name' => $file_name,
            'file_path' => $file_path
        );
    }
    $stmt->close();

    // 댓글 가져오기
    $stmt = $mysqli->prepare("SELECT id, username, content, created_at FROM comments WHERE post_id = ? ORDER BY created_at DESC");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $postId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->bind_result($comment_id, $username, $comment_content, $comment_created_at);
    $comments = array();
    while ($stmt->fetch()) {
        $comments[] = array(
            'id' => $comment_id,
            'username' => $username,
            'content' => $comment_content,
            'created_at' => $comment_created_at
        );
    }
    $stmt->close();
} else {
    die("Invalid post ID.");
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시물 내용</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <link href="cumtom.css" rel="stylesheet">
    <style>
        .active-post {
            background-color: #dff0d8;
            font-weight: bold;
            border-left: 5px solid #5cb85c;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($title); ?></h3>
                <small class="text-muted">작성자: <?php echo htmlspecialchars($username); ?> | 작성일:
                    <?php echo $created_at; ?>
                    | 조회수: <?php echo $views; ?></small>
            </div>
            <div class="card-footer">
                <h5>파일:</h5>
                <ul>
                    <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-body">
                <?php echo htmlspecialchars_decode($content); ?>
            </div>
            <?php if (!empty($attachments)): ?>
                <div class="mt-4">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php
                        // 이미지 파일 여부 확인
                        $imageExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                        $fileExtension = strtolower(pathinfo($attachment['file_path'], PATHINFO_EXTENSION));
                        if (in_array($fileExtension, $imageExtensions)):
                            ?>
                            <div class="text-center">
                                <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="첨부 이미지"
                                    class="img-fluid mt-3" style="max-width: 50%; height: auto;">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <a href="modify_content.php?post_id=<?php echo $postId; ?>" class="btn btn-warning me-2">
                <i class="bi bi-pencil-square"></i>
            </a>
            <a href="delete_content.php?post_id=<?php echo $postId; ?>" class="btn btn-danger me-2"
                onclick="return confirm('정말 삭제하시겠습니까?');">
                <i class="bi bi-trash"></i>
            </a>
            <a href="input.php" class="btn btn-success me-2">
                <i class="bi bi-pencil-square"></i>
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-list-ul"></i>
            </a>
        </div>

        <!-- 댓글 -->
        <div class="mt-4">
            <h5>댓글 <?php echo count($comments); ?>개</h5>
            <?php if (!empty($comments)): ?>
                <ul class="list-group mb-4">
                    <?php foreach ($comments as $comment): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <!-- 왼쪽: 댓글 내용 -->
                            <div>
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong><br>
                                <span><?php echo htmlspecialchars($comment['content']); ?></span><br>
                                <small class="text-muted">작성일: <?php echo $comment['created_at']; ?></small>
                            </div>
                            <!-- 오른쪽: 수정/삭제 버튼 -->
                            <div class="text-end">
                                <a href="modify_comment.php?comment_id=<?php echo $comment['id']; ?>&post_id=<?php echo $postId; ?>"
                                    class="text-black">수정</a>
                                |
                                <a href="delete_comment.php?comment_id=<?php echo $comment['id']; ?>&post_id=<?php echo $postId; ?>"
                                    class="text-black">삭제</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>댓글이 없습니다.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5 border p-3">
            <form method="POST" action="add_comment.php" class="d-flex align-items-start">
                <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                <!-- 왼쪽: 이름과 비밀번호 -->
                <div class="me-3">
                    <div class="mb-3">
                        <label for="username" class="form-label">이름</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div>
                        <label for="userpassword" class="form-label">비밀번호</label>
                        <input type="password" id="userpassword" name="userpassword" class="form-control" required>
                    </div>
                </div>

                <!-- 오른쪽: 댓글 내용 -->
                <div class="flex-grow-1 me-3">
                    <label for="comment-content" class="form-label">내용</label>
                    <textarea id="comment-content" name="content" class="form-control" rows="4" required></textarea>
                </div>

                <!-- 등록 버튼 -->
                <div class="d-flex align-self-end">
                    <button type="submit" class="btn btn-primary">등록</button>
                </div>
            </form>
        </div>

        <!-- 이전 글, 다음 글 -->
        <div class="d-flex justify-content-between mt-4">
            <!-- 다음 게시글 -->
            <?php if ($nextPost): ?>
                <a href="content.php?post_id=<?php echo $nextPost['id']; ?>" class="btn btn-outline-primary">
                    다음 게시글: <?php echo htmlspecialchars($nextPost['title']); ?>
                </a>
            <?php else: ?>
                <button class="btn btn-outline-secondary" disabled>다음 게시글 없음</button>
            <?php endif; ?>

            <!-- 이전 게시글 -->
            <?php if ($prevPost): ?>
                <a href="content.php?post_id=<?php echo $prevPost['id']; ?>" class="btn btn-outline-primary">
                    이전 게시글: <?php echo htmlspecialchars($prevPost['title']); ?>
                </a>
            <?php else: ?>
                <button class="btn btn-outline-secondary" disabled>이전 게시글 없음</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    // 현재 게시글 ID 가져오기
    $currentPostId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    // 페이지네비게이션 및 검색 필터 처리
    $postsPerPage = 5;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($current_page - 1) * $postsPerPage;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchField = isset($_GET['search_field']) ? $_GET['search_field'] : 'title';

    // 게시물 데이터 가져오기
    $stmt = $mysqli->prepare("
    SELECT id, title, created_at, has_attachment, username, IFNULL(comments, 0) AS comments, views
    FROM posts
    WHERE $searchField LIKE CONCAT('%', ?, '%')
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
    $stmt->bind_param("sii", $search, $postsPerPage, $offset);
    $stmt->execute();
    $stmt->bind_result($id, $title, $created_at, $has_attachment, $username, $comments, $views);

    $posts = array();
    while ($stmt->fetch()) {
        $posts[] = array(
            'id' => $id,
            'title' => $title,
            'created_at' => $created_at,
            'has_attachment' => $has_attachment,
            'username' => $username,
            'comments' => $comments,
            'views' => $views
        );
    }
    $stmt->close();

    // 총 게시물 수 가져오기
    $countStmt = $mysqli->prepare("SELECT COUNT(*) FROM posts WHERE $searchField LIKE CONCAT('%', ?, '%')");
    $countStmt->bind_param("s", $search);
    $countStmt->execute();
    $countStmt->bind_result($totalCount);
    $countStmt->fetch();
    $countStmt->close();

    $totalPages = ceil($totalCount / $postsPerPage);
    ?>
    <div class="container mt-5">
        <!-- 게시글 목록 -->
        <?php if (!empty($posts)): ?>
            <ul class="list-group">
                <?php foreach ($posts as $post): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center post-item
                    <?php echo $currentPostId === $post['id'] ? 'active-post' : ''; ?>">
                        <div>
                            <span class="badge bg-secondary rounded-pill me-2"><?php echo $post['id']; ?></span>
                            <a href="content.php?post_id=<?php echo $post['id']; ?>&page=<?php echo $current_page; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>"
                                class="text-black">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                            <?php if ($post['has_attachment']): ?>
                                <i class="bi bi-paperclip text-secondary ms-2"></i>
                            <?php endif; ?>
                            [<?php echo $post['comments']; ?>]
                            <br>
                            <small class="text-muted">
                                작성자: <?php echo htmlspecialchars($post['username']); ?> |
                                작성일: <?php echo $post['created_at']; ?> |
                                조회수: <?php echo $post['views']; ?>
                            </small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- 페이지네비게이션 -->
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $maxVisiblePages = 5;
                    $currentGroup = ceil($current_page / $maxVisiblePages);
                    $startPage = ($currentGroup - 1) * $maxVisiblePages + 1;
                    $endPage = min($startPage + $maxVisiblePages - 1, $totalPages);
                    ?>

                    <!-- 처음으로 -->
                    <li class="page-item <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="content.php?post_id=<?php echo htmlspecialchars($currentPostId); ?>&page=1&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&laquo;&laquo;</a>
                    </li>

                    <!-- 이전 그룹 -->
                    <?php if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="content.php?post_id=<?php echo htmlspecialchars($currentPostId); ?>&page=<?php echo $startPage - $maxVisiblePages; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>

                    <!-- 페이지 번호 -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="content.php?post_id=<?php echo htmlspecialchars($currentPostId); ?>&page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- 다음 그룹 -->
                    <?php if ($endPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="content.php?post_id=<?php echo htmlspecialchars($currentPostId); ?>&page=<?php echo $startPage + $maxVisiblePages; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>

                    <!-- 마지막으로 -->
                    <li class="page-item <?php echo $current_page == $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="content.php?post_id=<?php echo htmlspecialchars($currentPostId); ?>&page=<?php echo $totalPages; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&raquo;&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php else: ?>
            <p>게시물이 없습니다.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>