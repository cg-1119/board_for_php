<?php
include "Database.php";
// Database connection
$db = new Database();
$mysqli = $db->connect();

// Pagination settings
$postsPerPage = 5;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $postsPerPage;

// 게시물 검색어 처리
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchField = isset($_GET['search_field']) ? $_GET['search_field'] : 'title';

// 총 게시물 수 계산
$countStmt = $mysqli->prepare("SELECT COUNT(*) FROM posts WHERE $searchField LIKE CONCAT('%', ?, '%')");
if (!$countStmt) {
    die("Prepare failed: " . $mysqli->error);
}
$countStmt->bind_param("s", $search);
if (!$countStmt->execute()) {
    die("Execute failed: " . $countStmt->error);
}
$countStmt->bind_result($totalCount);
$countStmt->fetch();
$countStmt->close();

// 총 페이지 수 계산
$totalPages = ceil($totalCount / $postsPerPage);

// 현재 페이지에 해당하는 게시물 가져오기
$stmt = $mysqli->prepare("SELECT id, title, created_at, has_attachment, username, IFNULL(comments, 0) AS comments, views FROM posts WHERE $searchField LIKE CONCAT('%', ?, '%') ORDER BY created_at DESC LIMIT ? OFFSET ?");
if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}
$stmt->bind_param("sii", $search, $postsPerPage, $offset);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판 목록</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <style>
        /* 게시글 마우스오버 효과 */
        .post-item {
            transition: background-color 0.3s ease;
        }
        .post-item:hover {
            background-color: #f8f9fa; /* 밝은 배경 */
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4 text-center">게시판</h1>
    <div class="d-flex justify-content-center mb-4">
        <form method="GET" action="index.php" class="w-50 d-flex">
            <select name="search_field" class="form-select me-2">
                <option value="username" <?php echo $searchField === 'username' ? 'selected' : ''; ?>>작성자</option>
                <option value="title" <?php echo $searchField === 'title' ? 'selected' : ''; ?>>제목</option>
            </select>
            <input type="text" name="search" class="form-control" placeholder="검색어를 입력하세요"
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
    <div>
        <?php if ($search) : ?>
            <p>검색 기준: <?php echo $searchField === 'username' ? '작성자' : '제목'; ?> |
                검색어: <?php echo htmlspecialchars($search); ?> | 찾은 개수: <?php echo $totalCount; ?>개</p>
        <?php endif; ?>
    </div>
    <?php if (!empty($posts)) : ?>
        <ul class="list-group">
            <?php foreach ($posts as $post) : ?>
                <li class="list-group-item d-flex justify-content-between align-items-center post-item">
                    <div>
                        <span class="badge bg-secondary rounded-pill me-2"><?php echo $post['id']; ?></span>
                        <a href="content.php?post_id=<?php echo $post['id']; ?>" class="text-black">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                        <?php if ($post['has_attachment']) : ?>
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
        <div class="d-flex justify-content-end mt-4">
            <a href="input.php" class="btn btn-success btn-square me-2">
                <i class="bi bi-pencil-square"></i>
            </a>
            <a href="index.php" class="btn btn-secondary btn-square">
                <i class="bi bi-list-ul"></i>
            </a>
        </div>
        <!-- 페이지 네비게이션 -->
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination">
                <li class="page-item <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="?page=1&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&laquo;&laquo;</a>
                </li>
                <li class="page-item <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="?page=<?php echo $current_page - 1; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&laquo;</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                    <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                        <a class="page-link"
                           href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $current_page == $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="?page=<?php echo $current_page + 1; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&raquo;</a>
                </li>
                <li class="page-item <?php echo $current_page == $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="?page=<?php echo $totalPages; ?>&search=<?php echo htmlspecialchars($search); ?>&search_field=<?php echo htmlspecialchars($searchField); ?>">&raquo;&raquo;</a>
                </li>
            </ul>
        </nav>
    <?php else : ?>
        <p>게시물이 없습니다.</p>
        <p>게시물을 작성 해 보세요.</p>
        <div class="d-flex justify-content-end mt-4">
            <a href="input.php" class="btn btn-success btn-square me-2">
                <i class="bi bi-pencil-square"></i>
            </a>
            <a href="index.php" class="btn btn-secondary btn-square">
                <i class="bi bi-list-ul"></i>
            </a>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>