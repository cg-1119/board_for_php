CREATE TABLE posts (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at VARCHAR(255) NOT NULL,
    has_attachment TINYINT(1) DEFAULT 0,
    username VARCHAR(100) NOT NULL,
    userpassword VARCHAR(255) NOT NULL,
    views INT DEFAULT 0,
    comments INT DEFAULT 0,
    PRIMARY KEY (id)
)DEFAULT CHARSET=utf8;

CREATE TABLE attachments
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8;

CREATE TABLE comments (
    id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    username VARCHAR(45) NOT NULL,
    userpassword VARCHAR(255) NOT NULL,
    created_at VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
)DEFAULT CHARSET=utf8;
